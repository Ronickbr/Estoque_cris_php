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
$name = limparEntrada($_POST['name'] ?? '');

if (empty($name)) {
    $_SESSION['mensagem'] = mensagem('danger', 'O nome do fabricante é obrigatório.');
    redirect('/pages/fabricantes/index.php');
}

// Processar o upload do logo se houver
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
        }
    }
}

// Salvar no banco
try {
    // Verificar se já existe fabricante com esse nome
    $stmtCheck = $conn->prepare("SELECT id FROM manufacturers WHERE name = ?");
    $stmtCheck->execute([$name]);
    
    if ($stmtCheck->fetch()) {
        $_SESSION['mensagem'] = mensagem('warning', 'Já existe um fabricante com este nome.');
        redirect('/pages/fabricantes/index.php');
    }
    
    // Inserir novo fabricante
    $stmt = $conn->prepare("INSERT INTO manufacturers (id, name, logo_url, created_at) VALUES (UUID(), ?, ?, NOW())");
    $stmt->execute([$name, $logo_url]);
    
    $_SESSION['mensagem'] = mensagem('success', 'Fabricante cadastrado com sucesso!');
    redirect('/pages/fabricantes/index.php');
    
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao cadastrar fabricante: ' . $e->getMessage());
    redirect('/pages/fabricantes/index.php');
}
