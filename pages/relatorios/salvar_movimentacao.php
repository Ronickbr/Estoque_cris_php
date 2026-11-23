<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Carregar serviço de estoque
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../src/Services/StockService.php';
}

use Estoque\Services\StockService;

verificarLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/relatorios/index.php');
}

exigirCsrf('/pages/relatorios/index.php');

try {
    $conn->beginTransaction();

    // Obter arrays dos campos do formulário
    $products = $_POST['products'] ?? [];
    $types = $_POST['types'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $notes = $_POST['notes'] ?? [];
    $movement_dates = $_POST['movement_dates'] ?? [];

    unset($_POST['manufacturers']);

    // Validar se há pelo menos uma movimentação
    if (empty($products) || !is_array($products)) {
        throw new Exception('É necessário informar pelo menos um produto.');
    }

    // Validar se os arrays têm o mesmo tamanho
    if (count($products) !== count($types) || 
        count($products) !== count($quantities) || 
        count($products) !== count($movement_dates)) {
        throw new Exception('Dados inválidos ou incompletos.');
    }

    $currentDate = date('Y-m-d');
    $movementsList = [];

    for ($i = 0; $i < count($products); $i++) {
        $pId = limparEntrada($products[$i]);
        $type = $types[$i];
        $qty = (int)$quantities[$i];
        $note = !empty($notes[$i]) ? limparEntrada($notes[$i]) : null;
        $mDate = $movement_dates[$i];

        if (empty($pId)) {
            throw new Exception('Produto não selecionado.');
        }

        if (!empty($mDate)) {
            if ($mDate > $currentDate) {
                throw new Exception('A data da movimentação não pode ser futura.');
            }
            $datetimeToUse = (strlen($mDate) === 10) ? $mDate . ' ' . date('H:i:s') : $mDate;
        } else {
            $datetimeToUse = date('Y-m-d H:i:s');
        }

        $movementsList[] = [
            'product_id' => $pId,
            'type' => $type,
            'quantity' => $qty,
            'notes' => $note,
            'created_at' => $datetimeToUse
        ];
    }

    $stockService = new StockService($conn);
    $usuarioId = $_SESSION['usuario_id'] ?? '1';
    $stockService->processBatchMovements($movementsList, $usuarioId);

    $conn->commit();
    $_SESSION['mensagem'] = mensagem('success', 'Movimentações registradas e estoque atualizado com sucesso!');

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao registrar movimentações: ' . $e->getMessage());
}

redirect('/pages/relatorios/index.php');
