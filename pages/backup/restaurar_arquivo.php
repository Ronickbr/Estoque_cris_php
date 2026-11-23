<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado e é admin
verificarLogin();
verificarAdmin();

$redirectUrl = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/pages/backup/index.php';
exigirCsrf($redirectUrl);

$arquivo = $_POST['arquivo'] ?? null;
if (empty($arquivo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não especificado.');
    header("Location: $redirectUrl");
    exit;
}

$arquivo = basename($arquivo);
$caminhoArquivo = dirname(__DIR__, 2) . '/backups/' . $arquivo;

if (!file_exists($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado.');
    header("Location: $redirectUrl");
    exit;
}

$extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));

if (!in_array($extensao, ['sql', 'json'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Tipo de arquivo não suportado. Use apenas arquivos .sql ou .json');
    header("Location: $redirectUrl");
    exit;
}

try {
    if ($extensao == 'sql') {
        $conteudoSQL = file_get_contents($caminhoArquivo);
        $conn->exec("SET FOREIGN_KEY_CHECKS=0");
        $consultas = explode(';', $conteudoSQL);
        
        foreach ($consultas as $consulta) {
            $consulta = trim($consulta);
            if (!empty($consulta)) {
                $conn->exec($consulta);
            }
        }
        
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        $_SESSION['mensagem'] = mensagem('success', 'Banco de dados restaurado com sucesso a partir do arquivo SQL.');
        
    } else if ($extensao == 'json') {
        $conteudoJSON = file_get_contents($caminhoArquivo);
        $dados = json_decode($conteudoJSON, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar o arquivo JSON: ' . json_last_error_msg());
        }
        
        if (!isset($dados['fabricantes']) || !isset($dados['produtos']) || !isset($dados['movimentacoes'])) {
            throw new Exception('O arquivo JSON não possui o formato esperado para restauração.');
        }
        
        $conn->beginTransaction();
        $conn->exec("SET FOREIGN_KEY_CHECKS=0");
        $conn->exec("TRUNCATE TABLE inventory_movements");
        $conn->exec("TRUNCATE TABLE products");
        $conn->exec("TRUNCATE TABLE manufacturers");
        
        $stmtFabricantes = $conn->prepare("INSERT INTO manufacturers (id, name) VALUES (?, ?)");
        foreach ($dados['fabricantes'] as $fabricante) {
            $id = $fabricante['id'];
            $nome = $fabricante['name'] ?? $fabricante['nome'];
            $stmtFabricantes->execute([$id, $nome]);
        }
        
        $stmtProdutos = $conn->prepare("INSERT INTO products (id, name, manufacturer_id, stock) VALUES (?, ?, ?, ?)");
        foreach ($dados['produtos'] as $produto) {
            $id = $produto['id'];
            $nome = $produto['name'] ?? $produto['nome'];
            $fabricanteId = $produto['manufacturer_id'] ?? $produto['manufacturerId'] ?? $produto['fabricante_id'];
            $estoque = $produto['stock'] ?? $produto['estoque'];
            $stmtProdutos->execute([$id, $nome, $fabricanteId, $estoque]);
        }
        
        $stmtMovimentacoes = $conn->prepare("INSERT INTO inventory_movements (id, product_id, type, quantity, created_at, notes) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($dados['movimentacoes'] as $movimentacao) {
            $id = $movimentacao['id'];
            $produtoId = $movimentacao['product_id'] ?? $movimentacao['productId'] ?? $movimentacao['produto_id'];
            $tipo = $movimentacao['type'] ?? $movimentacao['tipo'];
            $quantidade = $movimentacao['quantity'] ?? $movimentacao['quantidade'];
            $data = $movimentacao['created_at'] ?? $movimentacao['date'] ?? $movimentacao['data'];
            $observacoes = $movimentacao['notes'] ?? $movimentacao['observacoes'] ?? null;
            
            if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $data)) {
                $data = date('Y-m-d H:i:s', strtotime($data));
            }
            
            $stmtMovimentacoes->execute([$id, $produtoId, $tipo, $quantidade, $data, $observacoes]);
        }
        
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        $conn->commit();
        $_SESSION['mensagem'] = mensagem('success', 'Banco de dados restaurado com sucesso a partir do arquivo JSON.');
    }
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao restaurar o banco de dados: ' . $e->getMessage());
}

header("Location: $redirectUrl");
exit;
