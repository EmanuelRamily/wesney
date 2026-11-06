<?php
require_once 'conexao.php';

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

    if ($acao === 'adicionar' && $id && $id > 0) {
        $sql = $pdo->prepare(
            'SELECT id, estoque FROM produtos
             WHERE id = ? AND ativo = 1'
        );
        $sql->execute([$id]);
        $produto = $sql->fetch();

        if (!$produto) {
            mensagem('Produto não encontrado.');
        } else {
            $quantidadeAtual = (int)(
                $_SESSION['carrinho'][$id] ?? 0
            );

            if ($quantidadeAtual + 1 > (int)$produto['estoque']) {
                mensagem('Estoque insuficiente.');
            } else {
                $_SESSION['carrinho'][$id] = $quantidadeAtual + 1;
                mensagem('Produto adicionado ao carrinho.');
            }
        }

        ir('carrinho.php');
    }

    if ($acao === 'remover' && $id) {
        unset($_SESSION['carrinho'][$id]);
        mensagem('Produto removido do carrinho.');
        ir('carrinho.php');
    }
}

$itens = [];
$total = 0;

foreach ($_SESSION['carrinho'] as $id => $quantidade) {
    $sql = $pdo->prepare(
        'SELECT * FROM produtos WHERE id = ? AND ativo = 1'
    );
    $sql->execute([(int)$id]);
    $produto = $sql->fetch();

    if (!$produto) {
        continue;
    }

    $quantidade = (int)$quantidade;
    $preco = precoProduto($produto);
    $subtotal = $preco * $quantidade;

    $itens[] = [
        'produto' => $produto,
        'quantidade' => $quantidade,
        'preco' => $preco,
        'subtotal' => $subtotal
    ];

    $total += $subtotal;
}

$aviso = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrinho — AutoPeças Central</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<header>
    <h1>Meu carrinho</h1>
    <nav>
        <a href="index.php">Continuar comprando</a>
        <a href="login.php">Entrar</a>
    </nav>
</header>

<main>
    <section class="caixa">

        <?php if ($aviso): ?>
            <div class="aviso"><?= e($aviso) ?></div>
        <?php endif; ?>

        <?php if (!$itens): ?>
            <p>Seu carrinho está vazio.</p>
        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Quantidade</th>
                        <th>Subtotal</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= e($item['produto']['nome']) ?></td>
                            <td><?= dinheiro($item['preco']) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td><?= dinheiro($item['subtotal']) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="acao" value="remover">
                                    <input
                                        type="hidden"
                                        name="produto_id"
                                        value="<?= (int)$item['produto']['id'] ?>"
                                    >
                                    <button class="perigo">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Total: <?= dinheiro($total) ?></h2>

            <?php if (usuarioLogado()): ?>
                <form action="finalizar_pedido.php" method="post">
                    <button>Registrar pedido</button>
                </form>
            <?php else: ?>
                <p>Entre na sua conta para registrar o pedido.</p>
                <a class="botao" href="login.php">Entrar</a>
            <?php endif; ?>

            <p class="pequeno">
                Esta demonstração registra pedidos, mas não processa
                pagamentos online.
            </p>

        <?php endif; ?>

    </section>
</main>

</body>
</html>