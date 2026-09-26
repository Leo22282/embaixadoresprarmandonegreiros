<?php
/**
 * api/login.php
 * Autenticação do Embaixador / Usuário
 * 
 * Exige obrigatoriamente login e senha válidos.
 * Compatível com senhas bcrypt, md5 e texto plano da base de dados.
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = getJsonInput();

$login = trim($input['login'] ?? '');
$senha = trim($input['senha'] ?? '');

if (empty($login) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o login e a senha de acesso.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = garantirConexaoPdo();

try {
    // 1. Busca o usuário no banco
    $stmtUser = $pdo->prepare("
        SELECT id_usuario, login, senha, nivel, ativo
        FROM usuarios
        WHERE login = :login AND ativo = 1
        LIMIT 1
    ");
    $stmtUser->execute([':login' => $login]);
    $usuario = $stmtUser->fetch();

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Login não encontrado ou usuário inativo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Validação segura de senha:
    // a) password_verify (moderno bcrypt/argon2)
    // b) md5 (senhas legadas da base)
    // c) texto plano
    $hashBanco = (string)$usuario['senha'];
    $senhaValida = false;

    if (password_verify($senha, $hashBanco)) {
        $senhaValida = true;
    } elseif (md5($senha) === strtolower($hashBanco)) {
        $senhaValida = true;
    } elseif ($senha === $hashBanco) {
        $senhaValida = true;
    }

    if (!$senhaValida) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta. Verifique suas credenciais.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Busca a pessoa correspondente ao usuário
    $stmtPessoa = $pdo->prepare("
        SELECT id_pessoa, nome, tipo
        FROM pessoas
        WHERE id_usuario = :id_usuario AND status = 'ativo'
        LIMIT 1
    ");
    $stmtPessoa->execute([':id_usuario' => $usuario['id_usuario']]);
    $pessoa = $stmtPessoa->fetch();

    if (!$pessoa) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Perfil de pessoa não encontrado para este usuário.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id_embaixador = (int)$pessoa['id_pessoa'];

    // 4. Busca pontuação e posto acumulado atual
    $stmtPontos = $pdo->prepare("SELECT total_pontos, nivel_posto FROM pontuacao_acumulada WHERE id_embaixador = :id");
    $stmtPontos->execute([':id' => $id_embaixador]);
    $dadosPontos = $stmtPontos->fetch();

    $totalPontos = $dadosPontos ? (int)$dadosPontos['total_pontos'] : 0;
    $nivelPosto  = $dadosPontos ? $dadosPontos['nivel_posto'] : 'Candidato';

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso! Bem-vindo.',
        'embaixador' => [
            'id_pessoa'         => $id_embaixador,
            'id_usuario'        => (int)$usuario['id_usuario'],
            'nome'              => $pessoa['nome'],
            'login'             => $usuario['login'],
            'tipo'              => $pessoa['tipo'],
            'total_pontos'      => $totalPontos,
            'nivel_posto'       => $nivelPosto
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno no processo de login.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
