<?php
require_once 'conexao.php';

unset($_SESSION['usuario'], $_SESSION['carrinho']);

session_regenerate_id(true);

mensagem('Você saiu da sua conta.');

ir('index.php');