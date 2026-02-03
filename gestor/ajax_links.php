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
    jsonResponse(true, 'Links listados', $links);
}

// ============================================================================
// SALVAR LINK (POST - Inserir ou Editar)
// ============================================================================
if ($acao === 'salvar') {
    $id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $titulo = trim($_POST['titulo'] ?? '');
    $url    = trim($_POST['url'] ?? '');
    $icone  = trim($_POST['icone'] ?? ''); // Novo campo

    if (!$titulo || !$url) {
        jsonResponse(false, 'Título e URL são obrigatórios.');
    }

    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }

    try {
        if ($id) {
            // EDITAR
            $stmt = $pdo->prepare("UPDATE links SET titulo=?, url=?, icone=? WHERE id=? AND usuario_id=?");
            $stmt->execute([$titulo, $url, $icone, $id, $usuario_id]);
            $msg = 'Link atualizado!';
        } else {
            // INSERIR (Pega a última ordem para jogar no final)
            $stmtOrdem = $pdo->prepare("SELECT MAX(ordem) as max_ordem FROM links WHERE usuario_id = ?");
            $stmtOrdem->execute([$usuario_id]);
            $row = $stmtOrdem->fetch();
            $proximaOrdem = ($row['max_ordem'] ?? 0) + 1;

            $stmt = $pdo->prepare("INSERT INTO links (usuario_id, titulo, url, icone, ordem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $titulo, $url, $icone, $proximaOrdem]);
            $msg = 'Link criado!';
        }
        jsonResponse(true, $msg);
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro no banco: ' . $e->getMessage());
    }
}

// ============================================================================
// REORDENAR (Drag & Drop)
// ============================================================================
if ($acao === 'reordenar') {
    $listaIds = $_POST['ordem'] ?? []; // Recebe array [3, 1, 5, 2...]
    
    if (is_array($listaIds) && count($listaIds) > 0) {
        try {
            $pdo->beginTransaction();
            foreach ($listaIds as $posicao => $id_link) {
                // Atualiza a posição baseada no índice do array (0, 1, 2...)
                // O WHERE usuario_id garante que ninguém ordene links de outros
                $stmt = $pdo->prepare("UPDATE links SET ordem = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$posicao, $id_link, $usuario_id]);
            }
            $pdo->commit();
            jsonResponse(true, 'Ordem atualizada.');
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, 'Erro ao reordenar.');
        }
    }
}

// ============================================================================
// EXCLUIR LINK
// ============================================================================
if ($acao === 'excluir') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM links WHERE id=? AND usuario_id=?");
        $stmt->execute([$id, $usuario_id]);
        jsonResponse(true, 'Link removido.');
    }
}