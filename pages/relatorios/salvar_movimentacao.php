<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /estoque/pages/relatorios/index.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Obter arrays dos campos do formulário (removendo manufacturers que é apenas para UI)
    $products = $_POST['products'] ?? [];
    $types = $_POST['types'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $notes = $_POST['notes'] ?? [];
    $movement_dates = $_POST['movement_dates'] ?? [];

    // Remover o array de fabricantes do POST pois não será usado
    unset($_POST['manufacturers']);

    // Validar se há pelo menos uma movimentação
    if (empty($products)) {
        throw new Exception('É necessário informar pelo menos um produto.');
    }

    // Validar se os arrays têm o mesmo tamanho
    if (count($products) !== count($types) || 
        count($products) !== count($quantities) || 
        count($products) !== count($movement_dates)) {
        throw new Exception('Dados inválidos.');
    }

    // Preparar statement para inserção
    $stmtInsert = $conn->prepare("
        INSERT INTO inventory_movements 
        (id, product_id, type, quantity, notes, created_at, created_by) 
        VALUES 
        (UUID(), ?, ?, ?, ?, ?, ?)
    ");

    // Preparar statement para atualização do estoque
    $stmtUpdateStock = $conn->prepare("
        UPDATE products 
        SET stock = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");

    // Data atual para validação
    $currentDate = date('Y-m-d');

    // Função para recalcular o estoque de um produto
    function recalculateStock($conn, $product_id, $new_movements = []) {
        // Buscar todas as movimentações existentes do produto
        $stmtMovements = $conn->prepare("
            SELECT type, quantity, created_at
            FROM inventory_movements
            WHERE product_id = ?
            ORDER BY created_at ASC
        ");
        $stmtMovements->execute([$product_id]);
        $existing_movements = $stmtMovements->fetchAll(PDO::FETCH_ASSOC);

        // Combinar movimentações existentes com as novas
        $all_movements = array_merge($existing_movements, $new_movements);

        // Ordenar todas as movimentações por data
        usort($all_movements, function($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        // Recalcular o estoque
        $stock = 0; // Supondo que o estoque inicial seja 0 (ajuste se houver um valor inicial)
        foreach ($all_movements as $mov) {
            if ($mov['type'] === 'entrada') {
                $stock += (int)$mov['quantity'];
            } else {
                $stock -= (int)$mov['quantity'];
            }

            // Validar se o estoque fica negativo
            if ($stock < 0) {
                throw new Exception('Estoque insuficiente para o produto em ' . $mov['created_at'] . '. Estoque ficaria negativo: ' . $stock);
            }
        }

        return $stock;
    }

    // Array para armazenar as movimentações temporárias por produto
    $movements_by_product = [];

    // Processar cada movimentação para validação inicial
    for ($i = 0; $i < count($products); $i++) {
        $product_id = $products[$i];
        $type = $types[$i];
        $quantity = (int)$quantities[$i];
        $note = $notes[$i] ?? null;
        $movement_date = $movement_dates[$i];

        // Validar quantidade
        if ($quantity <= 0) {
            throw new Exception('A quantidade deve ser maior que zero.');
        }

        // Validar data da movimentação (se preenchida)
        if (!empty($movement_date)) {
            if ($movement_date > $currentDate) {
                throw new Exception('A data da movimentação não pode ser futura.');
            }
        } else {
            $movement_date = date('Y-m-d H:i:s'); // Usar data atual se não preenchida
        }

        // Agrupar movimentações por produto para recalcular o estoque
        if (!isset($movements_by_product[$product_id])) {
            $movements_by_product[$product_id] = [];
        }

        $movements_by_product[$product_id][] = [
            'type' => $type,
            'quantity' => $quantity,
            'created_at' => $movement_date,
            'note' => $note
        ];
    }

    // Validar e recalcular o estoque para cada produto
    $updated_stocks = [];
    foreach ($movements_by_product as $product_id => $new_movements) {
        $final_stock = recalculateStock($conn, $product_id, $new_movements);
        $updated_stocks[$product_id] = $final_stock;
    }

    // Se todas as validações passaram, inserir as movimentações
    for ($i = 0; $i < count($products); $i++) {
        $product_id = $products[$i];
        $type = $types[$i];
        $quantity = (int)$quantities[$i];
        $note = $notes[$i] ?? null;
        $movement_date = $movement_dates[$i];

        // Definir a data a ser usada
        $dateToUse = empty($movement_date) ? 'NOW()' : '?';
        if ($stmtInsert->queryString !== "
            INSERT INTO inventory_movements 
            (id, product_id, type, quantity, notes, created_at, created_by) 
            VALUES 
            (UUID(), ?, ?, ?, ?, $dateToUse, ?)
        ") {
            $stmtInsert = $conn->prepare("
                INSERT INTO inventory_movements 
                (id, product_id, type, quantity, notes, created_at, created_by) 
                VALUES 
                (UUID(), ?, ?, ?, ?, $dateToUse, ?)
            ");
        }

        // Inserir movimentação
        if (empty($movement_date)) {
            $stmtInsert->execute([
                $product_id,
                $type,
                $quantity,
                $note,
                $_SESSION['usuario_id']
            ]);
        } else {
            $stmtInsert->execute([
                $product_id,
                $type,
                $quantity,
                $note,
                $movement_date,
                $_SESSION['usuario_id']
            ]);
        }
    }

    // Atualizar o estoque de cada produto
    foreach ($updated_stocks as $product_id => $final_stock) {
        $stmtUpdateStock->execute([$final_stock, $product_id]);
    }

    $conn->commit();
    $_SESSION['mensagem'] = mensagem('success', 'Movimentações registradas com sucesso!');
    header('Location: /estoque/pages/relatorios/index.php');
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao registrar movimentações: ' . $e->getMessage());
}

header('Location: /estoque/pages/relatorios/index.php');
exit;
?>