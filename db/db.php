<?php
// Carrega configuracoes do db/.env se existir
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $linhas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#')) continue;
        if (strpos($linha, '=') !== false) {
            [$k, $v] = explode('=', $linha, 2);
            $k = trim($k);
            $v = trim($v);
            if ((str_starts_with($v, '"') && str_ends_with($v, '"')) ||
                (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                $v = substr($v, 1, -1);
            }
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

// Compatibilidade: se existir config.php legado, mantem suporte
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$servidor     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($servidor ?? 'localhost'));
$database     = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($database ?? ''));
$usuario      = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($usuario ?? ''));
$senha        = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($senha ?? ''));
$pastaImagens = $pastaImagens ?? (__DIR__ . '/../images/');
$urlImagens   = $urlImagens ?? '/embaixadaarmandonegreiros/images/';

class Embaixada
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