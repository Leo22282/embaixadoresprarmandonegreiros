<?php
/**
 * api/painel_monitoramento.php
 * Painel de Auditoria e Acompanhamento para Conselheiros e Responsáveis
 * 
 * Permite visualizar o histórico de tarefas do embaixador, tempo gasto,
 * status e destaca tarefas com alerta de 'suspeita' (antifraude).
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_embaixador = filter_var($_GET['id_embaixador'] ?? null, FILTER_VALIDATE_INT);
$filtro_status = trim($_GET['status'] ?? ''); // opcional: 'suspeita', 'concluida', 'em_andamento'

try {
    $sql = "
        SELECT 
            et.id_registro,
            et.id_embaixador,
            p.nome AS nome_embaixador,
            t.id_tarefa,
            t.titulo AS titulo_tarefa,
            t.tipo AS tipo_tarefa,
            t.pontos AS pontos_totais,
            t.tempo_minimo_segundos,
            et.status,
            et.data_inicio,
            et.data_fim,
            et.tempo_gasto_segundos,
            et.pontos_obtidos,
            et.respostas_submetidas,
            CASE 
                WHEN et.status = 'suspeita' THEN 1 
                ELSE 0 
            END AS alerta_fraude
        FROM embaixador_tarefas et
        INNER JOIN tarefas t ON et.id_tarefa = t.id_tarefa
        INNER JOIN pessoas p ON et.id_embaixador = p.id_pessoa
        WHERE 1=1
    ";

    $params = [];

    if ($id_embaixador) {
        $sql .= " AND et.id_embaixador = :id_embaixador";
        $params[':id_embaixador'] = $id_embaixador;
    }

    if (!empty($filtro_status)) {
        $sql .= " AND et.status = :status";
        $params[':status'] = $filtro_status;
    }

    $sql .= " ORDER BY et.data_inicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll();

    // Métricas resumidas para o painel
    $totalTarefas   = count($registros);
    $totalSuspeitas = count(array_filter($registros, fn($r) => $r['status'] === 'suspeita'));
    $totalConcluidas = count(array_filter($registros, fn($r) => in_array($r['status'], ['concluida', 'aprovada_lider'])));
    $totalPontos    = array_sum(array_column($registros, 'pontos_obtidos'));

    echo json_encode([
        'sucesso' => true,
        'metricas' => [
            'total_tarefas'    => $totalTarefas,
            'total_concluidas' => $totalConcluidas,
            'total_suspeitas'  => $totalSuspeitas,
            'pontos_totais'    => $totalPontos
        ],
        'registros' => array_map(function($r) {
            return [
                'id_registro'           => (int)$r['id_registro'],
                'id_embaixador'         => (int)$r['id_embaixador'],
                'nome_embaixador'       => $r['nome_embaixador'],
                'id_tarefa'             => (int)$r['id_tarefa'],
                'titulo_tarefa'         => $r['titulo_tarefa'],
                'tipo_tarefa'           => $r['tipo_tarefa'],
                'pontos_totais'         => (int)$r['pontos_totais'],
                'pontos_obtidos'        => (int)$r['pontos_obtidos'],
                'tempo_minimo_segundos' => (int)$r['tempo_minimo_segundos'],
                'tempo_gasto_segundos'  => (int)$r['tempo_gasto_segundos'],
                'status'                => $r['status'],
                'alerta_fraude'         => (bool)$r['alerta_fraude'],
                'data_inicio'           => $r['data_inicio'],
                'data_fim'              => $r['data_fim']
            ];
        }, $registros)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao carregar dados do painel de monitoramento.',
        'detalhe' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
