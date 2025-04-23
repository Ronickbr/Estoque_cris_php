<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado e é admin
verificarLogin();

// Diretório para armazenar backups
$diretorioBackup = '../../backups/';

// Verificar se o diretório existe
if (!file_exists($diretorioBackup)) {
    $_SESSION['mensagem'] = mensagem('warning', 'Diretório de backups não encontrado');
    header('Location: /estoque/pages/backup/index.php');
    exit;
}

// Listar todos os backups (SQL e JSON)
$arquivos = glob($diretorioBackup . '*.{sql,json}', GLOB_BRACE);

// Manter apenas o backup mais recente de cada tipo
$ultimoSQL = null;
$ultimaDataSQL = 0;
$ultimoJSON = null;
$ultimaDataJSON = 0;

foreach ($arquivos as $arquivo) {
    $data = filemtime($arquivo);
    $extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
    
    if ($extensao === 'sql' && $data > $ultimaDataSQL) {
        $ultimoSQL = $arquivo;
        $ultimaDataSQL = $data;
    } else if ($extensao === 'json' && $data > $ultimaDataJSON) {
        $ultimoJSON = $arquivo;
        $ultimaDataJSON = $data;
    }
}

// Contador de arquivos excluídos
$excluidos = 0;

// Excluir todos os backups, exceto os mais recentes
foreach ($arquivos as $arquivo) {
    if ($arquivo !== $ultimoSQL && $arquivo !== $ultimoJSON) {
        if (unlink($arquivo)) {
            $excluidos++;
        }
    }
}

// Mensagem de resultado
if ($excluidos > 0) {
    $_SESSION['mensagem'] = mensagem('success', $excluidos . ' backup(s) antigo(s) excluído(s) com sucesso');
} else {
    $_SESSION['mensagem'] = mensagem('info', 'Não há backups antigos para excluir');
}

// Redirecionar de volta
header('Location: /estoque/pages/backup/index.php');
exit;
?>