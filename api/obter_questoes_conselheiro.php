<?php
/**
 * api/obter_questoes_conselheiro.php
 * Retorna as questões de uma tarefa diretamente da tabela 'questoes' com gabarito oficial para a reunião
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_tarefa = filter_var($_GET['id_tarefa'] ?? 1, FILTER_VALIDATE_INT);
$pdo = garantirConexaoPdo();

try {
    $stmt = $pdo->prepare("
        SELECT id_questao, id_tarefa, ordem, enunciado, tipo,
               opcao_a, opcao_b, opcao_c, opcao_d, resposta_correta, explicacao, pontos
        FROM questoes
        WHERE id_tarefa = :id_tarefa AND ativo = 1
        ORDER BY ordem ASC, id_questao ASC
    ");
    $stmt->execute([':id_tarefa' => $id_tarefa]);
    $questoes = $stmt->fetchAll();

    echo json_encode([
        'sucesso' => true,
        'questoes' => array_map(function($q) {
            $opcoes = [];
            if ($q['tipo'] === 'multipla_escolha') {
                if ($q['opcao_a'] !== null) $opcoes[] = ['letra' => 'a', 'texto' => $q['opcao_a']];
                if ($q['opcao_b'] !== null) $opcoes[] = ['letra' => 'b', 'texto' => $q['opcao_b']];
                if ($q['opcao_c'] !== null) $opcoes[] = ['letra' => 'c', 'texto' => $q['opcao_c']];
                if ($q['opcao_d'] !== null) $opcoes[] = ['letra' => 'd', 'texto' => $q['opcao_d']];
            }
            return [
                'id_questao'       => (int)$q['id_questao'],
                'id'               => (int)$q['id_questao'],
                'ordem'            => (int)$q['ordem'],
                'enunciado'        => $q['enunciado'],
                'tipo'             => $q['tipo'],
                'pontos'           => (int)$q['pontos'],
                'resposta_correta' => strtolower(trim((string)$q['resposta_correta'])),
                'correta'          => strtolower(trim((string)$q['resposta_correta'])),
                'explicacao'       => $q['explicacao'],
                'opcoes'           => $opcoes
            ];
        }, $questoes)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao carregar questões do conselheiro.', 'detalhe' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
