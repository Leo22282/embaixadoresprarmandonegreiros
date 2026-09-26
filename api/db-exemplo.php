<?php
/**
 * db-exemplo.php - Modelo de Conexao Segura com MySQL via PDO
 * Copie este arquivo para db.php na mesma pasta e configure suas credenciais.
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

// Configuracoes do Banco de Dados
$host    = 'localhost'; // Na Hostinger geralmente e 'localhost'
$db      = 'NOME_DO_BANCO';
$user    = 'USUARIO_DO_BANCO';
$pass    = 'SENHA_DO_BANCO';
$charset = 'utf8mb4';

// Opcional: Se existir config.php legado na pasta db/, pode carregar as variaveis automaticamente
if (file_exists(__DIR__ . '/../db/config.php')) {
    include_once __DIR__ . '/../db/config.php';
    if (!empty($servidor)) $host = $servidor;
    if (!empty($database)) $db   = $database;
    if (!empty($usuario))  $user = $usuario;
    if (isset($senha))     $pass = $senha;
}

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
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$db;charset=$charset", $user, $pass, $options);
    } catch (PDOException $e2) {
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
            'detalhe' => $erro_conexao ?: 'Verifique suas credenciais no arquivo api/db.php.'
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