<?php
// =========================================================================
// 1. CONFIGURAÇÃO DE DEBUG E SESSÃO
// =========================================================================
define('DEBUG_MODE', true); // Mude para false em produção

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$debug_log = [];

function logDebug($msg, $dados = null) {
    if (!DEBUG_MODE) return;
    global $debug_log;
    $entry = "[" . date('H:i:s') . "] " . $msg;
    if ($dados) {
        $entry .= " | Dados: " . json_encode($dados);
    }
    $debug_log[] = $entry;
}

// =========================================================================
// 2. CONEXÃO COM BANCO DE DADOS
// =========================================================================
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'linktree_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    logDebug("Conexão PDO realizada.", ["DB" => $db]);
} catch (PDOException $e) {
    logDebug("ERRO CRÍTICO DE CONEXÃO", $e->getMessage());
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die(json_encode(['status' => 'erro', 'msg' => 'Erro de conexão: ' . $e->getMessage()]));
    }
    die("Erro de conexão com o banco de dados.");
}

// =========================================================================
// 3. FUNÇÕES AUXILIARES (AS QUE ESTAVAM FALTANDO)
// =========================================================================

/**
 * Função de Segurança: Sanitiza output HTML
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Função de Segurança: Bloqueia acesso não logado
 */
function verificarAutenticacao() {
    if (!isset($_SESSION['usuario_id'])) {
        // Se for uma requisição AJAX, retorna JSON de erro
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
           header('Content-Type: application/json');
           echo json_encode(['status' => 'erro', 'msg' => 'Sessão expirada', 'redirect' => '../index.php']);
           exit;
        }
        
        // Se for acesso normal, redireciona
        header('Location: ../index.php?erro=acesso_negado');
        exit;
    }
}

/**
 * Retorno Padrão para AJAX
 */
function jsonResponse($sucesso = true, $mensagem = '', $dados = []) {
    global $debug_log;
    
    header('Content-Type: application/json');
    $response = [
        'status' => $sucesso ? 'sucesso' : 'erro',
        'msg'    => $mensagem,
        'dados'  => $dados
    ];

    if (DEBUG_MODE) {
        $response['debug_log'] = $debug_log;
    }

    echo json_encode($response);
    exit;
}
?>