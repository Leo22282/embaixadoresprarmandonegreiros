<?php
/**
 * api/login.php
 * Autenticação do Embaixador / Usuário
 * 
 * Compatível com base u725505776_embaixada
 * Suporta:
 * 1. Login com usuário e senha (bcrypt, md5 legado ou texto plano)
 * 2. Login rápido de demonstração/testes por id_pessoa
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
$id_pessoa_direto = filter_var($input['id_pessoa'] ?? null, FILTER_VALIDATE_INT);

try {
    $pessoa = null;
    $usuario = null;

    // CASO A: Login rápido selecionando a pessoa (útil para testes em sala de aula ou conselheiro)
    if ($id_pessoa_direto) {
        $stmtPessoa = $pdo->prepare("
            SELECT p.id_pessoa, p.nome, p.tipo, p.id_usuario, u.login, u.nivel
            FROM pessoas p
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_pessoa = :id AND p.status = 'ativo'
            LIMIT 1
        ");
        $stmtPessoa->execute([':id' => $id_pessoa_direto]);
        $pessoa = $stmtPessoa->fetch();

        if (!$pessoa) {
            http_response_code(404);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Embaixador não encontrado ou inativo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } 
    // CASO B: Login tradicional com login e senha
    else {
        if (empty($login)) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Informe o seu login de acesso.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Busca o usuário no banco
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

        // Validação flexível de senha:
        // 1. password_verify (moderno bcrypt/argon2)
        // 2. md5 (senhas legadas da base Hostinger)
        // 3. texto plano (caso haja usuários de teste)
        $hashBanco = $usuario['senha'];
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

        // Busca a pessoa correspondente ao usuário
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

        $pessoa['login'] = $usuario['login'];
        $pessoa['nivel'] = $usuario['nivel'];
    }

    // Busca pontuação e posto acumulado atual
    $id_embaixador = (int)$pessoa['id_pessoa'];
    $stmtPontos = $pdo->prepare("SELECT total_pontos, nivel_posto FROM pontuacao_acumulada WHERE id_embaixador = :id");
    $stmtPontos->execute([':id' => $id_embaixador]);
    $dadosPontos = $stmtPontos->fetch();

    $totalPontos = $dadosPontos ? (int)$dadosPontos['total_pontos'] : 0;
    $nivelPosto  = $dadosPontos ? $dadosPontos['nivel_posto'] : 'Candidato';

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Login realizado com sucesso! Bem-vindo, Embaixador.',
        'embaixador' => [
            'id_pessoa'         => $id_embaixador,
            'id_usuario'        => $pessoa['id_usuario'] ?? null,
            'nome'              => $pessoa['nome'],
            'login'             => $pessoa['login'] ?? '',
            'tipo'              => $pessoa['tipo'],
            'total_pontos'      => $totalPontos,
            'nivel_posto'       => $nivelPosto
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno no processo de login.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
