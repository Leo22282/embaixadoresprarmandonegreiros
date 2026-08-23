<?php
session_start();
require_once '../../db/db.php';
require_once '../../validators/ValidadorImagem.php';

if (!isset($_SESSION['logado'])) {
    header('Location: ../../index.php');
    exit;
}

$nivelAtual = $_SESSION['nivel'] ?? 'embaixador';
if ($nivelAtual === 'embaixador') {
    header('Location: ../../index.php?pagina=atividades&erro=sem_permissao');
    exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$textoCurto = trim($_POST['texto_curto'] ?? '');
$conteudoHtml = trim($_POST['conteudo_html'] ?? '');

if ($titulo === '' || $textoCurto === '' || $conteudoHtml === '') {
    header('Location: ../../index.php?pagina=inserir_atividade&erro=campos_obrigatorios');
    exit;
}

if (mb_strlen($titulo) > 150 || mb_strlen($textoCurto) > 300) {
    header('Location: ../../index.php?pagina=inserir_atividade&erro=limite_caracteres');
    exit;
}

$imagemRelativa = null;
$imagemRecebida = $_FILES['imagem'] ?? null;

if ($imagemRecebida && $imagemRecebida['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        $extensaoImagem = ValidadorImagem::validar($imagemRecebida);
    } catch (RuntimeException $erro) {
        header('Location: ../../index.php?pagina=inserir_atividade&erro=tipo_imagem_invalido');
        exit;
    }

    $pastaAtividades = rtrim($pastaImagens, '/\\') . DIRECTORY_SEPARATOR . 'atividades';
    if (!is_dir($pastaAtividades) && !mkdir($pastaAtividades, 0755, true)) {
        header('Location: ../../index.php?pagina=inserir_atividade&erro=pasta_imagem');
        exit;
    }

    $nomeImagem = bin2hex(random_bytes(16)) . '.' . $extensaoImagem;
    $caminhoImagem = $pastaAtividades . DIRECTORY_SEPARATOR . $nomeImagem;

    if (!move_uploaded_file($imagemRecebida['tmp_name'], $caminhoImagem)) {
        header('Location: ../../index.php?pagina=inserir_atividade&erro=salvar_imagem');
        exit;
    }

    $imagemRelativa = 'images/atividades/' . $nomeImagem;
}

$embaixada = new Embaixada;
$sql = "INSERT INTO atividades (titulo, texto_curto, conteudo_html, imagem)
        VALUES (:titulo, :texto_curto, :conteudo_html, :imagem)";

try {
    $stmt = $embaixada->pdo()->prepare($sql);
    $stmt->execute([
        ':titulo' => $titulo,
        ':texto_curto' => $textoCurto,
        ':conteudo_html' => $conteudoHtml,
        ':imagem' => $imagemRelativa,
    ]);
} catch (Throwable $erro) {
    if ($imagemRelativa !== null) {
        @unlink($pastaAtividades . DIRECTORY_SEPARATOR . $nomeImagem);
    }

    header('Location: ../../index.php?pagina=inserir_atividade&erro=salvar_atividade');
    exit;
}

header('Location: ../../index.php?pagina=atividades&sucesso=1');
exit;
