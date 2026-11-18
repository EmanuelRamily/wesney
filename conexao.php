<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = 'localhost';
$banco = 'autopecas';
$usuarioDB = 'root';
$senhaDB = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuarioDB,
        $senhaDB,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Erro de conexão. Confira o XAMPP e o banco de dados.');
}

function e($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function mensagem($texto) {
    $_SESSION['mensagem'] = $texto;
}

function ir($pagina = 'index.php') {
    header('Location: ' . $pagina);
    exit;
}

function usuarioLogado() {
    return isset($_SESSION['usuario']);
}

function exigirAdmin() {
    if (
        !usuarioLogado() ||
        $_SESSION['usuario']['tipo'] !== 'admin'
    ) {
        mensagem('Acesso permitido somente ao administrador.');
        ir('index.php');
    }
}

function precoProduto($produto) {
    if (
        usuarioLogado() &&
        $_SESSION['usuario']['tipo'] === 'atacado'
    ) {
        return (float)$produto['preco_atacado'];
    }

    return (float)$produto['preco_varejo'];
}

function dinheiro($valor) {
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}