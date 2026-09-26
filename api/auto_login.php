<?php
/**
 * api/auto_login.php
 * Ponte de autenticação automática: verifica a sessão PHP do site principal
 * e retorna os dados do embaixador no mesmo formato que api/login.php,
 * permitindo que a gamificação React autentique o usuário sem pedir senha.
 *
 * Chamado via GET pelo gamificacao/index.php (ou pelo React em mount).
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

// Verifica se o usuário possui sessão ativa no site principal
if (empty($_SESSION['logado']) || empty($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Sessão não encontrada. Faça login no site principal primeiro.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = garantirConexaoPdo();

$idUsuario = (int) $_SESSION['id_usuario'];

try {
    // 1. Busca dados do usuário
    $stmtUser = $pdo->prepare("
        SELECT id_usuario, login, senha, nivel, ativo
        FROM usuarios
        WHERE id_usuario = :id_usuario AND ativo = 1
        LIMIT 1
    ");
    $stmtUser->execute([':id_usuario' => $idUsuario]);
    $usuario = $stmtUser->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Usuário da sessão não encontrado ou inativo.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Busca a pessoa correspondente
    $stmtPessoa = $pdo->prepare("
        SELECT id_pessoa, nome, tipo
        FROM pessoas
        WHERE id_usuario = :id_usuario AND status = 'ativo'
        LIMIT 1
    ");
    $stmtPessoa->execute([':id_usuario' => $idUsuario]);
    $pessoa = $stmtPessoa->fetch();

    if (!$pessoa) {
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Perfil de pessoa não encontrado para este usuário.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id_embaixador = (int) $pessoa['id_pessoa'];

    // 3. Busca pontuação e posto
    $stmtPontos = $pdo->prepare("SELECT total_pontos, nivel_posto FROM pontuacao_acumulada WHERE id_embaixador = :id");
    $stmtPontos->execute([':id' => $id_embaixador]);
    $dadosPontos = $stmtPontos->fetch();

    $totalPontos = $dadosPontos ? (int) $dadosPontos['total_pontos'] : 0;
    $nivelPosto  = $dadosPontos ? $dadosPontos['nivel_posto'] : 'Candidato';

    // 4. Retorna os dados no mesmo formato que api/login.php
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Autenticação automática via sessão do site principal.',
        'embaixador' => [
            'id_pessoa'    => $id_embaixador,
            'id_usuario'   => (int) $usuario['id_usuario'],
            'nome'         => $pessoa['nome'],
            'login'        => $usuario['login'],
            'tipo'         => $pessoa['tipo'],
            'total_pontos' => $totalPontos,
            'nivel_posto'  => $nivelPosto
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno na autenticação automática.',
        'detalhe' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
