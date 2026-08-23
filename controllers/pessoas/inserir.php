<?php
session_start();
require_once '../../db/db.php';

$embaixada = new Embaixada;

$nome = trim($_POST['nome'] ?? '');
$nivelAtual = $_SESSION['nivel'] ?? '';
$tipo = trim($_POST['tipo'] ?? 'embaixador');
$telefone = trim($_POST['telefone'] ?? '');
$email = trim($_POST['email'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$genero = trim($_POST['genero'] ?? '');
$status = trim($_POST['status'] ?? 'ativo');
$observacao = trim($_POST['observacao'] ?? '');

if ($nome === '') {
    header('Location: ../../index.php?pagina=inserir_pessoa&erro=1');
    exit;
}

if ($nivelAtual === 'responsavel') {
    $tipo = 'embaixador';
}

if ($nivelAtual !== 'admin' && $nivelAtual !== 'conselheiro' && $nivelAtual !== 'responsavel') {
    $tipo = 'embaixador';
}

$idUsuario = $_SESSION['id_usuario'] ?? null;

$sql = "INSERT INTO pessoas (id_usuario, nome, tipo, telefone, email, data_nascimento, genero, status, observacao)
        VALUES (:id_usuario, :nome, :tipo, :telefone, :email, :data_nascimento, :genero, :status, :observacao)";

$stmt = $embaixada->pdo()->prepare($sql);
$stmt->execute([
    ':id_usuario' => $idUsuario,
    ':nome' => $nome,
    ':tipo' => $tipo,
    ':telefone' => $telefone,
    ':email' => $email,
    ':data_nascimento' => $dataNascimento !== '' ? $dataNascimento : null,
    ':genero' => $genero !== '' ? $genero : null,
    ':status' => $status,
    ':observacao' => $observacao,
]);

if ($nivelAtual === 'responsavel') {
    $pessoaResponsavel = $embaixada->list("SELECT id_pessoa FROM pessoas WHERE id_usuario = {$idUsuario} LIMIT 1");
    $idResponsavel = $pessoaResponsavel[0]['id_pessoa'] ?? 0;
    if ($idResponsavel > 0) {
        $idEmbaixador = $embaixada->pdo()->lastInsertId();
        $vinculoSql = "INSERT INTO responsavel_embaixador (id_responsavel, id_embaixador, relacionamento, ativo) VALUES (:id_responsavel, :id_embaixador, 'responsavel', 1)";
        $vinculoStmt = $embaixada->pdo()->prepare($vinculoSql);
        $vinculoStmt->execute([
            ':id_responsavel' => $idResponsavel,
            ':id_embaixador' => $idEmbaixador,
        ]);
    }
}

header('Location: ../../index.php?pagina=pessoas&sucesso=1');
exit;
