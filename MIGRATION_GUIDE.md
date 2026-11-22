# Guia de Execução das Migrações - Estoque Cris

## Ordem de Execução Obrigatória

```bash
# 1. Backup completo ANTES de qualquer migração
mysqldump --single-transaction --routines --triggers \
  -u netstore_estoque -p estoque > backup_pre_migrations_$(date +%F_%H-%M).sql

# 2. Executar migrações em ordem
mysql -u netstore_estoque -p estoque < migrations/001_fix_schema.sql
mysql -u netstore_estoque -p estoque < migrations/002_add_indexes.sql
mysql -u netstore_estoque -p estoque < migrations/003_stock_triggers.sql

# 3. Sincronizar estoque atual (uma única vez após triggers)
mysql -u netstore_estoque -p estoque -e "
UPDATE products p
SET stock = (
    SELECT COALESCE(SUM(CASE WHEN type = 'entrada' THEN quantity ELSE 0 END), 0) -
           COALESCE(SUM(CASE WHEN type = 'saida' THEN quantity ELSE 0 END), 0)
    FROM inventory_movements im
    WHERE im.product_id = p.id
),
updated_at = NOW();
"

# 4. Atualizar código PHP (substituir salvar_movimentacao.php)
cp pages/relatorios/salvar_movimentacao_optimized.php pages/relatorios/salvar_movimentacao.php

# 5. Testar
# - Inserir entrada → stock aumenta
# - Inserir saída → stock diminui  
# - Tentar saída > stock → deve falhar com erro amigável
# - Concorrência: duas abas fazendo saída simultânea
```

## Verificações Pós-Migração

### 1. Verificar Constraints
```sql
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = 'estoque'
  AND TABLE_NAME IN ('usuarios', 'products', 'manufacturers', 'inventory_movements')
ORDER BY TABLE_NAME, CONSTRAINT_TYPE;
```

### 2. Verificar FKs
```sql
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'estoque'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME;
```

### 3. Verificar Índices
```sql
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX, NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'estoque'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
```

### 4. Verificar Triggers
```sql
SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = 'estoque'
ORDER BY EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION;
```

### 5. Testar Integridade Referencial
```sql
-- Deve falhar: FK usuarios → inventory_movements
INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, created_by)
VALUES (UUID(), 'prod-id', 'entrada', 1, NOW(), 99999);

-- Deve falhar: FK products → inventory_movements  
INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, created_by)
VALUES (UUID(), 'invalid-id', 'entrada', 1, NOW(), 1);

-- Deve falhar: CHECK stock >= 0
UPDATE products SET stock = -1 WHERE id = 'valid-id';

-- Deve falhar: CHECK quantity > 0
INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, created_by)
VALUES (UUID(), 'valid-id', 'entrada', 0, NOW(), 1);
```

### 6. Testar Trigger de Estoque
```sql
-- 1. Verificar estoque inicial
SELECT id, name, stock FROM products WHERE id = '06b6937c-9533-4e52-af82-963765b261fc';

-- 2. Inserir entrada (+10)
INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
VALUES (UUID(), '06b6937c-9533-4e52-af82-963765b261fc', 'entrada', 10, 'Teste trigger', NOW(), 1);

-- 3. Verificar estoque atualizado
SELECT id, name, stock FROM products WHERE id = '06b6937c-9533-4e52-af82-963765b261fc';

-- 4. Inserir saída (-5)
INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
VALUES (UUID(), '06b6937c-9533-4e52-af82-963765b261fc', 'saida', 5, 'Teste trigger', NOW(), 1);

-- 5. Verificar estoque final
SELECT id, name, stock FROM products WHERE id = '06b6937c-9533-4e52-af82-963765b261fc';

-- 6. Tentar saída > estoque (deve falhar)
INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by)
VALUES (UUID(), '06b6937c-9533-4e52-af82-963765b261fc', 'saida', 1000, 'Teste trigger', NOW(), 1);
-- ERRO ESPERADO: Estoque negativo detectado...
```

### 7. Testar Concorrência (Simular Race Condition)
```bash
# Terminal 1: Iniciar transação sem commit
mysql -u netstore_estoque -p estoque -e "
SET autocommit=0;
INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, created_by)
VALUES (UUID(), '06b6937c-9533-4e52-af82-963765b261fc', 'saida', 5, NOW(), 1);
-- NÃO FAZER COMMIT - manter transação aberta
"

# Terminal 2: Tentar outra saída (deve aguardar lock)
mysql -u netstore_estoque -p estoque -e "
INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, created_by)
VALUES (UUID(), '06b6937c-9533-4e52-af82-963765b261fc', 'saida', 3, NOW(), 1);
"

# Terminal 1: Commit
mysql -u netstore_estoque -p estoque -e "COMMIT;"

# Terminal 2: Deve prosseguir após lock liberado
# Resultado final: stock = stock_inicial - 8
```

## Rollback de Emergência

```bash
# Restaurar backup
mysql -u netstore_estoque -p estoque < backup_pre_migrations_YYYY-MM-DD_HH-MM.sql

# Restaurar código PHP original
git checkout pages/relatorios/salvar_movimentacao.php
```

## Monitoramento Pós-Deploy

### Queries Lentas (Slow Query Log)
```sql
-- Habilitar slow query log
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 1;  -- queries > 1s
SET GLOBAL log_queries_not_using_indexes = 1;

-- Verificar
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 20;
```

### Uso de Índices
```sql
-- Índices não utilizados
SELECT * FROM sys.schema_unused_indexes WHERE object_schema = 'estoque';

-- Estatísticas de uso
SELECT * FROM sys.schema_index_statistics WHERE table_schema = 'estoque';
```

### Tamanho das Tabelas
```sql
SELECT 
    TABLE_NAME,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)',
    TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'estoque'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
```

## Checklist de Validação

- [ ] Backup realizado e testado
- [ ] Migração 001 executada sem erros
- [ ] Migração 002 executada sem erros  
- [ ] Migração 003 executada sem erros
- [ ] Estoque sincronizado corretamente
- [ ] Código PHP atualizado (salvar_movimentacao.php)
- [ ] Testes unitários passando
- [ ] Teste de concorrência passando
- [ ] Testes de integração (UI) passando
- [ ] Slow query log monitorado por 24h
- [ ] Rollback testado em ambiente de staging

## Contato / Escalação

Em caso de problemas durante migração:
1. Parar aplicação (manutenção)
2. Restaurar backup
3. Analisar erro nos logs
4. Corrigir e reexecutar
5. Comunicar equipe