<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado
verificarLogin();

$redirectUrl = url('/pages/fabricantes/index.php');
exigirCsrf($redirectUrl);

// Verificar se o ID foi fornecido via POST
$id = $_POST['id'] ?? null;
if (empty($id)) {
    $_SESSION['mensagem'] = mensagem('warning', 'ID de fabricante não fornecido.');
    redirect('/pages/fabricantes/index.php');
}

$id = limparEntrada($id);

// Excluir fabricante
try {
    // Verificar se fabricante existe
    $stmtCheck = $conn->prepare("SELECT id, name, logo_url FROM manufacturers WHERE id = ?");
    $stmtCheck->execute([$id]);
    $fabricante = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$fabricante) {
        $_SESSION['mensagem'] = mensagem('warning', 'Fabricante não encontrado.');
        redirect('/pages/fabricantes/index.php');
    }
    
    // Verificar se existem produtos associados
    $stmtProdutos = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE manufacturer_id = ?");
    $stmtProdutos->execute([$id]);
    $totalProdutos = $stmtProdutos->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($totalProdutos > 0) {
        $_SESSION['mensagem'] = mensagem('warning', 'Não é possível excluir um fabricante com produtos associados.');
        redirect('/pages/fabricantes/index.php');
    }
    
    // Remover logo se existir
    if ($fabricante['logo_url']) {
        $relPath = preg_replace('#^(\.\./|/)+#', '', $fabricante['logo_url']);
        $diskPath = dirname(__DIR__, 2) . '/' . $relPath;
        if (file_exists($diskPath) && is_file($diskPath)) {
            @unlink($diskPath);
        }
    }
    
    // Excluir fabricante
    $stmt = $conn->prepare("DELETE FROM manufacturers WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['mensagem'] = mensagem('success', 'Fabricante "' . htmlspecialchars($fabricante['name']) . '" excluído com sucesso!');
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir fabricante: ' . $e->getMessage());
}

// Redirecionar
redirect('/pages/fabricantes/index.php');
