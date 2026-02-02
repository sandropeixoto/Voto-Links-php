<?php
require_once '../functions.php';
verificarAutenticacao();

$acao = $_REQUEST['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// ============================================================================
// LISTAR LINKS (GET)
// ============================================================================
if ($acao === 'listar') {
    $stmt = $pdo->prepare("SELECT * FROM links WHERE usuario_id = ? ORDER BY ordem ASC, id DESC");
    $stmt->execute([$usuario_id]);
    $links = $stmt->fetchAll();
    
    // Retornamos os dados puros para o JS montar a tabela
    jsonResponse(true, 'Links listados', $links);
}

// ============================================================================
// SALVAR LINK (POST - Inserir ou Editar)
// ============================================================================
if ($acao === 'salvar') {
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $titulo = trim($_POST['titulo'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $ordem  = filter_input(INPUT_POST, 'ordem', FILTER_VALIDATE_INT) ?: 0;

    if (!$titulo || !$url) {
        jsonResponse(false, 'Título e URL são obrigatórios.');
    }

    // Adiciona http se o usuário esqueceu
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }

    try {
        if ($id) {
            // -- ATUALIZAR (UPDATE) --
            // O WHERE usuario_id = ? garante que ele só edita o SEU próprio link
            $stmt = $pdo->prepare("UPDATE links SET titulo=?, url=?, ordem=? WHERE id=? AND usuario_id=?");
            $stmt->execute([$titulo, $url, $ordem, $id, $usuario_id]);
            $msg = 'Link atualizado com sucesso!';
        } else {
            // -- INSERIR (INSERT) --
            $stmt = $pdo->prepare("INSERT INTO links (usuario_id, titulo, url, ordem) VALUES (?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $titulo, $url, $ordem]);
            $msg = 'Link criado com sucesso!';
        }
        jsonResponse(true, $msg);

    } catch (PDOException $e) {
        jsonResponse(false, 'Erro no banco: ' . $e->getMessage());
    }
}

// ============================================================================
// EXCLUIR LINK (POST)
// ============================================================================
if ($acao === 'excluir') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if ($id) {
        // Novamente, WHERE usuario_id garante segurança
        $stmt = $pdo->prepare("DELETE FROM links WHERE id=? AND usuario_id=?");
        $stmt->execute([$id, $usuario_id]);
        jsonResponse(true, 'Link removido.');
    }
}