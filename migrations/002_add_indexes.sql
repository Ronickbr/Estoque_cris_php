-- ============================================================
-- MIGRAÇÃO 002: Índices Otimizados para Queries Comuns
-- ============================================================
-- Objetivo: Acelerar listagens, filtros, relatórios e joins
-- ============================================================

-- ------------------------------------------------------------
-- PRODUCTS
-- ------------------------------------------------------------

-- 1. Listagem principal com filtros: manufacturer_id + name + stock range
--    Query: pages/produtos/index.php
--    WHERE manufacturer_id = ? AND name LIKE ? AND stock <= min_stock
CREATE INDEX `idx_products_filter_main` 
ON `products` (`manufacturer_id`, `name`, `stock`, `min_stock`);

-- 2. Busca por nome (prefixo) - covering para evitar lookup
--    Query: filtro "name LIKE 'termo%'"
CREATE INDEX `idx_products_name_prefix` 
ON `products` (`name`(50), `manufacturer_id`, `stock`, `min_stock`, `image_url`);

-- 3. Produtos com estoque baixo (relatório/dashboard)
--    Query: stock <= min_stock
CREATE INDEX `idx_products_low_stock` 
ON `products` (`stock`, `min_stock`, `manufacturer_id`, `name`)
WHERE `stock` <= `min_stock`;

-- 4. Join manufacturers → products (contagem de produtos por fabricante)
--    Query: pages/fabricantes/index.php
CREATE INDEX `idx_products_manufacturer_count` 
ON `products` (`manufacturer_id`);

-- ------------------------------------------------------------
-- INVENTORY_MOVEMENTS
-- ------------------------------------------------------------

-- 5. Relatório principal: range de datas + filtros opcionais
--    Query: pages/relatorios/index.php
--    WHERE created_at BETWEEN ? AND ? 
--      AND product_id = ? 
--      AND type = ?
--      AND p.manufacturer_id = ? (via JOIN)
CREATE INDEX `idx_movements_report_main` 
ON `inventory_movements` (`created_at`, `product_id`, `type`, `quantity`, `notes`);

-- 6. Cálculo de estoque atual por produto (ORDER BY created_at)
--    Query: function recalculateStock() em PHP + trigger
--    WHERE product_id = ? ORDER BY created_at ASC
CREATE INDEX `idx_movements_stock_calc` 
ON `inventory_movements` (`product_id`, `created_at`, `type`, `quantity`);

-- 7. Movimentações por usuário (auditoria)
--    Query: created_by = ?
CREATE INDEX `idx_movements_by_user` 
ON `inventory_movements` (`created_by`, `created_at`);

-- 8. Totais por tipo em período (dashboard cards)
--    Query: pages/relatorios/index.php - SUM por type
CREATE INDEX `idx_movements_type_date` 
ON `inventory_movements` (`type`, `created_at`, `product_id`, `quantity`);

-- ------------------------------------------------------------
-- MANUFACTURERS
-- ------------------------------------------------------------

-- 9. Listagem com contagem de produtos (já coberto por idx_products_manufacturer_count)
--    Mas adicionar covering para name + logo_url
CREATE INDEX `idx_manufacturers_list` 
ON `manufacturers` (`name`, `logo_url`);

-- ------------------------------------------------------------
-- USUARIOS
-- ------------------------------------------------------------

-- 10. Login por email (já tem UNIQUE)
-- 11. Busca por external_id (API)
CREATE INDEX `idx_usuarios_external_id` 
ON `usuarios` (`external_id`);

-- ------------------------------------------------------------
-- ESTATÍSTICAS E VERIFICAÇÃO
-- ------------------------------------------------------------
-- ANALYZE TABLE `products`, `inventory_movements`, `manufacturers`, `usuarios`;

-- Verificar uso dos índices:
-- SELECT * FROM sys.schema_unused_indexes WHERE object_schema = 'estoque';
-- SELECT * FROM sys.schema_index_statistics WHERE table_schema = 'estoque';

-- ============================================================
-- NOTAS DE IMPLEMENTAÇÃO
-- ============================================================
-- 1. Índices parciais (WHERE stock <= min_stock) requerem MySQL 8.0.13+
-- 2. Índices covering (INCLUDE) não existem no MySQL - usar colunas no final do índice
-- 3. Ordem das colunas: igualdade → range → ORDER BY → covering
-- 4. Monitorar: SHOW INDEX FROM table; EXPLAIN ANALYZE query;