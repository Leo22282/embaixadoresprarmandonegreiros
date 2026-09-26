<?php
/**
 * api/aprovar_tarefa.php
 * Permite ao conselheiro auditar e aprovar manualmente tarefas com status 'suspeita'
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = getJsonInput();
$id_registro = filter_var($input['id_registro'] ?? null, FILTER_VALIDATE_INT);
$acao        = $input['acao'] ?? 'aprovar'; // 'aprovar' ou 'descartar'

if (!$id_registro) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'id_registro é obrigatório.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT et.id_registro, et.id_embaixador, et.status, t.pontos
        FROM embaixador_tarefas et
        INNER JOIN tarefas t ON et.id_tarefa = t.id_tarefa
        WHERE et.id_registro = :id_registro
        LIMIT 1
    ");
    $stmt->execute([':id_registro' => $id_registro]);
    $reg = $stmt->fetch();

    if (!$reg) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Registro não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idEmbaixador = (int)$reg['id_embaixador'];
    $pontosTarefa = (int)$reg['pontos'];

    $pdo->beginTransaction();

    if ($acao === 'aprovar') {
        $stmtUp = $pdo->prepare("
            UPDATE embaixador_tarefas
            SET status = 'aprovada_lider', pontos_obtidos = :pontos
            WHERE id_registro = :id_registro
        ");
        $stmtUp->execute([':pontos' => $pontosTarefa, ':id_registro' => $id_registro]);

        // Credita pontos acumulados
        $stmtPontos = $pdo->prepare("
            INSERT INTO pontuacao_acumulada (id_embaixador, total_pontos, nivel_posto)
            VALUES (:id, :pontos, 'Candidato')
            ON DUPLICATE KEY UPDATE total_pontos = total_pontos + :pontos_add
        ");
        $stmtPontos->execute([':id' => $idEmbaixador, ':pontos' => $pontosTarefa, ':pontos_add' => $pontosTarefa]);

        $mensagem = "Tarefa #{$id_registro} aprovada pelo conselheiro com sucesso! Pontuação creditada.";
    } else {
        $stmtUp = $pdo->prepare("
            UPDATE embaixador_tarefas
            SET status = 'suspeita', pontos_obtidos = 0
            WHERE id_registro = :id_registro
        ");
        $stmtUp->execute([':id_registro' => $id_registro]);
        $mensagem = "Tarefa mantida como rejeitada/suspeita.";
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao auditar tarefa.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
