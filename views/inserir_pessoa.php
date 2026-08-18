<?php
$acao = 'controllers/pessoas/inserir.php';
$idPessoa = $_GET['id'] ?? null;

if ($idPessoa) {
    $dadosPessoa = $financeiro->list("SELECT * FROM pessoas WHERE id_pessoa = " . (int)$idPessoa);
    $dadosPessoa = $dadosPessoa[0] ?? null;
    if ($dadosPessoa) {
        $acao = 'controllers/pessoas/editar.php';
        $nome = $dadosPessoa['nome'];
        $tipo = $dadosPessoa['tipo'];
        $telefone = $dadosPessoa['telefone'] ?? '';
        $email = $dadosPessoa['email'] ?? '';
        $dataNascimento = $dadosPessoa['data_nascimento'] ?? '';
        $genero = $dadosPessoa['genero'] ?? '';
        $status = $dadosPessoa['status'] ?? 'ativo';
        $observacao = $dadosPessoa['observacao'] ?? '';
    }
}

if (!isset($nome)) {
    $nome = '';
    $tipo = $_SESSION['nivel'] === 'embaixador' ? 'embaixador' : 'embaixador';
    $telefone = '';
    $email = '';
    $dataNascimento = '';
    $genero = '';
    $status = 'ativo';
    $observacao = '';
}
?>

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="page-title mb-1"><?php echo $idPessoa ? 'Editar cadastro' : 'Cadastrar pessoa'; ?></h2>
            <p class="text-muted mb-0">Dados do perfil da pessoa no sistema.</p>
        </div>
        <a href="index.php?pagina=pessoas" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <form action="<?php echo $acao; ?>" method="post">
        <?php if ($idPessoa): ?>
            <input type="hidden" name="id" value="<?php echo (int)$idPessoa; ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="nome" class="form-label">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="tipo" class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo" <?php echo $_SESSION['nivel'] === 'embaixador' ? 'disabled' : ''; ?> required>
                    <option value="responsavel" <?php echo ($tipo ?? '') === 'responsavel' ? 'selected' : ''; ?>>Responsável</option>
                    <option value="embaixador" <?php echo ($tipo ?? '') === 'embaixador' ? 'selected' : ''; ?>>Embaixador</option>
                    <option value="conselheiro" <?php echo ($tipo ?? '') === 'conselheiro' ? 'selected' : ''; ?>>Conselheiro</option>
                </select>
                <?php if ($_SESSION['nivel'] === 'embaixador'): ?>
                    <input type="hidden" name="tipo" value="embaixador">
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="col-md-4">
                <label for="data_nascimento" class="form-label">Data de nascimento</label>
                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" value="<?php echo htmlspecialchars($dataNascimento); ?>">
            </div>

            <div class="col-md-4">
                <label for="genero" class="form-label">Gênero</label>
                <select class="form-select" id="genero" name="genero">
                    <option value="">Selecione</option>
                    <option value="masculino" <?php echo ($genero ?? '') === 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="feminino" <?php echo ($genero ?? '') === 'feminino' ? 'selected' : ''; ?>>Feminino</option>
                    <option value="outro" <?php echo ($genero ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="ativo" <?php echo ($status ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo ($status ?? 'ativo') === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>

            <div class="col-12">
                <label for="observacao" class="form-label">Observação</label>
                <textarea class="form-control" id="observacao" name="observacao" rows="4"><?php echo htmlspecialchars($observacao); ?></textarea>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-success"><?php echo $idPessoa ? 'Salvar alterações' : 'Cadastrar'; ?></button>
            <a href="index.php?pagina=pessoas" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>
