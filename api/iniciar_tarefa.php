<?php
/**
 * api/iniciar_tarefa.php
 * Registra o início da tarefa e busca questões normalizadas da tabela 'questoes'
 */

declare(strict_types=1);

require_once file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : (file_exists(__DIR__ . '/config/db.php') ? __DIR__ . '/config/db.php' : __DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido. Utilize POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = getJsonInput();
$id_embaixador = filter_var($input['id_embaixador'] ?? 5, FILTER_VALIDATE_INT);
$id_tarefa     = filter_var($input['id_tarefa'] ?? 1, FILTER_VALIDATE_INT);

$pdo = garantirConexaoPdo();

try {
    // 1. Validar se o embaixador existe e é do tipo 'embaixador' ativo
    $stmtPessoa = $pdo->prepare("
        SELECT id_pessoa, nome, status 
        FROM pessoas 
        WHERE id_pessoa = :id_pessoa AND status = 'ativo'
        LIMIT 1
    ");
    $stmtPessoa->execute([':id_pessoa' => $id_embaixador]);
    $pessoa = $stmtPessoa->fetch();

    if (!$pessoa) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Embaixador não encontrado ou inativo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Validar se a tarefa existe, está ativa e vigente
    $stmtTarefa = $pdo->prepare("
        SELECT id_tarefa, titulo, descricao, tipo, pontos, tempo_minimo_segundos, 
               data_inicio_vigencia, data_fim_vigencia, conteudo_json, ativo
        FROM tarefas
        WHERE id_tarefa = :id_tarefa
        LIMIT 1
    ");
    $stmtTarefa->execute([':id_tarefa' => $id_tarefa]);
    $tarefa = $stmtTarefa->fetch();

    if (!$tarefa || (int)$tarefa['ativo'] !== 1) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tarefa não encontrada ou inativa.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Buscar questões normalizadas na tabela 'questoes' (cada questão é uma linha)
    $stmtQuestoes = $pdo->prepare("
        SELECT id_questao, id_tarefa, ordem, enunciado, tipo,
               opcao_a, opcao_b, opcao_c, opcao_d, pontos
        FROM questoes
        WHERE id_tarefa = :id_tarefa AND ativo = 1
        ORDER BY ordem ASC, id_questao ASC
    ");
    $stmtQuestoes->execute([':id_tarefa' => $id_tarefa]);
    $linhasQuestoes = $stmtQuestoes->fetchAll();

    // Formata perguntas para o frontend (sem expor resposta_correta para o aluno)
    $perguntasFormatadas = [];
    foreach ($linhasQuestoes as $q) {
        $opcoes = [];
        if ($q['tipo'] === 'multipla_escolha') {
            if ($q['opcao_a'] !== null) $opcoes[] = ['letra' => 'a', 'texto' => $q['opcao_a']];
            if ($q['opcao_b'] !== null) $opcoes[] = ['letra' => 'b', 'texto' => $q['opcao_b']];
            if ($q['opcao_c'] !== null) $opcoes[] = ['letra' => 'c', 'texto' => $q['opcao_c']];
            if ($q['opcao_d'] !== null) $opcoes[] = ['letra' => 'd', 'texto' => $q['opcao_d']];
        }

        $perguntasFormatadas[] = [
            'id_questao' => (int)$q['id_questao'],
            'id'         => (int)$q['id_questao'],
            'ordem'      => (int)$q['ordem'],
            'enunciado'  => $q['enunciado'],
            'tipo'       => $q['tipo'],
            'pontos'     => (int)$q['pontos'],
            'opcoes'     => $opcoes
        ];
    }

    $conteudoJson = json_decode($tarefa['conteudo_json'] ?? '{}', true);
    $textoLeitura = $conteudoJson['texto_leitura'] ?? null;

    // 4. Resiliência: Checa se há tentativa aberta 'em_andamento'
    $stmtCheck = $pdo->prepare("
        SELECT id_registro, data_inicio, TIMESTAMPDIFF(SECOND, data_inicio, NOW()) AS segundos_passados
        FROM embaixador_tarefas
        WHERE id_embaixador = :id_embaixador 
          AND id_tarefa = :id_tarefa 
          AND status = 'em_andamento'
        ORDER BY id_registro DESC
        LIMIT 1
    ");
    $stmtCheck->execute([
        ':id_embaixador' => $id_embaixador,
        ':id_tarefa'     => $id_tarefa
    ]);
    $registroExistente = $stmtCheck->fetch();

    if ($registroExistente) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Retomando tarefa em andamento.',
            'id_registro' => (int)$registroExistente['id_registro'],
            'data_inicio' => $registroExistente['data_inicio'],
            'segundos_passados' => (int)$registroExistente['segundos_passados'],
            'tarefa' => [
                'id_tarefa' => (int)$tarefa['id_tarefa'],
                'titulo' => $tarefa['titulo'],
                'descricao' => $tarefa['descricao'],
                'tipo' => $tarefa['tipo'],
                'pontos' => (int)$tarefa['pontos'],
                'tempo_minimo_segundos' => (int)$tarefa['tempo_minimo_segundos'],
                'conteudo' => [
                    'texto_leitura' => $textoLeitura,
                    'perguntas'     => $perguntasFormatadas
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Inserir novo registro com NOW() auditado no MySQL
    $stmtInsert = $pdo->prepare("
        INSERT INTO embaixador_tarefas (
            id_embaixador,
            id_tarefa,
            status,
            data_inicio,
            pontos_obtidos
        ) VALUES (
            :id_embaixador,
            :id_tarefa,
            'em_andamento',
            NOW(),
            0
        )
    ");

    $stmtInsert->execute([
        ':id_embaixador' => $id_embaixador,
        ':id_tarefa'     => $id_tarefa
    ]);

    $id_registro = (int)$pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Tarefa iniciada com sucesso.',
        'id_registro' => $id_registro,
        'segundos_passados' => 0,
        'tarefa' => [
            'id_tarefa' => (int)$tarefa['id_tarefa'],
            'titulo' => $tarefa['titulo'],
            'descricao' => $tarefa['descricao'],
            'tipo' => $tarefa['tipo'],
            'pontos' => (int)$tarefa['pontos'],
            'tempo_minimo_segundos' => (int)$tarefa['tempo_minimo_segundos'],
            'conteudo' => [
                'texto_leitura' => $textoLeitura,
                'perguntas'     => $perguntasFormatadas
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar início da tarefa.',
        'detalhe' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
