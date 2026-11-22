# Análise e Otimização do Banco de Dados - Estoque Cris

## 1. Visão Geral do Esquema Atual

### Tabelas Principais
| Tabela | Engine | Charset | Linhas (backup) |
|--------|--------|---------|-----------------|
| `manufacturers` | InnoDB | utf8mb4 | 7 |
| `products` | InnoDB | utf8mb4 | ~80 |
| `inventory_movements` | InnoDB | utf8mb4 | ~150 |
| `usuarios` | InnoDB | utf8mb4 | 2 |

### Problemas Críticos Identificados

---

## 2. Problemas Críticos

### 2.1 Incompatibilidade de Tipos de ID (CRÍTICO)
- `usuarios.id` = `INT AUTO_INCREMENT`
- `manufacturers.id`, `products.id`, `inventory_movements.id` = `VARCHAR(36)` (UUID)
- `inventory_movements.created_by` = `VARCHAR(36)` mas referencia `usuarios.id` (INT)
- **Impacto**: FK impossível, joins quebrados, integridade referencial comprometida

### 2.2 Cálculo de Estoque em PHP (Race Condition)
```php
// Em salvar_movimentacao.php - LINHA 62-100
function recalculateStock($conn, $product_id, $new_movements = []) {
    // Busca TODAS as movimentações do produto
    // Recalcula em memória
    // Valida estoque negativo
}
```
**Problemas**:
- SELECT + UPDATE não atômico
- Sem `SELECT ... FOR UPDATE` 
- Concorrência: duas saídas simultâneas podem passar validação mas deixar estoque negativo
- Performance degrada linearmente com histórico de movimentações

### 2.3 Índices Ausentes para Queries Comuns
Queries identificadas sem índices adequados:
1. `products` filtrado por `name` (LIKE) + `manufacturer_id` + `stock` vs `min_stock`
2. `inventory_movements` por `created_at` range + `product_id` + `type`
3. Relatório: JOIN products + manufacturers + inventory_movements com GROUP BY

### 2.4 Constraints Ausentes
- `stock >= 0` (CHECK)
- `min_stock >= 0` (CHECK)  
- `quantity > 0` em `inventory_movements` (CHECK)
- `updated_at` automático em `products`

### 2.5 Schema.sql Inconsistente
O arquivo `config/schema.sql` mistura dois esquemas diferentes (Português antigo + UUID novo).

---

## 3. Recomendações de Otimização

### 3.1 Migração: Unificar IDs para BIGINT
```sql
-- Vantagens: 8 bytes vs 36 bytes, índices menores, joins mais rápidos, auto-increment nativo
-- UUIDs podem ser mantidos como coluna alternativa (external_id) se necessário para APIs
```

### 3.2 Índices Compostos e Covering
```sql
-- Products: listagem com filtros
CREATE INDEX idx_products_filter ON products (manufacturer_id, name, stock, min_stock);

-- Inventory movements: relatórios por período
CREATE INDEX idx_movements_report ON inventory_movements (created_at, product_id, type, quantity);

-- Covering index para relatório de estoque atual
CREATE INDEX idx_movements_stock_calc ON inventory_movements (product_id, created_at, type, quantity);
```

### 3.3 Trigger para Atualização Automática de Estoque
```sql
-- Elimina race condition, move lógica para BD, garante consistência
CREATE TRIGGER trg_movements_after_insert
AFTER INSERT ON inventory_movements
FOR EACH ROW
BEGIN
    UPDATE products 
    SET stock = stock + IF(NEW.type = 'entrada', NEW.quantity, -NEW.quantity),
        updated_at = NOW()
    WHERE id = NEW.product_id;
END;
```

### 3.4 Materialized View para Estoque Atual (Opcional)
```sql
-- Para relatórios rápidos sem recalcular
CREATE TABLE mv_product_stock AS
SELECT p.id, p.name, p.manufacturer_id, p.min_stock,
       COALESCE(SUM(CASE WHEN im.type = 'entrada' THEN im.quantity ELSE 0 END), 0) -
       COALESCE(SUM(CASE WHEN im.type = 'saida' THEN im.quantity ELSE 0 END), 0) AS current_stock
FROM products p
LEFT JOIN inventory_movements im ON p.id = im.product_id
GROUP BY p.id, p.name, p.manufacturer_id, p.min_stock;
```

