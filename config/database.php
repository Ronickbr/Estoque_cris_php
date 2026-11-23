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
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("ERRO DE CONEXÃO: " . $e->getMessage());
}

// Definir o caminho base da aplicação dinamicamente
if (!defined('BASE_PATH')) {
    if (getenv('BASE_PATH') !== false && getenv('BASE_PATH') !== '') {
        $base = getenv('BASE_PATH');
    } else {
        $base = '/';
        if (php_sapi_name() !== 'cli' && isset($_SERVER['SCRIPT_NAME'])) {
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
            // Se o script estiver rodando em uma subpasta como /estoque/...
            if (preg_match('#^(.*?)/(?:pages|config|includes|assets|src|uploads|backups|login\.php|logout\.php|index\.php|criar_usuario\.php)#i', $scriptName, $m)) {
                $base = $m[1] === '' ? '/' : $m[1] . '/';
            } elseif (preg_match('#^(/[^/]+)#', $scriptName, $m) && !in_array($m[1], ['/index.php', '/login.php', '/logout.php', '/criar_usuario.php', '/var', '/usr', '/etc', '/home', '/app'])) {
                $base = $m[1] . '/';
            }
        }
    }
    // Normalize: ensure it starts and ends with '/'
    $base = '/' . trim($base, '/') . '/';
    if ($base === '//') {
        $base = '/';
    }
    define('BASE_PATH', $base);
}

// Helpers de roteamento dinâmico
if (!function_exists('url')) {
    function url($path = '') {
        $base = defined('BASE_PATH') ? BASE_PATH : '/';
        $trimmedBase = trim($base, '/');
        $path = ltrim((string)$path, '/');
        
        // Strip legacy 'estoque/' prefix if base is not 'estoque'
        if ($trimmedBase !== 'estoque' && (strpos($path, 'estoque/') === 0 || $path === 'estoque')) {
            $path = substr($path, 7);
        }
        
        if ($trimmedBase !== '' && strpos($path, $trimmedBase . '/') === 0) {
            $path = substr($path, strlen($trimmedBase) + 1);
        }
        
        if ($path === '' || $path === false) {
            return $base;
        }
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        return url($path);
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        $target = url($path);
        if (!headers_sent()) {
            header('Location: ' . $target);
            exit;
        } else {
            echo '<script>window.location.href = ' . json_encode($target) . ';</script>';
            exit;
        }
    }
}
?>