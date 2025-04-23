<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

try {
    $arquivo = null;
    $nomeArquivo = null;

    // Verificar se é um upload de arquivo ou uma restauração direta
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo_backup'])) {
        // Caso de upload de arquivo
        $arquivo = $_FILES['arquivo_backup']['tmp_name'];
        $nomeArquivo = $_FILES['arquivo_backup']['name'];
    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['arquivo'])) {
        // Caso de restauração direta de um arquivo existente
        if (!isset($_GET['arquivo']) || empty($_GET['arquivo'])) {
            $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não especificado');
            header('Location: /estoque/pages/backup/index.php');
            exit;
        }

        $nomeArquivo = basename($_GET['arquivo']);
        $arquivo = '../../backups/' . $nomeArquivo;
        
        if (!file_exists($arquivo)) {
            $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado');
            header('Location: /estoque/pages/backup/index.php');
            exit;
        }
    } else {
        throw new Exception('Requisição inválida');
    }

    $tipo = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

    if ($tipo != 'sql') {
        throw new Exception('Formato de arquivo inválido. Use apenas arquivos .sql');
    }

    // Desativar verificação de chaves estrangeiras
    $conn->exec('SET FOREIGN_KEY_CHECKS=0');

    // Array com as tabelas na ordem correta de exclusão (respeitando as dependências)
    $tabelas = [
        'inventory_movements',  // Primeiro as tabelas dependentes
        'products',
        'manufacturers',
        'usuarios'             // Adicionado usuarios à lista
    ];

    // Dropar todas as tabelas existentes
    foreach ($tabelas as $tabela) {
        try {
            $conn->exec("DROP TABLE IF EXISTS `$tabela`");
        } catch (PDOException $e) {
            // Ignora erros se a tabela não existir
            continue;
        }
    }

    // Ler o arquivo SQL e executar os comandos
    $sql = file_get_contents($arquivo);
    
    // Dividir o arquivo em comandos individuais
    $comandos = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        )
    );

    // Executar cada comando separadamente
    foreach ($comandos as $comando) {
        if (!empty($comando)) {
            try {
                $conn->exec($comando);
            } catch (PDOException $e) {
                // Se houver erro em algum comando, registra e continua
                error_log("Erro ao executar comando SQL: " . $e->getMessage());
                error_log("Comando: " . $comando);
                continue;
            }
        }
    }

    // Reativar verificação de chaves estrangeiras
    $conn->exec('SET FOREIGN_KEY_CHECKS=1');

    $_SESSION['mensagem'] = mensagem('success', 'Backup restaurado com sucesso!');
    header('Location: /estoque/pages/backup/index.php');
    exit;

} catch(Exception $e) {
    // Garantir que as chaves estrangeiras sejam reativadas mesmo em caso de erro
    $conn->exec('SET FOREIGN_KEY_CHECKS=1');
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao restaurar o backup: ' . $e->getMessage());
    header('Location: /estoque/pages/backup/index.php');
    exit;
}
?>