<?php
require_once __DIR__ . '/../config/database.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inicializar sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

// Verificar se o usuário é administrador
function ehAdmin() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}

// Redirecionar usuário não logado
function verificarLogin() {
    if (!estaLogado()) {
        $currentPath = $_SERVER['REQUEST_URI'];
        // Store the full path including /estoque prefix
        $_SESSION['redirect_after_login'] = $currentPath;
        if (!headers_sent()) {
            header('Location: /estoque/login.php');
            exit;
        } else {
            echo '<script>window.location.href = "/estoque/login.php";</script>';
            exit;
        }
    }
}

// Redirecionar usuário não administrador
function verificarAdmin() {
    if (!ehAdmin()) {
        if (!headers_sent()) {
            header('Location: /estoque/index.php');
            exit;
        } else {
            echo '<script>window.location.href = "/estoque/index.php";</script>';
            exit;
        }
    }
}

// Sanitizar entrada para prevenir XSS
function limparEntrada($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Gerar uma mensagem de alerta com Bootstrap
function mensagem($tipo, $texto) {
    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
              ' . $texto . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

// Formatar data para exibição
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// Obter nome do fabricante pelo ID
function getNomeFabricante($conn, $id) {
    $stmt = $conn->prepare("SELECT nome FROM fabricantes WHERE id = ?");
    $stmt->execute([$id]);
    $fabricante = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fabricante ? $fabricante['nome'] : 'Desconhecido';
}
?>