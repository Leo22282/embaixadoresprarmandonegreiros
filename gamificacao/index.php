<?php
/**
 * gamificacao/index.php
 * Ponto de entrada da Gamificação com auto‑login.
 *
 * Quando o embaixador clica em "Gamificação" no header.php do site principal,
 * este script:
 *   1. Verifica se há sessão PHP ativa (usuário logado no site).
 *   2. Chama api/auto_login.php internamente para obter os dados do embaixador.
 *   3. Injeta esses dados no localStorage antes de carregar o React SPA,
 *      de modo que o App.jsx já encontre o usuário autenticado e pule a tela de login.
 *   4. Se não houver sessão, redireciona para o login do site principal.
 */

session_start();

// Se o usuário não está logado no site principal, redireciona
if (empty($_SESSION['logado']) || empty($_SESSION['id_usuario'])) {
    header('Location: ../index.php?erro=sessao');
    exit;
}

// Monta os dados do embaixador fazendo uma requisição interna ao auto_login
// Em vez de chamar via HTTP, simulamos incluindo o arquivo diretamente
// e capturando a saída JSON.
ob_start();

// Salva o REQUEST_METHOD original e simula GET para o auto_login
$_SERVER['REQUEST_METHOD_ORIGINAL'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

include __DIR__ . '/../api/auto_login.php';

$jsonResposta = ob_get_clean();

// Limpa os headers JSON que o db.php setou durante o include
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Methods');
header_remove('Access-Control-Allow-Headers');
header('Content-Type: text/html; charset=UTF-8');

$dadosResposta = json_decode($jsonResposta, true);

// Se deu erro na busca, redireciona para o site principal
if (!$dadosResposta || empty($dadosResposta['sucesso'])) {
    header('Location: ../index.php?erro=autologin');
    exit;
}

$embaixadorJson = json_encode($dadosResposta['embaixador'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Embaixadores do Rei - Plataforma Gamificada</title>
    <script type="module" crossorigin src="./assets/index-lQCc962K.js"></script>
    <link rel="stylesheet" crossorigin href="./assets/index-s-v17lFD.css">
  </head>
  <body>
    <div id="root"></div>
    <script>
      // Injeta o embaixador no localStorage antes do React montar.
      // O App.jsx já lê de 'er_embaixador_ativo' e pula o Login se existir.
      try {
        var dados = <?php echo $embaixadorJson; ?>;
        localStorage.setItem('er_embaixador_ativo', JSON.stringify(dados));
      } catch(e) {
        console.error('Erro ao gravar auto-login:', e);
      }
    </script>
  </body>
</html>
