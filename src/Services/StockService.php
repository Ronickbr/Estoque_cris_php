<?php

namespace Estoque\Services;

use PDO;
use Exception;

class StockService
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Identifica produtos onde products.stock difere de SUM(entradas) - SUM(saidas)
     */
    public function getDiscrepancies()
    {
        $sql = "
            SELECT p.id, p.name, p.stock as stock_coluna,
                   COALESCE(SUM(CASE WHEN im.type = 'entrada' THEN im.quantity ELSE 0 END), 0) -
                   COALESCE(SUM(CASE WHEN im.type = 'saida' THEN im.quantity ELSE 0 END), 0) as stock_movimentacoes
            FROM products p
            LEFT JOIN inventory_movements im ON p.id = im.product_id
            GROUP BY p.id, p.name, p.stock
            HAVING stock_coluna != stock_movimentacoes
        ";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reconcilia produtos divergentes ajustando as movimentações para garantir paridade absoluta.
     * Considera products.stock como a quantidade real pretendida e cria movimentação de ajuste.
     */
    public function reconcileAll($usuarioId = '1')
    {
        $discrepancies = $this->getDiscrepancies();
        if (empty($discrepancies)) {
            return 0;
        }

        $stmtInsert = $this->conn->prepare("
            INSERT INTO inventory_movements 
            (id, product_id, type, quantity, notes, created_at, created_by)
            VALUES 
            (UUID(), ?, ?, ?, ?, NOW(), ?)
        ");

        $reconciledCount = 0;
        foreach ($discrepancies as $item) {
            $diff = (int)$item['stock_coluna'] - (int)$item['stock_movimentacoes'];
            if ($diff === 0) continue;

            $type = $diff > 0 ? 'entrada' : 'saida';
            $qty = abs($diff);
            $note = 'Ajuste de inventário (Reconciliação automática)';

            $stmtInsert->execute([
                $item['id'],
                $type,
                $qty,
                $note,
                $usuarioId
            ]);
            $reconciledCount++;
        }

        return $reconciledCount;
    }

    /**
     * Processa movimentações em lote de forma atômica com trava e validação rigorosa.
     */
    public function processBatchMovements(array $movements, $usuarioId = '1')
    {
        if (empty($movements)) {
            throw new Exception('Nenhuma movimentação informada.');
        }

        $stmtLock = $this->conn->prepare("SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE");
        $stmtInsert = $this->conn->prepare("
            INSERT INTO inventory_movements 
            (id, product_id, type, quantity, notes, created_at, created_by) 
            VALUES 
            (UUID(), ?, ?, ?, ?, ?, ?)
        ");
        $stmtUpdate = $this->conn->prepare("
            UPDATE products 
            SET stock = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        // Agrupar e validar impacto por produto
        $impactByProduct = [];
        foreach ($movements as $m) {
            $pId = $m['product_id'];
            $type = $m['type'];
            $qty = (int)$m['quantity'];

            if (!in_array($type, ['entrada', 'saida'])) {
                throw new Exception("Tipo de movimentação inválido: {$type}");
            }
            if ($qty <= 0) {
                throw new Exception("Quantidade deve ser maior que zero.");
            }

            if (!isset($impactByProduct[$pId])) {
                $impactByProduct[$pId] = 0;
            }
            $impactByProduct[$pId] += ($type === 'entrada' ? $qty : -$qty);
        }

        // Validar estoque disponível
        $currentStocks = [];
        foreach ($impactByProduct as $pId => $netChange) {
            $stmtLock->execute([$pId]);
            $prod = $stmtLock->fetch(PDO::FETCH_ASSOC);
            if (!$prod) {
                throw new Exception("Produto não encontrado: {$pId}");
            }

            $current = (int)$prod['stock'];
            $finalStock = $current + $netChange;
            if ($finalStock < 0) {
                throw new Exception("Estoque insuficiente para o produto '{$prod['name']}'. Estoque atual: {$current}, retirada necessária: " . abs($netChange));
            }
            $currentStocks[$pId] = $finalStock;
        }

        // Inserir movimentações
        foreach ($movements as $m) {
            $stmtInsert->execute([
                $m['product_id'],
                $m['type'],
                (int)$m['quantity'],
                !empty($m['notes']) ? $m['notes'] : null,
                $m['created_at'],
                $usuarioId
            ]);
        }

        // Atualizar estoque dos produtos
        foreach ($currentStocks as $pId => $finalStock) {
            $stmtUpdate->execute([$finalStock, $pId]);
        }

        return count($movements);
    }
}
