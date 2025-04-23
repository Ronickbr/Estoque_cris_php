<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_usuario'])) {
    $nome = limparEntrada($_POST['novo_nome'] ?? '');
    $email = limparEntrada($_POST['novo_email'] ?? '');
    $senha = $_POST['nova_senha'] ?? '';
    $nivel = $_POST['nivel'] ?? '';

    if (empty($nome) || empty($email) || empty($senha) || empty($nivel)) {
        $_SESSION['erro_criar_usuario'] = 'Por favor, preencha todos os campos.';
        header('Location: /estoque/login.php');
        exit;
    }

    if (!in_array($nivel, ['usuario', 'admin'])) {
        $_SESSION['erro_criar_usuario'] = 'Nível de acesso inválido.';
        header('Location: /estoque/login.php');
        exit;
    }

    try {
        // Verificar se o nome ou e-mail já existe
        $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE nome = ? OR email = ?");
        $stmtCheck->execute([$nome, $email]);
        if ($stmtCheck->fetch()) {
            $_SESSION['erro_criar_usuario'] = 'Nome ou e-mail já cadastrado.';
            header('Location: /estoque/login.php');
            exit;
        }

        // Criptografar a senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir novo usuário
        $stmtInsert = $conn->prepare("
            INSERT INTO usuarios (nome, email, senha, nivel, criado_em)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtInsert->execute([$nome, $email, $senhaHash, $nivel]);

        $_SESSION['sucesso_criar_usuario'] = 'Usuário criado com sucesso! Faça login.';
        header('Location: /estoque/login.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro_criar_usuario'] = 'Erro ao criar usuário: ' . $e->getMessage();
        header('Location: /estoque/login.php');
        exit;
    }
}

// Redirecionar se não for POST
header('Location: /estoque/login.php');
exit;
?>