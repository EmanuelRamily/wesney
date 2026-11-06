<?php
require_once 'conexao.php';

if (!usuarioLogado()) {
    mensagem('Entre na sua conta antes de finalizar o pedido.');
    ir('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ir('carrinho.php');
}

if (empty($_SESSION['carrinho'])) {
    mensagem('Seu carrinho está vazio.');
    ir('carrinho.php');
}

try {
    $pdo->beginTransaction();

    $itens = [];
    $total = 0;

    foreach ($_SESSION['carrinho'] as $id => $quantidade) {
        $id = (int)$id;
        $quantidade = (int)$quantidade;

        if ($id < 1 || $quantidade < 1) {
            throw new Exception('Item inválido no carrinho.');
        }

        $sql = $pdo->prepare(
            'SELECT * FROM produtos
             WHERE id = ? AND ativo = 1
             FOR UPDATE'
        );
        $sql->execute([$id]);

        $produto = $sql->fetch();

        if (
            !$produto ||
            $quantidade > (int)$produto['estoque']
        ) {
            throw new Exception(
                'Produto indisponível ou estoque insuficiente.'
            );
        }

        $preco = precoProduto($produto);

        $itens[] = [
            'produto' => $produto,
            'quantidade' => $quantidade,
            'preco' => $preco
        ];

        $total += $preco * $quantidade;
    }

    $sql = $pdo->prepare(
        "INSERT INTO pedidos (usuario_id, total, status)
         VALUES (?, ?, 'recebido')"
    );

    $sql->execute([
        $_SESSION['usuario']['id'],
        $total
    ]);

    $pedidoId = $pdo->lastInsertId();

    foreach ($itens as $item) {
        $sql = $pdo->prepare(
            'INSERT INTO itens_pedido
             (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (?, ?, ?, ?)'
        );

        $sql->execute([
            $pedidoId,
            $item['produto']['id'],
            $item['quantidade'],
            $item['preco']
        ]);

        $sql = $pdo->prepare(
            'UPDATE produtos
             SET estoque = estoque - ?
             WHERE id = ?'
        );

        $sql->execute([
            $item['quantidade'],
            $item['produto']['id']
        ]);
    }

    $pdo->commit();

    $_SESSION['carrinho'] = [];

    mensagem(
        'Pedido #' . $pedidoId .
        ' registrado com sucesso! Não foi realizado pagamento online.'
    );

    ir('index.php');

} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    mensagem(
        'Não foi possível registrar o pedido. Confira o carrinho e tente novamente.'
    );

    ir('carrinho.php');
}