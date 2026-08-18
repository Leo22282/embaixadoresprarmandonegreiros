<?php
session_start();

require_once '../db/db.php';

$embaixada = new Embaixada;
$login = trim($_POST['login'] ?? '');
$senha = md5(trim($_POST['senha'] ?? ''));

$sql = "SELECT * FROM usuarios WHERE login = '{$login}' AND senha = '{$senha}' LIMIT 1";
$registro = $embaixada->list($sql);

if (!empty($registro)) {
    $usuario = $registro[0];
    $_SESSION['logado'] = true;
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['login'] = $usuario['login'];
    $_SESSION['nome'] = $usuario['nome'];
    $_SESSION['nivel'] = $usuario['nivel'];

    $pessoa = $embaixada->list("SELECT * FROM pessoas WHERE id_usuario = {$usuario['id_usuario']} LIMIT 1");
    if (!empty($pessoa)) {
        $_SESSION['id_pessoa'] = $pessoa[0]['id_pessoa'];
        $_SESSION['tipo_pessoa'] = $pessoa[0]['tipo'];
    }

    header('Location: ../index.php?pagina=pessoas');
    exit;
}

header('Location: ../index.php?erro=1');
exit;
