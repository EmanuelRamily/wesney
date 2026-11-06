
<?php
require_once 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoCadastro = $_POST['tipo_cadastro'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
    $empresa = trim($_POST['nome_empresa'] ?? '');

    if (!in_array($tipoCadastro, ['fisica', 'juridica'], true)) {
        $erro = 'Selecione o tipo de cadastro.';
    } elseif ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Preencha seu nome e um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($tipoCadastro === 'fisica' && strlen($cpf) !== 11) {
        $erro = 'O CPF deve ter 11 números.';
    } elseif ($tipoCadastro === 'juridica' &&
        (strlen($cnpj) !== 14 || $empresa === '')) {
        $erro = 'Informe o nome da empresa e um CNPJ com 14 números.';
    } else {
        try {
            $tipo = $tipoCadastro === 'juridica' ? 'varejo' : 'varejo';
            $status = $tipoCadastro === 'juridica'
                ? 'pendente'
                : 'nao_se_aplica';

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
                (nome, email, senha, tipo, cpf, cnpj,
                 nome_empresa, status_empresa)
                VALUES
                (:nome, :email, :senha, :tipo, :cpf, :cnpj,
                 :empresa, :status)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senhaHash,
                ':tipo' => $tipo,
                ':cpf' => $tipoCadastro === 'fisica' ? $cpf : null,
                ':cnpj' => $tipoCadastro === 'juridica' ? $cnpj : null,
                ':empresa' => $tipoCadastro === 'juridica' ? $empresa : null,
                ':status' => $status
            ]);

            mensagem('sucesso', 'Cadastro realizado! Faça seu login.');
            ir('login.php');

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Este CPF, CNPJ ou e-mail já está cadastrado.';
            } else {
                $erro = 'Não foi possível realizar o cadastro.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Autopeças</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<main>
    <div class="card">
        <h2>Criar conta</h2>
        <p>Escolha o tipo de cadastro.</p>

        <?php if ($erro): ?>
            <div class="erro">
                <?= e($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Tipo de cadastro</label>
            <select name="tipo_cadastro" id="tipoCadastro"
                    onchange="alterarCadastro()" required>
                <option value="">Selecione</option>
                <option value="fisica">Pessoa Física - CPF</option>
                <option value="juridica">Pessoa Jurídica - CNPJ</option>
            </select>

            <label>Nome do responsável</label>
            <input type="text" name="nome" required>

            <div id="camposCPF" style="display:none;">
                <label>CPF</label>
                <input type="text" name="cpf"
                       id="cpf" maxlength="14"
                       placeholder="000.000.000-00">
            </div>

            <div id="camposCNPJ" style="display:none;">
                <label>Nome da empresa</label>
                <input type="text" name="nome_empresa"
                       id="empresa">

                <label>CNPJ</label>
                <input type="text" name="cnpj"
                       id="cnpj" maxlength="18"
                       placeholder="00.000.000/0000-00">
            </div>

            <label>E-mail</label>
            <input type="email" name="email" required>

            <label>Senha</label>
            <input type="password" name="senha"
                   minlength="6" required>

            <button type="submit">Criar cadastro</button>

        </form>

        <p>Já possui conta?
            <a href="login.php">Entrar</a>
        </p>
    </div>
</main>

<script>
function alterarCadastro() {
    const tipo = document.getElementById('tipoCadastro').value;

    const cpf = document.getElementById('camposCPF');
    const cnpj = document.getElementById('camposCNPJ');

    cpf.style.display = tipo === 'fisica' ? 'block' : 'none';
    cnpj.style.display = tipo === 'juridica' ? 'block' : 'none';

    document.getElementById('cpf').required = tipo === 'fisica';
    document.getElementById('empresa').required = tipo === 'juridica';
    document.getElementById('cnpj').required = tipo === 'juridica';
}
</script>

</body>
</html>
