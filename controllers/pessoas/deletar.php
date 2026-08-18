<?php
require_once '../../db/db.php';

$financeiro = new Financeiro;

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $sql = 'DELETE FROM pessoas WHERE id_pessoa = :id';
    $stmt = $financeiro->pdo()->prepare($sql);
    $stmt->execute([':id' => $id]);
}

header('Location: ../../index.php?pagina=pessoas');
exit;
