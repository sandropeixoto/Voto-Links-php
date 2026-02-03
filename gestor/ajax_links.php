<?php
require_once '../functions.php';
verificarAutenticacao();

$acao = $_REQUEST['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// --- LISTAR ---
if ($acao === 'listar') {
    $stmt = $pdo->prepare("SELECT * FROM links WHERE usuario_id = ? ORDER BY ordem ASC, id DESC");
    $stmt->execute([$usuario_id]);
    $links = $stmt->fetchAll();
    jsonResponse(true, 'Listado', $links);
}

// --- SALVAR (NOVO OU EDIÇÃO) ---
if ($acao === 'salvar') {
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $titulo = trim($_POST['titulo'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $icone  = trim($_POST['icone'] ?? '');

    if (!$titulo || !$url) {
        jsonResponse(false, 'Título e URL são obrigatórios.');
    }
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) $url = "https://" . $url;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE links SET titulo=?, url=?, icone=? WHERE id=? AND usuario_id=?");
            $stmt->execute([$titulo, $url, $icone, $id, $usuario_id]);
        } else {
            // Pega ultima ordem
            $stmtOrdem = $pdo->prepare("SELECT MAX(ordem) as max_ordem FROM links WHERE usuario_id = ?");
            $stmtOrdem->execute([$usuario_id]);
            $ordem = ($stmtOrdem->fetch()['max_ordem'] ?? 0) + 1;

            $stmt = $pdo->prepare("INSERT INTO links (usuario_id, titulo, url, icone, ordem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $titulo, $url, $icone, $ordem]);
        }
        jsonResponse(true, 'Salvo!');
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro: ' . $e->getMessage());
    }
}

// --- REORDENAR ---
if ($acao === 'reordenar') {
    $ids = $_POST['ordem'] ?? [];
    if (count($ids) > 0) {
        foreach ($ids as $pos => $id_link) {
            $stmt = $pdo->prepare("UPDATE links SET ordem = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pos, $id_link, $usuario_id]);
        }
        jsonResponse(true, 'Reordenado');
    }
}

// --- EXCLUIR ---
if ($acao === 'excluir') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM links WHERE id=? AND usuario_id=?");
        $stmt->execute([$id, $usuario_id]);
        jsonResponse(true, 'Deletado');
    }
}

// --- TOGGLE ATIVO (VISIBILIDADE) ---
if ($acao === 'toggle_ativo') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $ativo = filter_input(INPUT_POST, 'ativo', FILTER_VALIDATE_INT); // 1 ou 0
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE links SET ativo=? WHERE id=? AND usuario_id=?");
        $stmt->execute([$ativo, $id, $usuario_id]);
        jsonResponse(true, 'Status alterado');
    }
}