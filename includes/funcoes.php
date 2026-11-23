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
        $_SESSION['redirect_after_login'] = $currentPath;
        redirect('/login.php');
    }
}

// Redirecionar usuário não administrador
function verificarAdmin() {
    if (!ehAdmin()) {
        $_SESSION['mensagem'] = mensagem('danger', 'Acesso restrito a administradores.');
        redirect('/index.php');
    }
}

// Gerar ou recuperar token CSRF da sessão
function gerarCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Retornar campo input hidden com token CSRF
function campoCsrf() {
    $token = htmlspecialchars(gerarCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Validar token CSRF recebido via POST ou cabeçalho HTTP
function verificarCsrfToken($token = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Exigir método POST e token CSRF válido para operações de escrita/destrutivas
function exigirCsrf($redirectUrl = null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCsrfToken()) {
        http_response_code(403);
        $_SESSION['mensagem'] = mensagem('danger', 'Requisição não autorizada ou token de segurança inválido/expirado.');
        
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido ou ausente.']);
            exit;
        }
        
        if ($redirectUrl && !headers_sent()) {
            header("Location: $redirectUrl");
            exit;
        } elseif (!headers_sent() && !empty($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        } else {
            $defaultUrl = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/index.php';
            if (!headers_sent()) {
                header("Location: " . $defaultUrl);
                exit;
            } else {
                echo '<div class="alert alert-danger">Acesso negado: Token CSRF inválido ou ausente.</div>';
                exit;
            }
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