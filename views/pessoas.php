<?php
$nivel = $_SESSION['nivel'] ?? 'embaixador';
$idUsuario = $_SESSION['id_usuario'] ?? 0;
$idPessoaAtual = $_SESSION['id_pessoa'] ?? 0;

if ($nivel === 'admin') {
    $sql = "SELECT * FROM pessoas ORDER BY nome ASC";
} elseif ($nivel === 'responsavel') {
    $sql = "
        SELECT p.*
        FROM responsavel_embaixador re
        INNER JOIN pessoas p ON p.id_pessoa = re.id_embaixador
        WHERE re.id_responsavel = (
            SELECT pe.id_pessoa
            FROM pessoas pe
            WHERE pe.id_usuario = {$idUsuario}
            LIMIT 1
        )
        ORDER BY p.nome ASC
    ";
} else {
    $sql = "SELECT * FROM pessoas WHERE id_usuario = {$idUsuario} OR id_pessoa = {$idPessoaAtual} ORDER BY nome ASC";
}

$pessoas = $embaixada->list($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="page-title mb-1">
            <?php echo $nivel === 'admin' ? 'Pessoas cadastradas' : ($nivel === 'responsavel' ? 'Embaixadores vinculados' : 'Meu cadastro'); ?>
        </h2>
        <p class="text-muted mb-0">Visualização conforme o perfil de acesso.</p>
    </div>

    <?php if ($nivel !== 'embaixador'): ?>
        <a href="index.php?pagina=inserir_pessoa" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Novo cadastro
        </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pessoas)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum cadastro encontrado para este perfil.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pessoas as $index => $pessoa): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($pessoa['nome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $pessoa['tipo'] ?? ''))) ?: '-'; ?></td>
                                <td><?php echo htmlspecialchars($pessoa['telefone'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($pessoa['email'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($pessoa['status'] ?? 'ativo') === 'ativo' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($pessoa['status'] ?? 'ativo'); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="index.php?pagina=inserir_pessoa&id=<?php echo $pessoa['id_pessoa']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="controllers/pessoas/deletar.php?id=<?php echo $pessoa['id_pessoa']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja excluir este cadastro?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
