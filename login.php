<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $sql = $pdo->prepare(
        'SELECT * FROM usuarios WHERE email = ?'
    );
    $sql->execute([$email]);

    $usuario = $sql->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo']
        ];

        mensagem('Login realizado com sucesso!');
        ir('index.php');
    }

    mensagem('E-mail ou senha incorretos.');
    ir('login.php');
}

$aviso = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — AutoPeças Central</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<header>
    <h1>AutoPeças Central</h1>
    <nav>
        <a href="index.php">Início</a>
        <a href="cadastro.php">Cadastrar</a>
    </nav>
</header>

<main>
    <section class="caixa">
        <h2>Entrar na conta</h2>

        <?php if ($aviso): ?>
            <div class="aviso"><?= e($aviso) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>E-mail</label>
            <input type="email" name="email" required>

            <label>Senha</label>
            <input type="password" name="senha" required>

            <button type="submit">Entrar</button>
        </form>

        <p>
            Não tem conta?
            <a href="cadastro.php">Cadastre-se</a>.
        </p>
    </section>
</main>

</body>
</html>