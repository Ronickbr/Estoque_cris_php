<?php
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../includes/header.php';

verificarLogin();
exigirCsrf('/pages/perfil/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($nome) || empty($email)) {
        $_SESSION['mensagem'] = mensagem('danger', 'Todos os campos são obrigatórios.');
        header('Location: /pages/perfil/index.php');
        exit;
    }

    if ($nome && $email && $senha) {
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = mensagem('success', 'Perfil atualizado com sucesso!');
            header('Location: /pages/perfil/index.php');
            exit;
        } else {
            echo "Erro ao cadastrar usuário.";
        }
    } else {
        echo "Preencha todos os campos.";
    }
}

header('Location: /pages/perfil/index.php');
exit;
?>

<form method="POST">
    <label for="nome">Nome:</label>
    <input type="text" id="nome" name="nome" required>
    <br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="senha" required>
    <br>
    <button type="submit">Cadastrar</button>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
``` 