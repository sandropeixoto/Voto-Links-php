<?php
require_once '../functions.php';
verificarAutenticacao();

// Garante memória para processar imagens
ini_set('memory_limit', '256M');

$acao = $_POST['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// --- SALVAR DADOS DE TEXTO ---
if ($acao === 'salvar_dados') {
    // ... (Mesma lógica de antes, mantida para brevidade) ...
    $titulo_perfil = trim($_POST['titulo_perfil'] ?? '');
    $nome          = trim($_POST['nome'] ?? '');
    $bio           = trim($_POST['bio'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $exibir_aliado = isset($_POST['exibir_botao_aliado']) ? 1 : 0;
    
    $estilo_fonte  = $_POST['estilo_fonte'] ?? 'sans';
    $tipo_fundo    = $_POST['tipo_fundo'] ?? 'cor';
    $cor_fundo     = $_POST['cor_fundo'] ?? '#ffffff';

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET 
            titulo_perfil=?, nome=?, bio=?, telefone=?, exibir_botao_aliado=?, 
            estilo_fonte=?, tipo_fundo=?, cor_fundo=? 
            WHERE id=?");
        $stmt->execute([$titulo_perfil, $nome, $bio, $telefone, $exibir_aliado, $estilo_fonte, $tipo_fundo, $cor_fundo, $usuario_id]);
        
        $_SESSION['usuario_nome'] = $nome;
        jsonResponse(true, 'Perfil salvo!');
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro ao salvar: ' . $e->getMessage());
    }
}

// --- UPLOAD DE IMAGEM ---
if ($acao === 'upload_imagem') {
    $tipo = $_POST['tipo_upload'] ?? 'avatar';
    
    // Verifica erros do PHP antes de tudo
    if (!isset($_FILES['arquivo'])) {
        jsonResponse(false, 'Nenhum arquivo recebido.');
    }
    
    $erro = $_FILES['arquivo']['error'];
    if ($erro !== UPLOAD_ERR_OK) {
        $msg = 'Erro desconhecido no upload.';
        if ($erro == UPLOAD_ERR_INI_SIZE) $msg = 'O arquivo excede o limite do servidor (10MB).';
        if ($erro == UPLOAD_ERR_FORM_SIZE) $msg = 'O arquivo excede o limite do formulário.';
        if ($erro == UPLOAD_ERR_PARTIAL)   $msg = 'O upload foi interrompido.';
        if ($erro == UPLOAD_ERR_NO_FILE)   $msg = 'Nenhum arquivo foi enviado.';
        jsonResponse(false, $msg);
    }

    $file = $_FILES['arquivo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        jsonResponse(false, 'Formato inválido. Use JPG, PNG ou WebP.');
    }

    $base64Image = converterImagemBase64($file['tmp_name'], $tipo, $ext);

    if ($base64Image) {
        $coluna = ($tipo === 'avatar') ? 'foto' : 'imagem_fundo';
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET $coluna = ? WHERE id = ?");
            $stmt->execute([$base64Image, $usuario_id]);
            jsonResponse(true, 'Imagem salva com sucesso!', ['url' => $base64Image]);
        } catch (PDOException $e) {
            jsonResponse(false, 'Erro ao salvar no banco (Imagem muito complexa). Tente uma menor.');
        }
    } else {
        jsonResponse(false, 'Não foi possível processar a imagem.');
    }
}

// ... (Função converterImagemBase64 mantida igual ao passo anterior) ...
function converterImagemBase64($origem, $tipo, $ext) {
    list($largura, $altura) = getimagesize($origem);
    if ($ext == 'png') $img = imagecreatefrompng($origem);
    elseif ($ext == 'webp') $img = imagecreatefromwebp($origem);
    else $img = imagecreatefromjpeg($origem);
    if (!$img) return false;

    if ($tipo === 'avatar') {
        $novaLargura = 400; $novaAltura = 400; // Otimizado
        $menorLado = min($largura, $altura);
        $x = ($largura - $menorLado) / 2;
        $y = ($altura - $menorLado) / 2;
        $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);
        imagealphablending($novaImg, false);
        imagesavealpha($novaImg, true);
        imagecopyresampled($novaImg, $img, 0, 0, $x, $y, $novaLargura, $novaAltura, $menorLado, $menorLado);
    } else {
        $maxLargura = 1024; // Otimizado
        if ($largura > $maxLargura) {
            $ratio = $maxLargura / $largura;
            $novaLargura = $maxLargura;
            $novaAltura = $altura * $ratio;
        } else {
            $novaLargura = $largura; $novaAltura = $altura;
        }
        $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresampled($novaImg, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
    }
    ob_start();
    if ($ext == 'png') imagepng($novaImg); else imagejpeg($novaImg, null, 80); 
    $imageData = ob_get_clean();
    imagedestroy($img); imagedestroy($novaImg);
    $mime = ($ext == 'png') ? 'image/png' : 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode($imageData);
}
?>