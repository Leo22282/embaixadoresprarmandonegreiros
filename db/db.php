<?php
require_once __DIR__ . '/config.php';

class Financeiro
{
    private $conexao;

    public function __construct()
    {
        global $servidor, $database, $usuario, $senha;
        $this->conexao = new PDO('mysql:host=' . $servidor . ';dbname=' . $database, $usuario, $senha);
        $this->conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function pdo()
    {
        return $this->conexao;
    }

    public function list(string $sql): array
    {
        $resultado = [];

        foreach ($this->conexao->query($sql) as $value) {
            $resultado[] = $value;
        }

        return $resultado;
    }

    public function executador(string $sql, array $dados = []): int
    {
        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($dados);

        return $stmt->rowCount();
    }
}
