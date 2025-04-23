<?php
// Configurações do banco de dados
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'netstore_estoque');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: 'estoque_1103');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'netstore_estoque');

if (!file_exists(__DIR__ . '/../log')) {
    mkdir(__DIR__ . '/../log', 0755, true);
}

$logFile = __DIR__ . '/../log/db_errors.log';

// Tentativa de conexão com o banco de dados MySQL
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    // Configurar PDO para lançar exceções em caso de erros
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERRO DE CONEXÃO: " . $e->getMessage());
}

// Definir o caminho base da aplicação
define('BASE_PATH', '/estoque');
?>