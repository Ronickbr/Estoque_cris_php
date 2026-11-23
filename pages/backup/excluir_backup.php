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

// Verificar se o nome do arquivo é seguro (sem "../" ou caminhos absolutos)
$arquivo = basename($arquivo);
$caminhoArquivo = dirname(__DIR__, 2) . '/backups/' . $arquivo;

// Verificar se o arquivo existe
if (!file_exists($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado.');
    header("Location: $redirectUrl");
    exit;
}

// Verificar se o arquivo é um tipo permitido
$extensao = pathinfo($caminhoArquivo, PATHINFO_EXTENSION);
if (!in_array(strtolower($extensao), ['sql', 'json'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Tipo de arquivo não permitido.');
    header("Location: $redirectUrl");
    exit;
}

// Excluir o arquivo
if (unlink($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('success', 'Backup excluído com sucesso.');
} else {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir o arquivo de backup.');
}

// Redirecionar de volta
header("Location: $redirectUrl");
exit;
