<?php
$titulo = 'Perfil';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar se usuário está logado
verificarLogin();

// Buscar dados do usuário logado
try {
    $usuario_id = $_SESSION['usuario_id'];
    $sql = "SELECT nome, email, criado_em, nivel FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $_SESSION['mensagem'] = mensagem('danger', 'Usuário não encontrado.');
        header('Location: /index.php');
        exit;
    }
} catch(PDOException $e) {
    $_SESSION['mensagem'] = mensagem('danger', 'Erro ao buscar dados do usuário: ' . $e->getMessage());
}

// Validação CSRF global para submissões POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf('/pages/perfil/index.php');
}

// Processar atualização de dados pessoais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_dados'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    
    // Validações
    if (empty($nome) || empty($email)) {
        $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos são obrigatórios.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem'] = mensagem('danger', 'E-mail inválido.');
    } else {
        try {
            // Verificar se o e-mail já está em uso por outro usuário
            $sqlEmail = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $stmtEmail = $conn->prepare($sqlEmail);
            $stmtEmail->execute([$email, $usuario_id]);
            
            if ($stmtEmail->rowCount() > 0) {
                $_SESSION['mensagem'] = mensagem('danger', 'Este e-mail já está em uso por outro usuário.');
            } else {
                // Atualizar dados
                $sqlUpdate = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->execute([$nome, $email, $usuario_id]);
                
                $_SESSION['mensagem'] = mensagem('success', 'Dados atualizados com sucesso!');
                // Atualizar dados na sessão
                $_SESSION['usuario_nome'] = $nome;
                header('Location: /pages/perfil/index.php');
                exit;
            }
        } catch(PDOException $e) {
            $_SESSION['mensagem'] = mensagem('danger', 'Erro ao atualizar dados: ' . $e->getMessage());
        }
    }
}

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos são obrigatórios.');
    } elseif ($nova_senha !== $confirmar_senha) {
        $_SESSION['mensagem'] = mensagem('danger', 'As novas senhas não coincidem.');
    } elseif (strlen($nova_senha) < 6) {
        $_SESSION['mensagem'] = mensagem('danger', 'A nova senha deve ter pelo menos 6 caracteres.');
    } else {
        try {
            // Verificar senha atual
            $sqlSenha = "SELECT senha FROM usuarios WHERE id = ?";
            $stmtSenha = $conn->prepare($sqlSenha);
            $stmtSenha->execute([$usuario_id]);
            $senhaHash = $stmtSenha->fetchColumn();
            
            if (!password_verify($senha_atual, $senhaHash)) {
                $_SESSION['mensagem'] = mensagem('danger', 'Senha atual incorreta.');
            } else {
                // Atualizar senha
                $novaSenhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sqlUpdateSenha = "UPDATE usuarios SET senha = ? WHERE id = ?";
                $stmtUpdateSenha = $conn->prepare($sqlUpdateSenha);
                $stmtUpdateSenha->execute([$novaSenhaHash, $usuario_id]);
                
                $_SESSION['mensagem'] = mensagem('success', 'Senha alterada com sucesso!');
                header('Location: /pages/perfil/index.php');
                exit;
            }
        } catch(PDOException $e) {
            $_SESSION['mensagem'] = mensagem('danger', 'Erro ao alterar senha: ' . $e->getMessage());
        }
    }
}

// Processar alteração de email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_email'])) {
    $novo_email = filter_var($_POST['novo_email'], FILTER_SANITIZE_EMAIL);
    $confirmar_email = filter_var($_POST['confirmar_email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha_email'];
    
    // Validações
    if (empty($novo_email) || empty($confirmar_email) || empty($senha)) {
        $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos são obrigatórios.');
    } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem'] = mensagem('danger', 'Email inválido.');
    } elseif ($novo_email !== $confirmar_email) {
        $_SESSION['mensagem'] = mensagem('danger', 'Os emails não coincidem.');
    } else {
        try {
            // Verificar senha
            $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $senhaHash = $stmt->fetchColumn();
            
            if (!password_verify($senha, $senhaHash)) {
                $_SESSION['mensagem'] = mensagem('danger', 'Senha incorreta.');
            } else {
                // Verificar se o novo email já está em uso
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$novo_email, $_SESSION['usuario_id']]);
                
                if ($stmt->fetch()) {
                    $_SESSION['mensagem'] = mensagem('danger', 'Este email já está sendo usado por outro usuário.');
                } else {
                    // Atualizar email
                    $stmt = $conn->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
                    $stmt->execute([$novo_email, $_SESSION['usuario_id']]);
                    
                    $_SESSION['mensagem'] = mensagem('success', 'Email atualizado com sucesso!');
                    $_SESSION['usuario_email'] = $novo_email;
                    header('Location: /pages/perfil/index.php');
                    exit;
                }
            }
        } catch(PDOException $e) {
            $_SESSION['mensagem'] = mensagem('danger', 'Erro ao alterar email: ' . $e->getMessage());
        }
    }
}

