<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();
verificarAdmin();

$redirectUrl = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/pages/backup/index.php';
exigirCsrf($redirectUrl);

try {
    $arquivo = null;
    $nomeArquivo = null;

    // Verificar se é um upload de arquivo ou uma restauração direta
    if (isset($_FILES['arquivo_backup']) && !empty($_FILES['arquivo_backup']['tmp_name'])) {
        $arquivo = $_FILES['arquivo_backup']['tmp_name'];
        $nomeArquivo = $_FILES['arquivo_backup']['name'];
    } elseif (!empty($_POST['arquivo'])) {
        $nomeArquivo = basename($_POST['arquivo']);
        $arquivo = dirname(__DIR__, 2) . '/backups/' . $nomeArquivo;
        
        if (!file_exists($arquivo)) {
            $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado.');
            header("Location: $redirectUrl");
            exit;
        }
    } else {
        throw new Exception('Requisição inválida: arquivo não fornecido.');
    }

    $tipo = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));

    if ($tipo !== 'sql') {
        throw new Exception('Formato de arquivo inválido. Use apenas arquivos .sql');
    }

    // Desativar verificação de chaves estrangeiras
    $conn->exec('SET FOREIGN_KEY_CHECKS=0');

    // Array com as tabelas na ordem correta de exclusão
    $tabelas = [
        'inventory_movements',
        'products',
        'manufacturers',
        'usuarios'
    ];

    // Dropar todas as tabelas existentes
    foreach ($tabelas as $tabela) {
        try {
            $conn->exec("DROP TABLE IF EXISTS `$tabela`");
        } catch (PDOException $e) {
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
                error_log("Erro ao executar comando SQL: " . $e->getMessage());
                error_log("Comando: " . $comando);
                continue;
            }
        }
    }

    // Reativar verificação de chaves estrangeiras
    $conn->exec('SET FOREIGN_KEY_CHECKS=1');

    $_SESSION['mensagem'] = mensagem('success', 'Backup restaurado com sucesso!');
    header("Location: $redirectUrl");
    exit;

} catch(Exception $e) {
    $conn->exec('SET FOREIGN_KEY_CHECKS=1');
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao restaurar o backup: ' . $e->getMessage());
    header("Location: $redirectUrl");
    exit;
}
