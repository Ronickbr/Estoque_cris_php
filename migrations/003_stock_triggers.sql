-- ============================================================
-- MIGRAÇÃO 003: Triggers para Atualização Automática de Estoque
-- ============================================================
-- Objetivo: Eliminar race condition, mover lógica para BD, garantir ACID
-- Substitui: function recalculateStock() em PHP (salvar_movimentacao.php)
-- ============================================================

DELIMITER //

-- ------------------------------------------------------------
-- 1. Trigger AFTER INSERT em inventory_movements
--    Atualiza products.stock atomicamente
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_movements_after_insert`//
CREATE TRIGGER `trg_movements_after_insert`
AFTER INSERT ON `inventory_movements`
FOR EACH ROW
BEGIN
    DECLARE v_new_stock BIGINT;
    
    -- Calcular novo estoque
    SET v_new_stock = (
        SELECT COALESCE(SUM(CASE WHEN `type` = 'entrada' THEN `quantity` ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN `type` = 'saida' THEN `quantity` ELSE 0 END), 0)
        FROM `inventory_movements`
        WHERE `product_id` = NEW.`product_id`
    );
    
    -- Atualizar produto com lock de linha (SELECT ... FOR UPDATE implícito no UPDATE)
    UPDATE `products`
    SET `stock` = v_new_stock,
        `updated_at` = NOW()
    WHERE `id` = NEW.`product_id`;
    
    -- Validar estoque não-negativo (defesa em profundidade, CHECK constraint já existe)
    IF v_new_stock < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = CONCAT('Estoque negativo detectado para produto ', NEW.`product_id`, ': ', v_new_stock);
    END IF;
END//

-- ------------------------------------------------------------
-- 2. Trigger AFTER UPDATE em inventory_movements
--    Recalcula estoque se type ou quantity mudaram
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_movements_after_update`//
CREATE TRIGGER `trg_movements_after_update`
AFTER UPDATE ON `inventory_movements`
FOR EACH ROW
BEGIN
    DECLARE v_new_stock BIGINT;
    
    -- Só recalcular se campos relevantes mudaram
    IF OLD.`type` <> NEW.`type` OR OLD.`quantity` <> NEW.`quantity` OR OLD.`product_id` <> NEW.`product_id` THEN
        
        -- Se product_id mudou, recalcular AMBOS os produtos
        IF OLD.`product_id` <> NEW.`product_id` THEN
            -- Produto antigo
            SET v_new_stock = (
                SELECT COALESCE(SUM(CASE WHEN `type` = 'entrada' THEN `quantity` ELSE 0 END), 0) -
                       COALESCE(SUM(CASE WHEN `type` = 'saida' THEN `quantity` ELSE 0 END), 0)
                FROM `inventory_movements`
                WHERE `product_id` = OLD.`product_id`
            );
            UPDATE `products`
            SET `stock` = v_new_stock,
                `updated_at` = NOW()
            WHERE `id` = OLD.`product_id`;
            
            IF v_new_stock < 0 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = CONCAT('Estoque negativo no produto antigo ', OLD.`product_id`, ': ', v_new_stock);
            END IF;
        END IF
        
        -- Produto novo (ou mesmo produto se só type/quantity mudou)
        SET v_new_stock = (
            SELECT COALESCE(SUM(CASE WHEN `type` = 'entrada' THEN `quantity` ELSE 0 END), 0) -
                   COALESCE(SUM(CASE WHEN `type` = 'saida' THEN `quantity` ELSE 0 END), 0)
            FROM `inventory_movements`
            WHERE `product_id` = NEW.`product_id`
        );
        UPDATE `products`
        SET `stock` = v_new_stock,
            `updated_at` = NOW()
        WHERE `id` = NEW.`product_id`;
        
        IF v_new_stock < 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = CONCAT('Estoque negativo no produto novo ', NEW.`product_id`, ': ', v_new_stock);
        END IF;
    END IF;
END//

