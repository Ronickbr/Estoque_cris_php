<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado e é admin
verificarLogin();

// Verificar se o arquivo foi especificado
if (!isset($_GET['arquivo']) || empty($_GET['arquivo'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não especificado');
    header('Location: /estoque/pages/backup/index.php');
    exit;
}

// Verificar se o nome do arquivo é seguro (sem "../" ou caminhos absolutos)
$arquivo = basename($_GET['arquivo']);
$caminhoArquivo = '../../backups/' . $arquivo;

// Verificar se o arquivo existe
if (!file_exists($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado');
    header('Location: /estoque/pages/backup/index.php');
    exit;
}

// Verificar tipo de arquivo
$extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));

if (!in_array($extensao, ['sql', 'json'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Tipo de arquivo não suportado. Use apenas arquivos .sql ou .json');
    header('Location: /estoque/pages/backup/index.php');
    exit;
}

try {
    if ($extensao == 'sql') {
        // Restaurar a partir de arquivo SQL
        
        // Ler o conteúdo do arquivo SQL
        $conteudoSQL = file_get_contents($caminhoArquivo);
        
        // Desativar verificação de chaves estrangeiras
        $conn->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Dividir o arquivo em consultas individuais
        $consultas = explode(';', $conteudoSQL);
        
        // Executar cada consulta
        foreach ($consultas as $consulta) {
            $consulta = trim($consulta);
            if (!empty($consulta)) {
                $conn->exec($consulta);
            }
        }
        
        // Ativar verificação de chaves estrangeiras
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        
        $_SESSION['mensagem'] = mensagem('success', 'Banco de dados restaurado com sucesso a partir do arquivo SQL.');
        
    } else if ($extensao == 'json') {
        // Restaurar a partir de arquivo JSON
        
        // Ler o conteúdo do arquivo JSON
        $conteudoJSON = file_get_contents($caminhoArquivo);
        $dados = json_decode($conteudoJSON, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro ao decodificar o arquivo JSON: ' . json_last_error_msg());
        }
        
        // Verificar se o arquivo JSON tem o formato esperado
        if (!isset($dados['fabricantes']) || !isset($dados['produtos']) || !isset($dados['movimentacoes'])) {
            throw new Exception('O arquivo JSON não possui o formato esperado para restauração.');
        }
        
        // Iniciar transação
        $conn->beginTransaction();
        
        // Desativar verificação de chaves estrangeiras
        $conn->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Limpar tabelas existentes
        $conn->exec("TRUNCATE TABLE movimentacoes_estoque");
        $conn->exec("TRUNCATE TABLE produtos");
        $conn->exec("TRUNCATE TABLE fabricantes");
        
        // Restaurar fabricantes
        $stmtFabricantes = $conn->prepare("INSERT INTO fabricantes (id, nome) VALUES (?, ?)");
        
        foreach ($dados['fabricantes'] as $fabricante) {
            // Converter do formato antigo para o novo
            $id = $fabricante['id'] ?? $fabricante['id'];
            $nome = $fabricante['name'] ?? $fabricante['nome']; // Compatibilidade com versão antiga
            
            $stmtFabricantes->execute([$id, $nome]);
        }
        
        // Restaurar produtos
        $stmtProdutos = $conn->prepare("INSERT INTO produtos (id, nome, fabricante_id, estoque) VALUES (?, ?, ?, ?)");
        
        foreach ($dados['produtos'] as $produto) {
            // Converter do formato antigo para o novo
            $id = $produto['id'] ?? $produto['id'];
            $nome = $produto['name'] ?? $produto['nome']; // Compatibilidade com versão antiga
            $fabricanteId = $produto['manufacturerId'] ?? $produto['fabricante_id']; // Compatibilidade com versão antiga
            $estoque = $produto['stock'] ?? $produto['estoque']; // Compatibilidade com versão antiga
            
            $stmtProdutos->execute([$id, $nome, $fabricanteId, $estoque]);
        }
        
        // Restaurar movimentações
        $stmtMovimentacoes = $conn->prepare("INSERT INTO movimentacoes_estoque (id, produto_id, tipo, quantidade, data, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($dados['movimentacoes'] as $movimentacao) {
            // Converter do formato antigo para o novo
            $id = $movimentacao['id'] ?? $movimentacao['id'];
            $produtoId = $movimentacao['productId'] ?? $movimentacao['produto_id']; // Compatibilidade com versão antiga
            $tipo = $movimentacao['type'] ?? $movimentacao['tipo']; // Compatibilidade com versão antiga
            $quantidade = $movimentacao['quantity'] ?? $movimentacao['quantidade']; // Compatibilidade com versão antiga
            $data = $movimentacao['date'] ?? $movimentacao['data']; // Compatibilidade com versão antiga
            $observacoes = $movimentacao['notes'] ?? $movimentacao['observacoes'] ?? null; // Compatibilidade com versão antiga
            
            // Garantir formato de data MySQL
            if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $data)) {
                $data = date('Y-m-d H:i:s', strtotime($data));
            }
            
            $stmtMovimentacoes->execute([$id, $produtoId, $tipo, $quantidade, $data, $observacoes]);
        }
        
        // Ativar verificação de chaves estrangeiras
        $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        
        // Commit da transação
        $conn->commit();
        
        $_SESSION['mensagem'] = mensagem('success', 'Banco de dados restaurado com sucesso a partir do arquivo JSON.');
    }
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao restaurar o banco de dados: ' . $e->getMessage());
}

// Redirecionar de volta
header('Location: /estoque/pages/backup/index.php');
exit;
?>