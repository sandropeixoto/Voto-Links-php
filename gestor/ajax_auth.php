<?php
// gestor/ajax_auth.php
require_once '../functions.php'; 

// ⚠️ IMPORTANTE: NÃO coloque verificarAutenticacao() aqui!
// Este arquivo precisa ser acessível publicamente para quem não tem login ainda.

// Verifica método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método inválido');
}

$acao = $_POST['acao'] ?? '';

// ============================================================================
// LOGIN
// ============================================================================
if ($acao === 'login') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        jsonResponse(false, 'Preencha todos os campos.');
    }

    // Busca usuário pelo email
    $stmt = $pdo->prepare("SELECT id, nome, senha, usuario FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_slug'] = $user['usuario'];

        jsonResponse(true, 'Login realizado! Redirecionando...', ['redirect' => 'gestor/index.php']);
    } else {
        jsonResponse(false, 'E-mail ou senha incorretos.');
    }
}

// ============================================================================
// CADASTRO
// ============================================================================
if ($acao === 'cadastrar') {
    $nome = trim($_POST['nome'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $usuario = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['usuario'] ?? ''); 
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !$email || !$usuario || strlen($senha) < 6) {
        jsonResponse(false, 'Preencha corretamente. Senha min. 6 caracteres.');
    }

    // --- REGRA DE NOMES PROIBIDOS ---
    $proibidos = ['admin', 'gestor', 'api', 'login', 'dashboard', 'includes', 'assets', 'css', 'js', 'images', 'index', 'p'];
    if (in_array(strtolower($usuario), $proibidos)) {
        jsonResponse(false, 'Este nome de usuário é reservado.');
    }

    // Verifica duplicidade
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR usuario = ?");
    $stmt->execute([$email, $usuario]);
    if ($stmt->rowCount() > 0) {
        jsonResponse(false, 'E-mail ou Usuário já estão em uso.');
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, usuario, senha) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $usuario, $senhaHash]);
        
        $_SESSION['usuario_id'] = $pdo->lastInsertId();
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_slug'] = $usuario;

        jsonResponse(true, 'Conta criada com sucesso!', ['redirect' => 'gestor/index.php']);
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro ao criar conta: ' . $e->getMessage());
    }
}