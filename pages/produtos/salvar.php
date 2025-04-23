<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensagem'] = mensagem('danger', 'Método inválido.');
    header('Location: /estoque/pages/produtos/index.php');
    exit;
}

// Recebe e limpa os dados do formulário
$nome = limparEntrada($_POST['name']);
$fabricante_id = limparEntrada($_POST['manufacturer_id']);
$estoque = limparEntrada($_POST['stock']);
$estoque_minimo = limparEntrada($_POST['min_stock']);

// Validações básicas
if (empty($nome) || empty($fabricante_id) || !is_numeric($estoque) || !is_numeric($estoque_minimo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos obrigatórios devem ser preenchidos corretamente.');
    header('Location: /estoque/pages/produtos/index.php');
    exit;
}

try {
    // Inicia a transação
    $conn->beginTransaction();
    
    // Gera um UUID para o produto
    $bytes = random_bytes(16);
    $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
    $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant RFC 4122
    $id = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));

    $image_url = null;

    // Processa upload da imagem se existir
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/produtos/';
        
        // Cria o diretório se não existir
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Gera um nome único para o arquivo
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Verifica o tipo de arquivo
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido. Apenas jpg, jpeg, png e gif são aceitos.');
        }

        // Move o arquivo para o diretório de uploads
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            throw new Exception('Erro ao fazer upload da imagem.');
        }

        $image_url = '../../uploads/produtos/' . $new_filename;
    }

    // Prepara e executa a query para inserir o produto
    $sql = "INSERT INTO products (id, name, manufacturer_id, stock, min_stock, image_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id, $nome, $fabricante_id, $estoque, $estoque_minimo, $image_url]);

    // Registra a movimentação inicial do estoque
    if ($estoque > 0) {
        $product_id = $id;
        $movement_id = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $sql = "INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by) VALUES (?, ?, 'entrada', ?, 'Estoque inicial', NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$movement_id, $product_id, $estoque, $_SESSION['usuario_id']]);
    }

    // Se chegou até aqui, commit na transação
    $conn->commit();

    $_SESSION['mensagem'] = mensagem('success', 'Produto cadastrado com sucesso!');

} catch (Exception $e) {
    // Se houver erro e existir uma transação ativa, faz rollback
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Se houver imagem já enviada, tenta removê-la
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao cadastrar produto: ' . $e->getMessage());
}

// Redireciona de volta para a lista de produtos
header('Location: /estoque/pages/produtos/index.php');
exit;