### 3.5 Particionamento de `inventory_movements`
```sql
-- Por mês em created_at para tabelas grandes (>1M linhas)
ALTER TABLE inventory_movements
PARTITION BY RANGE (YEAR(created_at)*100 + MONTH(created_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

---

## 4. Scripts de Migração Propostos

### 4.1 Migração 001: Corrigir Tipos e Constraints
Ver `migrations/001_fix_schema.sql`

### 4.2 Migração 002: Adicionar Índices Otimizados
Ver `migrations/002_add_indexes.sql`

### 4.3 Migração 003: Triggers de Estoque
Ver `migrations/003_stock_triggers.sql`

### 4.4 Migração 004: Particionamento (futuro)
Ver `migrations/004_partitioning.sql`

---

## 5. Estimativa de Impacto

| Otimização | Leitura (Relatórios) | Escrita (Movimentações) | Espaço em Disco |
|------------|---------------------|------------------------|-----------------|
| Índices compostos | **~10-50x mais rápido** | Leve overhead (~5%) | +15-20% |
| Trigger de estoque | Elimina recálculo PHP | Atômico, sem race condition | Neutro |
| BIGINT vs UUID | Joins 2-3x mais rápidos | Menos I/O | **-60% nos índices** |
| Particionamento | Scans parciais por mês | Insert em partição única | Neutro |

---

## 6. Próximos Passos Recomendados

1. **Imediato**: Aplicar migração 001 (corrigir FK usuarios→movements)
2. **Curto prazo**: Aplicar migração 002 (índices) + 003 (triggers)
3. **Médio prazo**: Migrar IDs para BIGINT (breaking change, requer coordenação)
4. **Longo prazo**: Particionamento quando `inventory_movements` > 500k linhas

---

## 7. Queries Problemáticas Identificadas

### Query 1: Listagem de produtos com filtros (pages/produtos/index.php)
```sql
SELECT p.id, p.name, p.stock, p.min_stock, p.manufacturer_id, p.image_url,
       m.name as manufacturer_name, m.logo_url 
FROM products p 
LEFT JOIN manufacturers m ON p.manufacturer_id = m.id
WHERE p.name LIKE ? AND p.manufacturer_id = ? AND p.stock <= p.min_stock
ORDER BY p.name ASC
```
**Otimização**: `CREATE INDEX idx_products_filter ON products (manufacturer_id, name, stock, min_stock);`

### Query 2: Relatório de movimentações (pages/relatorios/index.php)
```sql
SELECT m.id, m.product_id, m.type, m.quantity, m.created_at, m.notes, 
       p.name as product_name, mf.name as manufacturer_name
FROM inventory_movements m
JOIN products p ON m.product_id = p.id
LEFT JOIN manufacturers mf ON p.manufacturer_id = mf.id
WHERE m.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
  AND p.manufacturer_id = ?
  AND m.product_id = ?
  AND m.type = ?
ORDER BY m.created_at DESC
```
**Otimização**: `CREATE INDEX idx_movements_report ON inventory_movements (created_at, product_id, type) INCLUDE (quantity, notes);`

### Query 3: Cálculo de estoque atual (PHP function recalculateStock)
```sql
SELECT type, quantity, created_at
FROM inventory_movements
WHERE product_id = ?
ORDER BY created_at ASC
```
**Otimização**: `CREATE INDEX idx_movements_stock_calc ON inventory_movements (product_id, created_at) INCLUDE (type, quantity);` + **Trigger elimina esta query completamente**

---

## 8. Backup/Recovery Strategy

```bash
# Backup lógico diário (estrutura + dados)
mysqldump --single-transaction --routines --triggers \
  -u netstore_estoque -p estoque > backup_$(date +%F_%H-%M).sql

# Backup físico (Percona XtraBackup) para RTO/RPO menores
# Point-in-time recovery: binary logs habilitados
```

---

*Documento gerado em $(date). Revisar antes de aplicar em produção.*