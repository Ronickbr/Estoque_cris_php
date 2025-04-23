<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

verificarLogin();

try {
    $tabelas = [
        'inventory_movements',
        'products',
        'manufacturers',
        'usuarios'
    ];

    $arquivo = '../../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    $handle = fopen($arquivo, 'w');

    // Cabeçalho do arquivo SQL
    fwrite($handle, "-- Backup gerado em " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Sistema de Gerenciamento de Estoque\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tabelas as $tabela) {
        // Adicionar DROP TABLE IF EXISTS antes de cada CREATE TABLE
        $stmt = $conn->query("SHOW CREATE TABLE $tabela");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        fwrite($handle, "DROP TABLE IF EXISTS `$tabela`;\n");
        fwrite($handle, $row['Create Table'] . ";\n\n");

        // Obter dados da tabela
        $stmt = $conn->query("SELECT * FROM $tabela");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $valores = array_map(function($valor) use ($conn) {
                    if ($valor === null) return 'NULL';
                    return $conn->quote($valor);
                }, $row);
                
                fwrite($handle, "INSERT INTO $tabela (" . 
                    implode(', ', array_keys($row)) . 
                    ") VALUES (" . implode(', ', $valores) . ");\n");
            }
            fwrite($handle, "\n");
        }
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename=' . basename($arquivo));
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    exit;

} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao gerar backup: ' . $e->getMessage());
    header('Location: /estoque/pages/backup/index.php');
    exit;
}
?>