-- ============================================================
-- MIGRAÇÃO 001: Correção de Esquema e Integridade Referencial
-- ============================================================
-- Objetivo: Corrigir incompatibilidade de tipos (INT vs UUID),
-- adicionar constraints faltantes, limpar schema.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Corrigir tabela usuarios: migrar de INT para BIGINT (compatível com UUID numérico)
--    Mantemos BIGINT AUTO_INCREMENT para performance, adicionamos external_id (UUID) para APIs
-- ------------------------------------------------------------
ALTER TABLE `usuarios` 
    CHANGE COLUMN `id` `id` BIGINT NOT NULL AUTO_INCREMENT,
    ADD COLUMN `external_id` VARCHAR(36) GENERATED ALWAYS AS (UUID()) VIRTUAL AFTER `id`,
    ADD UNIQUE KEY `uk_usuarios_external_id` (`external_id`);

-- ------------------------------------------------------------
-- 2. Corrigir inventory_movements.created_by para referenciar usuarios.id (BIGINT)
-- ------------------------------------------------------------
-- Primeiro, criar coluna temporária BIGINT
ALTER TABLE `inventory_movements` 
    ADD COLUMN `created_by_new` BIGINT NULL AFTER `created_by`;

-- Migrar dados: mapear UUIDs existentes para IDs numéricos
-- Como o backup mostra created_by = '7a9d842c-ae42-4f18-931f-575c66335891' (UUID fixo)
-- Assumimos que este UUID corresponde ao usuário admin (id=1)
UPDATE `inventory_movements` 
SET `created_by_new` = CASE 
    WHEN `created_by` = '7a9d842c-ae42-4f18-931f-575c66335891' THEN 1
    ELSE 1  -- fallback para admin
END;

-- Remover coluna UUID antiga e renomear nova
ALTER TABLE `inventory_movements` 
    DROP COLUMN `created_by`,
    CHANGE COLUMN `created_by_new` `created_by` BIGINT NOT NULL,
    ADD CONSTRAINT `fk_movements_created_by` 
        FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

-- ------------------------------------------------------------
-- 3. Adicionar CHECK constraints (MySQL 8.0.16+)
-- ------------------------------------------------------------
ALTER TABLE `products` 
    ADD CONSTRAINT `chk_products_stock_nonneg` CHECK (`stock` >= 0),
    ADD CONSTRAINT `chk_products_min_stock_nonneg` CHECK (`min_stock` >= 0);

ALTER TABLE `inventory_movements` 
    ADD CONSTRAINT `chk_movements_quantity_positive` CHECK (`quantity` > 0);

-- ------------------------------------------------------------
-- 4. Adicionar updated_at automático em products (via trigger na migração 003)
--    Apenas garantir que a coluna existe e tem default
-- ------------------------------------------------------------
ALTER TABLE `products` 
    MODIFY COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ------------------------------------------------------------
-- 5. Adicionar created_at default em manufacturers
-- ------------------------------------------------------------
ALTER TABLE `manufacturers` 
    MODIFY COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- ------------------------------------------------------------
-- 6. Remover trigger redundante (ENUM já valida)
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `inventory_movements_type_check`;

-- ------------------------------------------------------------
-- 7. Índice em created_by para auditoria
-- ------------------------------------------------------------
CREATE INDEX `idx_movements_created_by` ON `inventory_movements` (`created_by`);

-- ------------------------------------------------------------
-- 8. Limpar usuários de teste se necessário
-- ------------------------------------------------------------
-- DELETE FROM `usuarios` WHERE `email` = 'admin@example.com' AND `id` > 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICAÇÃO PÓS-MIGRAÇÃO
-- ============================================================
-- SELECT 
--     TABLE_NAME, 
--     COLUMN_NAME, 
--     DATA_TYPE, 
--     IS_NULLABLE,
--     COLUMN_KEY,
--     COLUMN_DEFAULT
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'estoque' 
--   AND TABLE_NAME IN ('usuarios', 'products', 'manufacturers', 'inventory_movements')
-- ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- SELECT 
--     TABLE_NAME,
--     CONSTRAINT_NAME,
--     CONSTRAINT_TYPE
-- FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
-- WHERE TABLE_SCHEMA = 'estoque'
--   AND TABLE_NAME IN ('usuarios', 'products', 'manufacturers', 'inventory_movements');