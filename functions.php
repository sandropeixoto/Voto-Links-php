<?php
// =========================================================================
// 1. CONFIGURAÇÃO E CONEXÃO (Seu padrão estabelecido)
// =========================================================================

// Define o fuso horário (ajuste conforme sua região)
date_default_timezone_set('America/Sao_Paulo');

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do Banco (lê variáveis de ambiente ou usa padrão local)
$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'linktree_db'; // Nome do banco que criamos
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Em produção, não exiba o erro detalhado para o usuário
    die("Erro de conexão: " . $e->getMessage());
}

// =========================================================================
// 2. FUNÇÕES DE SEGURANÇA E UTILITÁRIOS
// =========================================================================

/**
 * Protege contra XSS (Cross-Site Scripting) ao exibir dados na tela.
 * Use sempre que der um echo em dados vindos do banco.
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se o usuário está logado.
 * Deve ser chamada no topo de todos os arquivos dentro da pasta /gestor.
 */
function verificarAutenticacao() {
    if (!isset($_SESSION['usuario_id'])) {
        // Redireciona para a raiz (login) se tentar acessar o painel direto
        header('Location: ../index.php?erro=acesso_negado');
        exit;
    }
}

/**
 * Padroniza o retorno para requisições AJAX (Bootstrap/jQuery).
 */
function jsonResponse($sucesso = true, $mensagem = '', $dados = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $sucesso ? 'sucesso' : 'erro',
        'msg'    => $mensagem,
        'dados'  => $dados
    ]);
    exit;
}