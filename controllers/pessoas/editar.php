<?php
session_start();
require_once '../../db/db.php';

$financeiro = new Financeiro;

$id = (int)($_POST['id'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$tipo = trim($_POST['tipo'] ?? 'embaixador');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$genero = trim($_POST['genero'] ?? '');
$status = trim($_POST['status'] ?? 'ativo');
$observacao = trim($_POST['observacao'] ?? '');

if ($id <= 0 || $nome === '') {
    header('Location: ../../index.php?pagina=inserir_pessoa&erro=1');
    exit;
}

$sql = "UPDATE pessoas SET nome = :nome, tipo = :tipo, telefone = :telefone, email = :email, data_nascimento = :data_nascimento, genero = :genero, status = :status, observacao = :observacao WHERE id_pessoa = :id";
$stmt = $financeiro->pdo()->prepare($sql);
$stmt->execute([
    ':nome' => $nome,
    ':tipo' => $tipo,
    ':telefone' => $telefone,
    ':email' => $email,
    ':data_nascimento' => $dataNascimento !== '' ? $dataNascimento : null,
    ':genero' => $genero !== '' ? $genero : null,
    ':status' => $status,
    ':observacao' => $observacao,
    ':id' => $id,
]);

header('Location: ../../index.php?pagina=pessoas&sucesso=1');
exit;
