-- ============================================================
-- MIGRAÇÃO 004: Particionamento de inventory_movements (Futuro)
-- ============================================================
-- Objetivo: Performance para tabelas grandes (>500k linhas)
-- Aplicar quando: inventory_movements > 500k linhas OU queries por range de data lentas
-- ============================================================

-- ------------------------------------------------------------
-- OPÇÃO A: Particionamento por RANGE em created_at (Mensal)
-- ------------------------------------------------------------
-- Vantagens: 
--   - Podas de partição automáticas em queries com WHERE created_at BETWEEN
--   - Manutenção: DROP PARTITION para dados antigos (LGPD/retenção)
--   - Índices locais por partição (menores, mais rápidos)
--
-- Requisitos:
--   - created_at deve fazer parte de TODAS as chaves primárias/únicas
--   - PK atual (id) não inclui created_at → precisar mudar PK

-- PASSO 1: Adicionar created_at à PK (requer remover FK primeiro)
-- ALTER TABLE `inventory_movements` DROP FOREIGN KEY `fk_movements_product_id`;
-- ALTER TABLE `inventory_movements` DROP PRIMARY KEY;
-- ALTER TABLE `inventory_movements` ADD PRIMARY KEY (`id`, `created_at`);

-- PASSO 2: Criar partições mensais
-- ALTER TABLE `inventory_movements`
-- PARTITION BY RANGE (YEAR(`created_at`)*100 + MONTH(`created_at`)) (
--     PARTITION p202401 VALUES LESS THAN (202402),
--     PARTITION p202402 VALUES LESS THAN (202403),
--     PARTITION p202403 VALUES LESS THAN (202404),
--     PARTITION p202404 VALUES LESS THAN (202405),
--     PARTITION p202405 VALUES LESS THAN (202406),
--     PARTITION p202406 VALUES LESS THAN (202407),
--     PARTITION p202407 VALUES LESS THAN (202408),
--     PARTITION p202408 VALUES LESS THAN (202409),
--     PARTITION p202409 VALUES LESS THAN (202410),
--     PARTITION p202410 VALUES LESS THAN (202411),
--     PARTITION p202411 VALUES LESS THAN (202412),
--     PARTITION p202412 VALUES LESS THAN (202501),
--     PARTITION p202501 VALUES LESS THAN (202502),
--     PARTITION p202502 VALUES LESS THAN (202503),
--     PARTITION p202503 VALUES LESS THAN (202504),
--     PARTITION p202504 VALUES LESS THAN (202505),
--     PARTITION p202505 VALUES LESS THAN (202506),
--     PARTITION p202506 VALUES LESS THAN (202507),
--     PARTITION p202507 VALUES LESS THAN (202508),
--     PARTITION p202508 VALUES LESS THAN (202509),
--     PARTITION p202509 VALUES LESS THAN (202510),
--     PARTITION p202510 VALUES LESS THAN (202511),
--     PARTITION p202511 VALUES LESS THAN (202512),
--     PARTITION p202512 VALUES LESS THAN (202601),
--     PARTITION pmax VALUES LESS THAN MAXVALUE
-- );

-- PASSO 3: Recriar FK (índice já existe localmente em cada partição)
-- ALTER TABLE `inventory_movements` 
-- ADD CONSTRAINT `fk_movements_product_id` 
-- FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

-- ------------------------------------------------------------
-- OPÇÃO B: Particionamento por HASH em product_id (Alternativa)
-- ------------------------------------------------------------
-- Útil se: queries filtram principalmente por product_id sem range de data
-- ALTER TABLE `inventory_movements`
-- PARTITION BY HASH(`product_id`)
-- PARTITIONS 16;

-- ------------------------------------------------------------
-- OPÇÃO C: Subparticionamento (RANGE + HASH)
-- ------------------------------------------------------------
-- ALTER TABLE `inventory_movements`
-- PARTITION BY RANGE (YEAR(`created_at`)*100 + MONTH(`created_at`))
-- SUBPARTITION BY HASH(`product_id`)
-- SUBPARTITIONS 4 (
--     PARTITION p202501 VALUES LESS THAN (202502),
--     PARTITION p202502 VALUES LESS THAN (202503),
--     PARTITION pmax VALUES LESS THAN MAXVALUE
-- );

-- ------------------------------------------------------------
-- MANUTENÇÃO DE PARTIÇÕES
-- ------------------------------------------------------------

-- Adicionar nova partição mensal (executar job mensal)
-- ALTER TABLE `inventory_movements` REORGANIZE PARTITION pmax INTO (
--     PARTITION p202601 VALUES LESS THAN (202602),
--     PARTITION pmax VALUES LESS THAN MAXVALUE
-- );

-- Remover partição antiga (retenção 2 anos - LGPD)
-- ALTER TABLE `inventory_movements` DROP PARTITION p202301;

-- Verificar partição de uma query
-- EXPLAIN PARTITIONS SELECT * FROM inventory_movements WHERE created_at BETWEEN '2025-01-01' AND '2025-01-31';

-- Verificar distribuição de linhas
-- SELECT PARTITION_NAME, TABLE_ROWS 
-- FROM INFORMATION_SCHEMA.PARTITIONS 
-- WHERE TABLE_SCHEMA = 'estoque' AND TABLE_NAME = 'inventory_movements';

-- ------------------------------------------------------------
-- CONSIDERAÇÕES IMPORTANTES
-- ------------------------------------------------------------
-- 1. Triggers funcionam normalmente com tabelas particionadas
-- 2. FKs requerem que a coluna referenciada esteja no índice local
-- 3. AUTO_INCREMENT não suportado em tabelas particionadas (usar UUID ou sequence)
-- 4. ALTER TABLE ... REORGANIZE PARTITION faz table rebuild (bloqueio)
--    → Usar pt-online-schema-change ou agendar janela de manutenção
-- 5. Backup: mysqldump funciona, mas restore recria partições
-- 6. Monitorar: PARTITION_NAME em EXPLAIN deve mostrar apenas partições relevantes

-- ------------------------------------------------------------
-- QUANDO APLICAR
-- ------------------------------------------------------------
-- Gatilhos sugeridos:
-- - inventory_movements > 500.000 linhas
-- - Query de relatório (range 30 dias) > 2 segundos
-- - Tamanho da tabela > 2GB
-- - Necessidade de purge rápido de dados antigos (LGPD)

-- ------------------------------------------------------------
-- ROLLBACK (se necessário)
-- ------------------------------------------------------------
-- ALTER TABLE `inventory_movements` REMOVE PARTITIONING;
-- ALTER TABLE `inventory_movements` DROP PRIMARY KEY;
-- ALTER TABLE `inventory_movements` ADD PRIMARY KEY (`id`);
-- ALTER TABLE `inventory_movements` 
-- ADD CONSTRAINT `fk_movements_product_id` 
-- FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;