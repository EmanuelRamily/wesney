<?php
require_once 'conexao.php';

$sql = $pdo->query(
    'SELECT * FROM produtos
     WHERE ativo = 1
     ORDER BY nome'
);

$produtos = $sql->fetchAll();

$aviso = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AutoPeças Central</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<header>
    <h1>AutoPeças Central</h1>

    <nav>
        <a href="index.php">Início</a>
        <a href="carrinho.php">Carrinho</a>

        <?php if (usuarioLogado()): ?>
            <span>Olá, <?= e($_SESSION['usuario']['nome']) ?>!</span>

            <?php if ($_SESSION['usuario']['tipo'] === 'admin'): ?>
                <a href="admin.php">Administração</a>
            <?php endif; ?>

            <a href="logout.php">Sair</a>
        <?php else: ?>
            <a href="login.php">Entrar</a>
            <a href="cadastro.php">Cadastrar</a>
        <?php endif; ?>
    </nav>
</header>

<main>

    <?php if ($aviso): ?>
        <div class="aviso"><?= e($aviso) ?></div>
    <?php endif; ?>

    <section class="caixa">
        <h2>Encontre as peças para seu veículo</h2>
        <p>
            Trabalhamos com vendas para clientes de varejo
            e empresas aprovadas para compras no atacado.
        </p>
    </section>

    <section class="produtos">

        <?php foreach ($produtos as $produto): ?>
            <article class="produto">
                <h3><?= e($produto['nome']) ?></h3>

                <p><?= e($produto['descricao']) ?></p>

                <h3><?= dinheiro(precoProduto($produto)) ?></h3>

                <p class="pequeno">
                    Estoque: <?= (int)$produto['estoque'] ?>
                </p>

                <?php if ((int)$produto['estoque'] > 0): ?>
                    <form action="carrinho.php" method="post">
                        <input
                            type="hidden"
                            name="acao"
                            value="adicionar"
                        >

                        <input
                            type="hidden"
                            name="produto_id"
                            value="<?= (int)$produto['id'] ?>"
                        >

                        <button>Adicionar ao carrinho</button>
                    </form>
                <?php else: ?>
                    <p>Produto esgotado.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$produtos): ?>
            <p>Nenhum produto cadastrado.</p>
        <?php endif; ?>

    </section>

</main>

<footer>
    AutoPeças Central — Projeto acadêmico
</footer>

</body>
</html>