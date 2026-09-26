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
$idResponsavelSelecionado = (int) ($_POST['id_responsavel'] ?? 0);

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

$pdo = $embaixada->pdo();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);
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

    if ($nivelAtual === 'responsavel' || $nivelAtual === 'conselheiro') {
        $idEmbaixador = (int) $pdo->lastInsertId();
        $pessoaCriada = $pdo->prepare(
            'SELECT id_pessoa FROM pessoas
             WHERE id_pessoa = :id_pessoa AND nome = :nome AND tipo = :tipo'
        );
        $pessoaCriada->execute([
            ':id_pessoa' => $idEmbaixador,
            ':nome' => $nome,
            ':tipo' => $tipo,
        ]);

        if (!$pessoaCriada->fetch()) {
            throw new RuntimeException('O cadastro criado não foi localizado.');
        }

        if ($nivelAtual === 'responsavel') {
            $pessoaResponsavel = $pdo->prepare(
                'SELECT id_pessoa FROM pessoas
                 WHERE id_usuario = :id_usuario AND id_pessoa <> :id_embaixador
                 LIMIT 1'
            );
            $pessoaResponsavel->execute([
                ':id_usuario' => $idUsuario,
                ':id_embaixador' => $idEmbaixador,
            ]);
            $idResponsavel = (int) ($pessoaResponsavel->fetchColumn() ?: 0);
        } else {
            $pessoaResponsavel = $pdo->prepare(
                'SELECT pessoas.id_pessoa
                 FROM pessoas
                 LEFT JOIN usuarios ON usuarios.id_usuario = pessoas.id_usuario
                 WHERE pessoas.id_pessoa = :id_responsavel
                   AND pessoas.status = \'ativo\'
                   AND (pessoas.tipo IN (\'responsavel\', \'conselheiro\')
                        OR usuarios.nivel IN (\'responsavel\', \'conselheiro\'))'
            );
            $pessoaResponsavel->execute([
                ':id_responsavel' => $idResponsavelSelecionado,
            ]);
            $idResponsavel = (int) ($pessoaResponsavel->fetchColumn() ?: 0);
        }

        if ($idEmbaixador <= 0 || $idResponsavel <= 0) {
            throw new RuntimeException('Não foi possível identificar os cadastros envolvidos no vínculo.');
        }

        $vinculoSql = "INSERT INTO responsavel_embaixador (id_responsavel, id_embaixador, relacionamento, ativo) VALUES (:id_responsavel, :id_embaixador, 'responsavel', 1)";
        $vinculoStmt = $pdo->prepare($vinculoSql);
        $vinculoStmt->execute([
            ':id_responsavel' => $idResponsavel,
            ':id_embaixador' => $idEmbaixador,
        ]);
    }

    $pdo->commit();
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: ../../index.php?pagina=inserir_pessoa&erro=salvar');
    exit;
}

header('Location: ../../index.php?pagina=pessoas&sucesso=1');
exit;
