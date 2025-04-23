<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'ID do produto não fornecido');
    header('Location: /estoque/pages/produtos/index.php');
    exit;
}

$id = limparEntrada($_GET['id']);

try {
    // Iniciar transação
    $conn->beginTransaction();
    
    // Primeiro, excluir os movimentos do estoque relacionados
    $stmtMovimentos = $conn->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
    $stmtMovimentos->execute([$id]);
    
    // Depois, excluir o produto
    $stmtProduto = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmtProduto->execute([$id]);
    
    if ($stmtProduto->rowCount() > 0) {
        $conn->commit();
        $_SESSION['mensagem'] = mensagem('success', 'Produto excluído com sucesso');
    } else {
        $conn->rollBack();
        $_SESSION['mensagem'] = mensagem('danger', 'Não foi possível excluir o produto');
    }

} catch(PDOException $e) {
    $conn->rollBack();
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir produto: ' . $e->getMessage());
}

header('Location: /estoque/pages/produtos/index.php');
exit;