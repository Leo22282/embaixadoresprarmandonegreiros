<?php
/**
 * api/listar_tarefas.php
 * Retorna as tarefas ativas disponíveis para o embaixador
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
        SELECT id_tarefa, titulo, descricao, tipo, pontos, tempo_minimo_segundos, 
               data_inicio_vigencia, data_fim_vigencia
        FROM tarefas
        WHERE ativo = 1
        ORDER BY id_tarefa ASC
    ");
    $tarefas = $stmt->fetchAll();

    echo json_encode([
        'sucesso' => true,
        'tarefas' => array_map(function($t) {
            return [
                'id_tarefa'              => (int)$t['id_tarefa'],
                'titulo'                 => $t['titulo'],
                'descricao'              => $t['descricao'],
                'tipo'                   => $t['tipo'],
                'pontos'                 => (int)$t['pontos'],
                'tempo_minimo_segundos'  => (int)$t['tempo_minimo_segundos'],
                'data_inicio_vigencia'   => $t['data_inicio_vigencia'],
                'data_fim_vigencia'      => $t['data_fim_vigencia']
            ];
        }, $tarefas)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao listar tarefas.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
