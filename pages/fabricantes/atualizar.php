<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado
verificarLogin();

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['mensagem'] = mensagem('warning', 'Método de acesso inválido.');
    redirect('/pages/fabricantes/index.php');
}

// Obter e validar dados
$id = $_POST['id'] ?? '';
$name = limparEntrada($_POST['name'] ?? '');

if (empty($id) || empty($name)) {
    $_SESSION['mensagem'] = mensagem('danger', 'ID e nome do fabricante são obrigatórios.');
    redirect('/pages/fabricantes/index.php');
}

// Processar o upload do novo logo se houver
$logo_url = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = dirname(__DIR__, 2) . '/uploads/manufacturers/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (in_array($file_extension, $allowed_types)) {
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            $logo_url = '/uploads/manufacturers/' . $new_filename;
            
            // Remover logo antigo se existir
            $stmtOldLogo = $conn->prepare("SELECT logo_url FROM manufacturers WHERE id = ?");
            $stmtOldLogo->execute([$id]);
            $oldLogo = $stmtOldLogo->fetchColumn();
            
            if ($oldLogo) {
                $relPath = preg_replace('#^(\.\./|/)+#', '', $oldLogo);
                $diskPath = dirname(__DIR__, 2) . '/' . $relPath;
                if (file_exists($diskPath) && is_file($diskPath)) {
                    @unlink($diskPath);
                }
            }
        }
    }
}

// Atualizar no banco
try {
    // Verificar se fabricante existe
    $stmtCheck = $conn->prepare("SELECT id FROM manufacturers WHERE id = ?");
    $stmtCheck->execute([$id]);
    
    if (!$stmtCheck->fetch()) {
        $_SESSION['mensagem'] = mensagem('warning', 'Fabricante não encontrado.');
        redirect('/pages/fabricantes/index.php');
    }
    
    // Verificar se já existe outro fabricante com esse nome
    $stmtNome = $conn->prepare("SELECT id FROM manufacturers WHERE name = ? AND id != ?");
    $stmtNome->execute([$name, $id]);
    
    if ($stmtNome->fetch()) {
        $_SESSION['mensagem'] = mensagem('warning', 'Já existe outro fabricante com este nome.');
        redirect('/pages/fabricantes/index.php');
    }
    
    // Atualizar fabricante
    if ($logo_url !== null) {
        $stmt = $conn->prepare("UPDATE manufacturers SET name = ?, logo_url = ? WHERE id = ?");
        $stmt->execute([$name, $logo_url, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE manufacturers SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }
    
    $_SESSION['mensagem'] = mensagem('success', 'Fabricante atualizado com sucesso!');
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao atualizar fabricante: ' . $e->getMessage());
}

redirect('/pages/fabricantes/index.php');