// Incluir cabeçalho
include __DIR__ . '/../../includes/header.php';
?>

<style>
/* Estilos adicionais para responsividade */
.card-header h5 {
    font-size: 1.25rem;
}
.form-label {
    font-size: 0.9rem;
}
.form-control {
    font-size: 0.9rem;
}
.form-text {
    font-size: 0.8rem;
}

/* Responsividade */
@media (max-width: 767.98px) {
    .content {
        padding: 1rem;
        padding-top: 4rem; /* Espaço para a navbar-mobile */
        margin-left: 0 !important; /* Garante que o conteúdo não tenha margem à esquerda */
    }
    .content .row {
        max-width: 100%; /* Remove a limitação de largura */
        margin: 0 auto;
        display: flex;
        justify-content: center; /* Centraliza os cards */
        flex-wrap: wrap;
    }
    .content .col-md-4 {
        max-width: 90%; /* Limita a largura dos cards para centralizar */
    }
    h1 {
        font-size: 1.5rem;
    }
    .breadcrumb {
        font-size: 0.8rem;
    }
    .btn-lg {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    .card-header h5 {
        font-size: 1.1rem;
    }
    .card-body {
        padding: 1rem;
    }
    .form-label {
        font-size: 0.85rem;
    }
    .form-control {
        font-size: 0.85rem;
        height: calc(2.5rem + 2px);
    }
    .form-text {
        font-size: 0.75rem;
    }
    .btn {
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 575.98px) {
    .content .col-md-4 {
        max-width: 95%; /* Ajusta ainda mais a largura dos cards */
    }
    h1 {
        font-size: 1.25rem;
    }
    .breadcrumb {
        font-size: 0.75rem;
    }
    .btn-lg {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
    .card-header h5 {
        font-size: 1rem;
    }
    .form-label {
        font-size: 0.8rem;
    }
    .form-control {
        font-size: 0.8rem;
        height: calc(2.25rem + 2px);
    }
    .form-text {
        font-size: 0.7rem;
    }
    .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}
</style>

<div class="content">
    <!-- Exibir mensagens de sessão se existirem -->
    <?php 
    if (isset($_SESSION['mensagem'])) {
        echo $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
    }
    ?>

    <!-- Cabeçalho da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="mb-0 text-dark fw-bold"><i class="bi bi-person-circle me-2"></i>Meu Perfil</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= url('/index.php'); ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Meu Perfil</li>
                </ol>
            </nav>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="<?= url('/index.php'); ?>" class="btn btn-secondary btn-lg">
                <i class="bi bi-arrow-left"></i> Voltar para Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Dados Pessoais -->
        <div class="col-12 col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-person me-2"></i>Dados Pessoais
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('/pages/perfil/index.php'); ?>" method="post">
                        <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Data de Cadastro</label>
                            <input type="text" class="form-control" value="<?php echo formatarData($usuario['criado_em']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Acesso</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($usuario['nivel']); ?>" readonly>
                        </div>
                        <button type="submit" name="atualizar_dados" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Atualizar Dados
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Alterar Email -->
        <div class="col-12 col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-envelope me-2"></i>Alterar Email
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('/pages/perfil/index.php'); ?>" method="post">
                        <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                        <div class="mb-3">
                            <label for="email_atual" class="form-label">Email Atual</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="novo_email" class="form-label">Novo Email</label>
                            <input type="email" class="form-control" id="novo_email" name="novo_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_email" class="form-label">Confirmar Novo Email</label>
                            <input type="email" class="form-control" id="confirmar_email" name="confirmar_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha_email" class="form-label">Sua Senha</label>
                            <input type="password" class="form-control" id="senha_email" name="senha_email" required>
                            <div class="form-text">Digite sua senha para confirmar a alteração</div>
                        </div>
                        <button type="submit" name="alterar_email" class="btn btn-warning w-100">
                            <i class="bi bi-envelope-fill"></i> Alterar Email
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Alterar Senha -->
        <div class="col-12 col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold text-primary">
                        <i class="bi bi-lock me-2"></i>Alterar Senha
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?= url('/pages/perfil/index.php'); ?>" method="post">
                        <?= function_exists('campoCsrf') ? campoCsrf() : ''; ?>
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        </div>
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                        </div>
                        <button type="submit" name="alterar_senha" class="btn btn-warning w-100">
                            <i class="bi bi-lock-fill"></i> Alterar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir rodapé
include __DIR__ . '/../../includes/footer.php';
?>