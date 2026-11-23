<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verifica se usuário está logado
verificarLogin();
exigirCsrf('/pages/produtos/index.php');

// Recebe e limpa os dados do formulário
$id = limparEntrada($_POST['id'] ?? '');
$name = limparEntrada($_POST['name'] ?? '');
$manufacturer_id = limparEntrada($_POST['manufacturer_id'] ?? '');
$stock = limparEntrada($_POST['stock'] ?? '');
$min_stock = limparEntrada($_POST['min_stock'] ?? '');

// Validações básicas
if (empty($id) || empty($name) || empty($manufacturer_id) || !is_numeric($stock) || !is_numeric($min_stock) || $stock < 0 || $min_stock < 0) {
    $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos obrigatórios devem ser preenchidos corretamente com valores válidos.');
    redirect('/pages/produtos/index.php');
}

try {
    // Inicia a transação
    $conn->beginTransaction();

    // Busca o produto atual para comparar o estoque
    $stmt = $conn->prepare("SELECT stock, image_url FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $produto_atual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto_atual) {
        throw new Exception('Produto não encontrado.');
    }

    $image_url = $produto_atual['image_url'];

    // Processa upload da imagem se existir
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__, 2) . '/uploads/produtos/';
        
        // Cria o diretório se não existir
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Gera um nome único para o arquivo
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido. Apenas jpg, jpeg, png, gif e webp são aceitos.');
        }

        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Move o arquivo para o diretório de uploads
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            throw new Exception('Erro ao fazer upload da imagem.');
        }

        $image_url = '/uploads/produtos/' . $new_filename;

        // Remove a imagem antiga se existir
        if (!empty($produto_atual['image_url'])) {
            $relPath = preg_replace('#^(\.\./|/)+#', '', $produto_atual['image_url']);
            $oldDiskPath = dirname(__DIR__, 2) . '/' . $relPath;
            if (file_exists($oldDiskPath) && is_file($oldDiskPath)) {
                unlink($oldDiskPath);
            }
        }
    }

    // Prepara a query de atualização
    $sql = "UPDATE products SET name = ?, manufacturer_id = ?, stock = ?, min_stock = ?, image_url = ?, updated_at = NOW() WHERE id = ?";
    $params = [$name, $manufacturer_id, $stock, $min_stock, $image_url, $id];

    // Executa a atualização
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    // Registra a movimentação de estoque se houver alteração
    $diferenca_estoque = $stock - $produto_atual['stock'];
    if ($diferenca_estoque != 0) {
        $tipo = $diferenca_estoque > 0 ? 'entrada' : 'saida';
        $quantidade = abs($diferenca_estoque);
        $usuario_id = $_SESSION['usuario_id'] ?? '1';
        
        $sql = "INSERT INTO inventory_movements (id, product_id, type, quantity, notes, created_at, created_by) 
                VALUES (UUID(), ?, ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id, $tipo, $quantidade, 'Atualização de estoque', $usuario_id]);
    }

    // Commit na transação
    $conn->commit();
    $_SESSION['mensagem'] = mensagem('success', 'Produto atualizado com sucesso!');

} catch (Exception $e) {
    // Se houver erro, faz rollback
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Se houver nova imagem já enviada, tenta removê-la
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao atualizar produto: ' . $e->getMessage());
}

redirect('/pages/produtos/index.php');
