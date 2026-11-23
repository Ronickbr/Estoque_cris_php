<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

// Redirecionar se já estiver logado
if (estaLogado()) {
    header('Location: /index.php');
    exit;
}

$erro = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['criar_usuario'])) {
    if (!verificarCsrfToken()) {
        $erro = 'Sessão expirada ou token de segurança inválido. Recarregue a página.';
    } else {
        $login = limparEntrada($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';
    
    if (empty($login) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, nome, email, senha, nivel FROM usuarios WHERE email = ? OR nome = ?");
            $stmt->execute([$login, $login]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_nivel'] = $usuario['nivel'];
                
                // Redirecionar para a página inicial
                header('Location: /index.php');
                exit;
            } else {
                $erro = 'Usuário ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}
}

$titulo = 'Login';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - Gerenciador de Estoque</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- CSS Personalizado -->
    <link href="<?php echo function_exists('url') ? url('/assets/css/style.css') : '/assets/css/style.css'; ?>?v=<?php echo @filemtime(__DIR__ . '/assets/css/style.css') ?: '2.1'; ?>" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--slate-900);
            font-family: var(--font-sans);
            padding: 1.5rem;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 2.25rem;
            margin: auto;
            background: #ffffff;
            border: 1px solid var(--slate-800);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <main class="form-signin text-center">
        <form method="post" action="">
            <?php echo campoCsrf(); ?>
            <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 56px; height: 56px; background-color: var(--primary-subtle); color: var(--primary);">
                <i class="bi bi-boxes fs-2"></i>
            </div>
            <h1 class="h4 mb-1 fw-bold text-dark">Estoque Pro</h1>
            <p class="text-muted small mb-4">Acesso ao Painel Corporativo</p>
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['sucesso_criar_usuario'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['sucesso_criar_usuario']; ?>
                    <?php unset($_SESSION['sucesso_criar_usuario']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['erro_criar_usuario'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['erro_criar_usuario']; ?>
                    <?php unset($_SESSION['erro_criar_usuario']); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="login" name="login" placeholder="Nome ou E-mail" required>
                <label for="login">Nome ou E-mail</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required>
                <label for="senha">Senha</label>
            </div>
            
            <div class="mb-3">
                <a href="/redefinir-senha.php">Esqueceu a senha?</a>
            </div>
            
            <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">Entrar</button>
            
            <!-- Botão oculto para criar usuário 
            <button type="button" class="hidden-btn mt-2" data-bs-toggle="modal" data-bs-target="#modalCriarUsuario">
                Criar novo usuário
            </button> -->
            
            <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> Gerenciador de Estoque</p>
        </form>
    </main>
    
    <!-- Modal para Criar Usuário -->
    <div class="modal fade" id="modalCriarUsuario" tabindex="-1" aria-labelledby="modalCriarUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCriarUsuarioLabel">Criar Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="/criar_usuario.php">
                    <?php echo campoCsrf(); ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="novo_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="novo_nome" name="novo_nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="novo_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="novo_email" name="novo_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                        </div>
                        <div class="mb-3">
                            <label for="nivel" class="form-label">Nível de Acesso</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="">Selecione...</option>
                                <option value="usuario">Usuário</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="criar_usuario" class="btn btn-primary">Criar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>