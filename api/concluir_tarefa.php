<?php
/**
 * api/concluir_tarefa.php
 * Conclusão da tarefa e gravação de CADA RESPOSTA como uma linha na tabela 'embaixador_tarefa_respostas'
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
$respostas   = $input['respostas'] ?? []; // Map: [id_questao => resposta]

if (!$id_registro) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'id_registro é obrigatório.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function calcularPostoER(int $pontos): string {
    if ($pontos >= 1000) return 'Embaixador Coroado';
    if ($pontos >= 600)  return 'Cavaleiro';
    if ($pontos >= 300)  return 'Escudeiro';
    if ($pontos >= 100)  return 'Arauto';
    return 'Candidato';
}

$pdo = garantirConexaoPdo();

try {
    // 1. Busca registro ativo de embaixador_tarefas
    $stmt = $pdo->prepare("
        SELECT 
            et.id_registro,
            et.id_embaixador,
            et.id_tarefa,
            et.status,
            et.data_inicio,
            TIMESTAMPDIFF(SECOND, et.data_inicio, NOW()) AS tempo_gasto_calculado,
            t.titulo,
            t.pontos,
            t.tempo_minimo_segundos,
            t.tipo
        FROM embaixador_tarefas et
        INNER JOIN tarefas t ON et.id_tarefa = t.id_tarefa
        WHERE et.id_registro = :id_registro
        LIMIT 1
    ");
    $stmt->execute([':id_registro' => $id_registro]);
    $registro = $stmt->fetch();

    if (!$registro) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Registro de tarefa não encontrado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($registro['status'] !== 'em_andamento') {
        http_response_code(409);
        echo json_encode(['sucesso' => false, 'mensagem' => "Esta tarefa já foi finalizada com status: '{$registro['status']}'."], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idEmbaixador        = (int)$registro['id_embaixador'];
    $idTarefa            = (int)$registro['id_tarefa'];
    $tempoGastoSegundos  = (int)$registro['tempo_gasto_calculado'];
    $tempoMinimoSegundos = (int)$registro['tempo_minimo_segundos'];
    $pontosMaximos       = (int)$registro['pontos'];

    // 2. Busca todas as questões da tarefa da tabela 'questoes'
    $stmtQ = $pdo->prepare("
        SELECT id_questao, ordem, enunciado, tipo,
               opcao_a, opcao_b, opcao_c, opcao_d, resposta_correta, pontos
        FROM questoes
        WHERE id_tarefa = :id_tarefa AND ativo = 1
        ORDER BY ordem ASC, id_questao ASC
    ");
    $stmtQ->execute([':id_tarefa' => $idTarefa]);
    $questoesBanco = $stmtQ->fetchAll();

    $totalQuestoes = count($questoesBanco);
    $acertos = 0;
    $erros = 0;
    $revisao = [];

    $pdo->beginTransaction();

    // 3. Statement para inserir CADA RESPOSTA como uma linha individual na tabela 'embaixador_tarefa_respostas'
    $stmtInsertResp = $pdo->prepare("
        INSERT INTO embaixador_tarefa_respostas (
            id_registro,
            id_embaixador,
            id_questao,
            resposta_escolhida,
            resposta_correta,
            acertou,
            pontos_obtidos
        ) VALUES (
            :id_registro,
            :id_embaixador,
            :id_questao,
            :resposta_escolhida,
            :resposta_correta,
            :acertou,
            :pontos_obtidos
        )
        ON DUPLICATE KEY UPDATE
            resposta_escolhida = VALUES(resposta_escolhida),
            resposta_correta   = VALUES(resposta_correta),
            acertou            = VALUES(acertou),
            pontos_obtidos     = VALUES(pontos_obtidos)
    ");

    foreach ($questoesBanco as $q) {
        $idQ = (int)$q['id_questao'];
        $respostaEscolhida = isset($respostas[$idQ]) ? trim((string)$respostas[$idQ]) : null;
        $gabarito = $q['resposta_correta'] ? strtolower(trim((string)$q['resposta_correta'])) : null;
        $pontosQuestao = (int)$q['pontos'];

        $acertou = false;
        $pontosGanhosNesta = 0;

        if ($q['tipo'] === 'multipla_escolha' && $gabarito !== null) {
            if ($respostaEscolhida !== null && strtolower($respostaEscolhida) === $gabarito) {
                $acertou = true;
                $pontosGanhosNesta = $pontosQuestao;
                $acertos++;
            } else {
                $erros++;
            }
        } elseif ($q['tipo'] === 'dissertativa') {
            // Questão dissertativa: se respondeu, conta presença e aguarda nota do conselheiro
            if (!empty($respostaEscolhida)) {
                $acertou = true;
                $pontosGanhosNesta = $pontosQuestao;
                $acertos++;
            }
        }

        // Grava a linha individual na tabela embaixador_tarefa_respostas
        $stmtInsertResp->execute([
            ':id_registro'        => $id_registro,
            ':id_embaixador'      => $idEmbaixador,
            ':id_questao'         => $idQ,
            ':resposta_escolhida' => $respostaEscolhida,
            ':resposta_correta'   => $gabarito,
            ':acertou'            => $acertou ? 1 : 0,
            ':pontos_obtidos'     => $pontosGanhosNesta
        ]);

        $opcoes = [];
        if ($q['opcao_a'] !== null) $opcoes[] = ['letra' => 'a', 'texto' => $q['opcao_a']];
        if ($q['opcao_b'] !== null) $opcoes[] = ['letra' => 'b', 'texto' => $q['opcao_b']];
        if ($q['opcao_c'] !== null) $opcoes[] = ['letra' => 'c', 'texto' => $q['opcao_c']];
        if ($q['opcao_d'] !== null) $opcoes[] = ['letra' => 'd', 'texto' => $q['opcao_d']];

        $revisao[] = [
            'id'               => $idQ,
            'enunciado'        => $q['enunciado'],
            'tipo'             => $q['tipo'],
            'opcoes'           => $opcoes,
            'sua_resposta'     => $respostaEscolhida,
            'resposta_correta' => $gabarito,
            'acertou'          => $acertou
        ];
    }

    // Cálculo proporcional de pontos
    if ($totalQuestoes > 0 && ($acertos + $erros) > 0) {
        $pontosCalculados = (int)round(($acertos / $totalQuestoes) * $pontosMaximos);
    } else {
        $pontosCalculados = $pontosMaximos;
    }

    // 4. REGRA ANTIFRAUDE OCULTA (Gravada apenas no banco para o Conselheiro)
    $isSuspeita = ($tempoGastoSegundos < $tempoMinimoSegundos);
    $novoStatus = $isSuspeita ? 'suspeita' : 'concluida';
    $pontosCreditados = $isSuspeita ? 0 : $pontosCalculados;

    // 5. Atualização de embaixador_tarefas (tempo_gasto_segundos é virtual, gerado pelo data_fim)
    $stmtUpdate = $pdo->prepare("
        UPDATE embaixador_tarefas
        SET 
            data_fim = NOW(),
            status = :status,
            pontos_obtidos = :pontos,
            respostas_submetidas = :resumo
        WHERE id_registro = :id_registro
    ");

    $resumoJson = json_encode([
        'total_questoes' => $totalQuestoes,
        'acertos'        => $acertos,
        'erros'          => $erros,
        'percentual'     => $totalQuestoes > 0 ? round(($acertos / $totalQuestoes) * 100) : 100
    ]);

    $stmtUpdate->execute([
        ':status'      => $novoStatus,
        ':pontos'      => $pontosCreditados,
        ':resumo'      => $resumoJson,
        ':id_registro' => $id_registro
    ]);

    // 6. Atualização de pontuacao_acumulada (se não for suspeita)
    $totalPontosAtual = 0;
    $postoAtual = 'Candidato';

    if ($novoStatus === 'concluida' && $pontosCreditados > 0) {
        $stmtPontos = $pdo->prepare("
            INSERT INTO pontuacao_acumulada (id_embaixador, total_pontos, nivel_posto)
            VALUES (:id_embaixador, :pontos, 'Candidato')
            ON DUPLICATE KEY UPDATE 
                total_pontos = total_pontos + :pontos_adicionais
        ");
        $stmtPontos->execute([
            ':id_embaixador'     => $idEmbaixador,
            ':pontos'            => $pontosCreditados,
            ':pontos_adicionais' => $pontosCreditados
        ]);

        $stmtTotal = $pdo->prepare("SELECT total_pontos FROM pontuacao_acumulada WHERE id_embaixador = :id");
        $stmtTotal->execute([':id' => $idEmbaixador]);
        $totalPontosAtual = (int)$stmtTotal->fetchColumn();

        $postoAtual = calcularPostoER($totalPontosAtual);

        $stmtPosto = $pdo->prepare("UPDATE pontuacao_acumulada SET nivel_posto = :posto WHERE id_embaixador = :id");
        $stmtPosto->execute([':posto' => $postoAtual, ':id' => $idEmbaixador]);
    } else {
        $stmtTotal = $pdo->prepare("SELECT total_pontos, nivel_posto FROM pontuacao_acumulada WHERE id_embaixador = :id");
        $stmtTotal->execute([':id' => $idEmbaixador]);
        $dadosSaldo = $stmtTotal->fetch();
        if ($dadosSaldo) {
            $totalPontosAtual = (int)$dadosSaldo['total_pontos'];
            $postoAtual = $dadosSaldo['nivel_posto'];
        }
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Simulado concluído com sucesso! Veja abaixo a revisão detalhada do seu desempenho.',
        'dados' => [
            'id_registro'           => $id_registro,
            'status'                => $novoStatus,
            'is_suspeita'           => $isSuspeita,
            'pontos_obtidos'        => $pontosCreditados,
            'pontos_maximos'        => $pontosMaximos,
            'total_questoes'        => $totalQuestoes,
            'acertos'               => $acertos,
            'erros'                 => $erros,
            'percentual'            => $totalQuestoes > 0 ? round(($acertos / $totalQuestoes) * 100) : 100,
            'revisao'               => $revisao,
            'ranking' => [
                'total_acumulado' => $totalPontosAtual,
                'posto'           => $postoAtual
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno ao finalizar a tarefa.',
        'detalhe' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
