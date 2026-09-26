<?php
/**
 * api/listar_embaixadores.php
 * Retorna os embaixadores ativos cadastrados para seleção rápida
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT p.id_pessoa, p.nome, p.tipo, u.login
        FROM pessoas p
        LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.tipo = 'embaixador' AND p.status = 'ativo'
        ORDER BY p.nome ASC
    ");
    $embaixadores = $stmt->fetchAll();

    echo json_encode([
        'sucesso' => true,
        'embaixadores' => array_map(function($e) {
            return [
                'id_pessoa' => (int)$e['id_pessoa'],
                'nome'      => $e['nome'],
                'login'     => $e['login'] ?? ''
            ];
        }, $embaixadores)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao listar embaixadores.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
