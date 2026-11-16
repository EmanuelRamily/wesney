<?php
require_once 'conexao.php';

exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    /* CADASTRAR PRODUTO */

    if ($acao === 'cadastrar_produto') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        $precoVarejo = filter_var(
            $_POST['preco_varejo'] ?? '',
            FILTER_VALIDATE_FLOAT
        );

        $precoAtacado = filter_var(
            $_POST['preco_atacado'] ?? '',
            FILTER_VALIDATE_FLOAT
        );

        $estoque = filter_var(
            $_POST['estoque'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (
            $nome === '' ||
            $precoVarejo === false ||
            $precoAtacado === false ||
            $estoque === false ||
            $precoVarejo < 0 ||
            $precoAtacado < 0 ||
            $estoque < 0
        ) {
            mensagem('Confira os dados do produto.');
            ir('admin.php');
        }

        $sql = $pdo->prepare(
            'INSERT INTO produtos
             (nome, descricao, preco_varejo, preco_atacado, estoque, ativo)
             VALUES (?, ?, ?, ?, ?, 1)'
        );

        $sql->execute([
            $nome,
            $descricao,
            $precoVarejo,
            $precoAtacado,
            $estoque
        ]);

        mensagem('Produto cadastrado com sucesso!');
        ir('admin.php');
    }

    /* APROVAR OU RECUSAR EMPRESA */

    if ($acao === 'avaliar_empresa') {
        $id = filter_input(
            INPUT_POST,
            'usuario_id',
            FILTER_VALIDATE_INT
        );

        $decisao = $_POST['decisao'] ?? '';

        if (
            !$id ||
            !in_array($decisao, ['aprovar', 'recusar'], true)
        ) {
            mensagem('Solicitação inválida.');
            ir('admin.php');
        }

        if ($decisao === 'aprovar') {
            $sql = $pdo->prepare(
                "UPDATE usuarios
                 SET tipo = 'atacado',
                     status_empresa = 'aprovada'
                 WHERE id = ?
                   AND status_empresa = 'pendente'"
            );
        } else {
            $sql = $pdo->prepare(
                "UPDATE usuarios
                 SET tipo = 'varejo',
                     status_empresa = 'recusada'
                 WHERE id = ?
                   AND status_empresa = 'pendente'"
            );
        }

        $sql->execute([$id]);

        if ($sql->rowCount() > 0) {
            mensagem(
                $decisao === 'aprovar'
                    ? 'Empresa aprovada para comprar no atacado.'
                    : 'Cadastro empresarial recusado.'
            );
        } else {
            mensagem('Não foi encontrada uma solicitação pendente para essa conta.');
        }

        ir('admin.php');
    }
}

$aviso = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$sql = $pdo->query(
    "SELECT id, nome, email, nome_empresa, cnpj
     FROM usuarios
     WHERE status_empresa = 'pendente'
     ORDER BY id DESC"
);

$empresas = $sql->fetchAll();

$sql = $pdo->query(
    'SELECT * FROM produtos ORDER BY id DESC'
);

$produtos = $sql->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administração — AutoPeças Central</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<header>
    <h1>Painel administrativo</h1>

    <nav>
        <a href="index.php">Ver loja</a>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<main>

    <?php if ($aviso): ?>
        <div class="aviso"><?= e($aviso) ?></div>
    <?php endif; ?>

    <section class="caixa">
        <h2>Cadastrar produto</h2>

        <form method="post">
            <input
                type="hidden"
                name="acao"
                value="cadastrar_produto"
            >

            <label>Nome</label>
            <input name="nome" maxlength="150" required>

            <label>Descrição</label>
            <textarea name="descricao" rows="3"></textarea>

            <label>Preço de varejo</label>
            <input
                type="number"
                name="preco_varejo"
                min="0"
                step="0.01"
                required
            >

            <label>Preço de atacado</label>
            <input
                type="number"
                name="preco_atacado"
                min="0"
                step="0.01"
                required
            >

            <label>Estoque</label>
            <input
                type="number"
                name="estoque"
                min="0"
                step="1"
                required
            >

            <button type="submit">Cadastrar produto</button>
        </form>
    </section>

    <section class="caixa">
        <h2>Empresas aguardando aprovação</h2>

        <?php if (!$empresas): ?>
            <p>Não existem empresas pendentes.</p>
        <?php else: ?>

            <?php foreach ($empresas as $empresa): ?>
                <article class="produto">
                    <h3><?= e($empresa['nome_empresa']) ?></h3>

                    <p>
                        Responsável: <?= e($empresa['nome']) ?>
                    </p>

                    <p>
                        E-mail: <?= e($empresa['email']) ?>
                    </p>

                    <p>
                        CNPJ: <?= e($empresa['cnpj']) ?>
                    </p>

                    <form method="post" style="display:inline-block">
                        <input
                            type="hidden"
                            name="acao"
                            value="avaliar_empresa"
                        >

                        <input
                            type="hidden"
                            name="usuario_id"
                            value="<?= (int)$empresa['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="decisao"
                            value="aprovar"
                        >

                        <button type="submit">Aprovar atacado</button>
                    </form>

                    <form method="post" style="display:inline-block">
                        <input
                            type="hidden"
                            name="acao"
                            value="avaliar_empresa"
                        >

                        <input
                            type="hidden"
                            name="usuario_id"
                            value="<?= (int)$empresa['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="decisao"
                            value="recusar"
                        >

                        <button type="submit" class="perigo">
                            Recusar
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <section class="caixa">
        <h2>Produtos cadastrados</h2>

        <?php if (!$produtos): ?>
            <p>Nenhum produto cadastrado.</p>
        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Varejo</th>
                        <th>Atacado</th>
                        <th>Estoque</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><?= e($produto['nome']) ?></td>
                            <td><?= dinheiro($produto['preco_varejo']) ?></td>
                            <td><?= dinheiro($produto['preco_atacado']) ?></td>
                            <td><?= (int)$produto['estoque'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </section>

</main>

</body>
</html>