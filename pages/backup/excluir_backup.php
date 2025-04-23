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

// Verificar se o arquivo é um tipo permitido
$extensao = pathinfo($caminhoArquivo, PATHINFO_EXTENSION);
if (!in_array(strtolower($extensao), ['sql', 'json'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Tipo de arquivo não permitido');
    header('Location: /estoque/pages/backup/index.php');
    exit;
}

// Excluir o arquivo
if (unlink($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('success', 'Backup excluído com sucesso');
} else {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao excluir o arquivo');
}

// Redirecionar de volta
header('Location: /estoque/pages/backup/index.php');
exit;
?>