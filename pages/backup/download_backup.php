<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado e é admin
verificarLogin();

// Verificar se o arquivo foi especificado
if (!isset($_GET['arquivo']) || empty($_GET['arquivo'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não especificado');
    header('Location: /pages/backup/index.php');
    exit;
}

// Verificar se o nome do arquivo é seguro (sem "../" ou caminhos absolutos)
$arquivo = basename($_GET['arquivo']);
$caminhoArquivo = '../../backups/' . $arquivo;

// Verificar se o arquivo existe
if (!file_exists($caminhoArquivo)) {
    $_SESSION['mensagem'] = mensagem('danger', 'Arquivo não encontrado');
    header('Location: /pages/backup/index.php');
    exit;
}

// Verificar se o arquivo é um tipo permitido
$extensao = pathinfo($caminhoArquivo, PATHINFO_EXTENSION);
if (!in_array(strtolower($extensao), ['sql', 'json'])) {
    $_SESSION['mensagem'] = mensagem('danger', 'Tipo de arquivo não permitido');
    header('Location: /pages/backup/index.php');
    exit;
}

// Configurar cabeçalhos para download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminhoArquivo));

// Limpar buffer de saída
ob_clean();
flush();

// Enviar arquivo
readfile($caminhoArquivo);
exit;
?>