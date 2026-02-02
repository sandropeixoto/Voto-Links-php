<?php
// functions.php

// 1. CONFIGURAÇÃO DE DEBUG
// Mude para false quando for para produção
define('DEBUG_MODE', true); 

// Inicializa o array de logs
$debug_log = [];

function logDebug($msg, $dados = null) {
    if (!DEBUG_MODE) return;
    global $debug_log;
    $entry = "[".date('H:i:s')."] " . $msg;
    if ($dados) {
        $entry .= " | Dados: " . json_encode($dados);
    }
    $debug_log[] = $entry;
}

// =========================================================================
// 2. CONEXÃO COM BANCO
// =========================================================================
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'linktree_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    logDebug("Conexão PDO realizada com sucesso.", ["DB" => $db, "Host" => $host]);
} catch (PDOException $e) {
    logDebug("ERRO CRÍTICO DE CONEXÃO", $e->getMessage());
    // Se for AJAX, precisamos retornar JSON mesmo no erro fatal
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die(json_encode(['status' => 'erro', 'msg' => 'Erro de conexão SQL: ' . $e->getMessage()]));
    }
    die("Erro de conexão: " . $e->getMessage());
}

// ... (Mantenha as funções h() e verificarAutenticacao() aqui)

/**
 * Atualização da função jsonResponse para enviar o log de debug junto
 */
function jsonResponse($sucesso = true, $mensagem = '', $dados = []) {
    global $debug_log;
    
    header('Content-Type: application/json');
    $response = [
        'status' => $sucesso ? 'sucesso' : 'erro',
        'msg'    => $mensagem,
        'dados'  => $dados
    ];

    // Anexa o debug se estiver ativo
    if (DEBUG_MODE) {
        $response['debug_log'] = $debug_log;
    }

    echo json_encode($response);
    exit;
}
?>