<?php
session_start();
session_id();
date_default_timezone_set('America/Sao_Paulo');

header('Access-Control-Allow-Origin: *');

include 'db/db.php';
$embaixada = new Embaixada;
include 'header.php';

if (isset($_SESSION['logado'])) {
    $pagina = $_GET['pagina'] ?? 'pessoas';
} else {
    $pagina = 'home';
}

switch ($pagina) {
    case 'home':
        include 'views/home.php';
        break;

    case 'pessoas':
        if (!isset($_SESSION['logado'])) {
            include 'views/home.php';
            break;
        }
        include 'views/pessoas.php';
        break;

    case 'inserir_pessoa':
        if (!isset($_SESSION['logado'])) {
            include 'views/home.php';
            break;
        }
        include 'views/inserir_pessoa.php';
        break;

    case 'meu_cadastro':
        if (!isset($_SESSION['logado'])) {
            include 'views/home.php';
            break;
        }
        $_GET['id'] = $_SESSION['id_pessoa'] ?? null;
        include 'views/inserir_pessoa.php';
        break;

    default:
        include 'views/home.php';
        break;
}

include 'footer.php';
