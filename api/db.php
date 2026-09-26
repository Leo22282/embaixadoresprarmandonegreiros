<?php
/**
 * db.php - Conexao Segura com MySQL via PDO
 * As credenciais sao lidas automaticamente do arquivo db/.env
 */

declare(strict_types=1);

// Garante que o PHP nunca imprima tags HTML de erro que quebrem o JSON no frontend
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Cabecalhos HTTP para APIs REST
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Trata requisicoes OPTIONS (Preflight)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Carrega as variaveis do arquivo db/.env se existir
$envFile = __DIR__ . '/../db/.env';
if (file_exists($envFile)) {
    $linhas = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

// 2. Se existir config.php legado na pasta db/, permite fallback
if (file_exists(__DIR__ . '/../db/config.php')) {
    include_once __DIR__ . '/../db/config.php';
}

$host    = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($servidor ?? 'localhost'));
$db      = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($database ?? ''));
$user    = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($usuario ?? ''));
$pass    = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($senha ?? ''));
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 5,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$pdo = null;
$erro_conexao = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (PDOException $e1) {
    if ($host !== 'localhost') {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=$db;charset=$charset", $user, $pass, $options);
        } catch (PDOException $e2) {
            $erro_conexao = $e1->getMessage();
        }
    } else {
        $erro_conexao = $e1->getMessage();
    }
}

/**
 * Utilitario para garantir que a conexao PDO esta ativa
 */
function garantirConexaoPdo(): PDO {
    global $pdo, $erro_conexao;
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Falha na conexao com o banco MySQL.',
            'detalhe' => $erro_conexao ?: 'Verifique o arquivo db/.env.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $pdo;
}

/**
 * Utilitario para decodificar JSON do POST
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}