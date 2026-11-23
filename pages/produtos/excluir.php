<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

$redirectUrl = url('/pages/produtos/index.php');
exigirCsrf($redirectUrl);

$id = $_POST['id'] ?? null;
if (empty($id)) {
    $_SESSION['mensagem'] = mensagem('danger', 'ID do produto não fornecido.');
    redirect('/pages/produtos/index.php');
}

$id = limparEntrada($id);

try {
    // Iniciar transação
    $conn->beginTransaction();
    
    // Buscar imagem do produto para remover arquivo físico se existir
    $stmtImg = $conn->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmtImg->execute([$id]);
    $imgUrl = $stmtImg->fetchColumn();

    // Primeiro, excluir os movimentos do estoque relacionados
    $stmtMovimentos = $conn->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
    $stmtMovimentos->execute([$id]);
    
    // Depois, excluir o produto
    $stmtProduto = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmtProduto->execute([$id]);
    
    if ($stmtProduto->rowCount() > 0) {
        $conn->commit();
        if ($imgUrl) {
            $relPath = preg_replace('#^(\.\./|/)+#', '', $imgUrl);
            $diskPath = dirname(__DIR__, 2) . '/' . $relPath;
            if (file_exists($diskPath) && is_file($diskPath)) {
                unlink($diskPath);
            }
        }
        $_SESSION['mensagem'] = mensagem('success', 'Produto excluído com sucesso.');
    } else {
        $conn->rollBack();
        $_SESSION['mensagem'] = mensagem('danger', 'Não foi possível excluir o produto.');
    }

} catch(PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir produto: ' . $e->getMessage());
}

redirect('/pages/produtos/index.php');
