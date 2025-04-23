<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado
verificarLogin();

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = mensagem('warning', 'ID de fabricante não fornecido.');
    header('Location: /estoque/pages/fabricantes/index.php');
    exit;
}

$id = $_GET['id'];

// Excluir fabricante
try {
    // Verificar se fabricante existe
    $stmtCheck = $conn->prepare("SELECT id, name, logo_url FROM manufacturers WHERE id = ?");
    $stmtCheck->execute([$id]);
    $fabricante = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$fabricante) {
        $_SESSION['mensagem'] = mensagem('warning', 'Fabricante não encontrado.');
        header('Location: /estoque/pages/fabricantes/index.php');
        exit;
    }
    
    // Verificar se existem produtos associados
    $stmtProdutos = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE manufacturer_id = ?");
    $stmtProdutos->execute([$id]);
    $totalProdutos = $stmtProdutos->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalProdutos > 0) {
        $_SESSION['mensagem'] = mensagem('warning', 'Não é possível excluir um fabricante com produtos associados.');
        header('Location: /estoque/pages/fabricantes/index.php');
        exit;
    }
    
    // Remover logo se existir
    if ($fabricante['logo_url'] && file_exists('../../' . ltrim($fabricante['logo_url'], '/'))) {
        unlink('../../' . ltrim($fabricante['logo_url'], '/'));
    }
    
    // Excluir fabricante
    $stmt = $conn->prepare("DELETE FROM manufacturers WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['mensagem'] = mensagem('success', 'Fabricante "' . htmlspecialchars($fabricante['name']) . '" excluído com sucesso!');
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir fabricante: ' . $e->getMessage());
}

// Redirecionar
header('Location: /estoque/pages/fabricantes/index.php');
exit;
?>