-- ------------------------------------------------------------
-- 3. Trigger AFTER DELETE em inventory_movements
--    Recalcula estoque do produto afetado
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_movements_after_delete`//
CREATE TRIGGER `trg_movements_after_delete`
AFTER DELETE ON `inventory_movements`
FOR EACH ROW
BEGIN
    DECLARE v_new_stock BIGINT;
    
    SET v_new_stock = (
        SELECT COALESCE(SUM(CASE WHEN `type` = 'entrada' THEN `quantity` ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN `type` = 'saida' THEN `quantity` ELSE 0 END), 0)
        FROM `inventory_movements`
        WHERE `product_id` = OLD.`product_id`
    );
    
    UPDATE `products`
    SET `stock` = v_new_stock,
        `updated_at` = NOW()
    WHERE `id` = OLD.`product_id`;
    
    IF v_new_stock < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = CONCAT('Estoque negativo após delete no produto ', OLD.`product_id`, ': ', v_new_stock);
    END IF;
END//

-- ------------------------------------------------------------
-- 4. Trigger BEFORE INSERT em products
--    Garantir min_stock >= 0 e stock >= 0
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_products_before_insert`//
CREATE TRIGGER `trg_products_before_insert`
BEFORE INSERT ON `products`
FOR EACH ROW
BEGIN
    IF NEW.`stock` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estoque inicial não pode ser negativo';
    END IF;
    IF NEW.`min_stock` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estoque mínimo não pode ser negativo';
    END IF;
    IF NEW.`stock` < NEW.`min_stock` THEN
        -- Apenas warning, não bloqueia (pode ser intencional)
        -- SIGNAL SQLSTATE '01000' SET MESSAGE_TEXT = 'Estoque inicial abaixo do mínimo';
    END IF;
END//

-- ------------------------------------------------------------
-- 5. Trigger BEFORE UPDATE em products
--    Validar alterações de stock/min_stock
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_products_before_update`//
CREATE TRIGGER `trg_products_before_update`
BEFORE UPDATE ON `products`
FOR EACH ROW
BEGIN
    -- Não permitir alteração direta de stock (deve vir via movements)
    -- Exceto se for ajuste manual explícito (detectado via variável de sessão)
    IF OLD.`stock` <> NEW.`stock` THEN
        IF @allow_manual_stock_adjustment IS NULL OR @allow_manual_stock_adjustment = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Alteração direta de estoque não permitida. Use movimentações de estoque.';
        END IF;
    END IF;
    
    IF NEW.`min_stock` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Estoque mínimo não pode ser negativo';
    END IF;
END//

DELIMITER ;

-- ------------------------------------------------------------
-- 6. Sincronizar estoque atual (executar uma vez após criar triggers)
-- ------------------------------------------------------------
-- UPDATE `products` p
-- SET `stock` = (
--     SELECT COALESCE(SUM(CASE WHEN `type` = 'entrada' THEN `quantity` ELSE 0 END), 0) -
--            COALESCE(SUM(CASE WHEN `type` = 'saida' THEN `quantity` ELSE 0 END), 0)
--     FROM `inventory_movements` im
--     WHERE im.`product_id` = p.`id`
-- ),
-- `updated_at` = NOW();

-- ------------------------------------------------------------
-- 7. Permitir ajuste manual de estoque (para correções admin)
--    Usar: SET @allow_manual_stock_adjustment = 1; UPDATE ...; SET @allow_manual_stock_adjustment = 0;
-- ------------------------------------------------------------

-- ============================================================
-- IMPACTO NO CÓDIGO PHP
-- ============================================================
-- APÓS APLICAR ESTA MIGRAÇÃO, REMOVER DE salvar_movimentacao.php:
-- 1. Função recalculateStock() (linhas 62-100)
-- 2. Validação de estoque negativo em PHP (linhas 95-99)
-- 3. Array $movements_by_product e loop de validação (linhas 115-130)
-- 4. Loop de atualização de estoque $updated_stocks (linhas 165-168)
-- 5. Transação pode ser simplificada (apenas INSERT em movements)
--
-- NOVA LÓGICA SIMPLIFICADA:
-- BEGIN TRANSACTION;
-- INSERT INTO inventory_movements ... (trigger atualiza stock automaticamente)
-- COMMIT;
-- ============================================================

-- ============================================================
-- TESTES DE VALIDAÇÃO
-- ============================================================
-- 1. Inserir entrada: stock deve aumentar
-- INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
-- VALUES (UUID(), 'prod-id', 'entrada', 10, 'Teste', NOW(), 1);
-- SELECT stock FROM products WHERE id = 'prod-id';

-- 2. Inserir saída: stock deve diminuir
-- INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
-- VALUES (UUID(), 'prod-id', 'saida', 5, 'Teste', NOW(), 1);
-- SELECT stock FROM products WHERE id = 'prod-id';

-- 3. Tentar saída > stock: deve falhar com erro 45000
-- INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
-- VALUES (UUID(), 'prod-id', 'saida', 1000, 'Teste', NOW(), 1);

-- 4. Concurrency test: duas sessões inserindo saída simultânea
-- Sessão 1: BEGIN; INSERT saida 5; (não commit)
-- Sessão 2: INSERT saida 5; (deve aguardar lock)
-- Sessão 1: COMMIT;
-- Sessão 2: prossegue, stock final = stock_inicial - 10