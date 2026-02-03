<?php
require_once '../functions.php';
verificarAutenticacao();

// Aumenta limite de memória para processar imagens
ini_set('memory_limit', '256M');

$acao = $_POST['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];

// ============================================================================
// 1. SALVAR DADOS DE TEXTO
// ============================================================================
if ($acao === 'salvar_dados') {
    $titulo_perfil = trim($_POST['titulo_perfil'] ?? '');
    $nome          = trim($_POST['nome'] ?? '');
    $bio           = trim($_POST['bio'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $exibir_aliado = isset($_POST['exibir_botao_aliado']) ? 1 : 0;
    
    $estilo_fonte  = $_POST['estilo_fonte'] ?? 'sans';
    $tipo_fundo    = $_POST['tipo_fundo'] ?? 'cor';
    $cor_fundo     = $_POST['cor_fundo'] ?? '#121212';

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET 
            titulo_perfil=?, nome=?, bio=?, telefone=?, exibir_botao_aliado=?, 
            estilo_fonte=?, tipo_fundo=?, cor_fundo=? 
            WHERE id=?");
        
        $stmt->execute([
            $titulo_perfil, $nome, $bio, $telefone, $exibir_aliado, 
            $estilo_fonte, $tipo_fundo, $cor_fundo, 
            $usuario_id
        ]);
        
        $_SESSION['usuario_nome'] = $nome;
        jsonResponse(true, 'Perfil salvo!');
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro ao salvar: ' . $e->getMessage());
    }
}

// ============================================================================
// 2. UPLOAD DE IMAGEM (BASE64 NO BANCO)
// ============================================================================
if ($acao === 'upload_imagem') {
    $tipo = $_POST['tipo_upload'] ?? 'avatar'; // 'avatar' ou 'fundo'
    
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Erro no envio do arquivo.');
    }

    $file = $_FILES['arquivo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        jsonResponse(false, 'Apenas JPG, PNG ou WebP.');
    }

    // Processa e converte para Base64
    $base64Image = converterImagemBase64($file['tmp_name'], $tipo, $ext);

    if ($base64Image) {
        // Salva a string gigante no banco
        $coluna = ($tipo === 'avatar') ? 'foto' : 'imagem_fundo';
        
        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET $coluna = ? WHERE id = ?");
            $stmt->execute([$base64Image, $usuario_id]);
            
            jsonResponse(true, 'Imagem salva!', ['url' => $base64Image]);
        } catch (PDOException $e) {
            jsonResponse(false, 'Erro de banco (Imagem muito grande?): ' . $e->getMessage());
        }
    } else {
        jsonResponse(false, 'Falha ao processar imagem.');
    }
}

/**
 * Redimensiona e retorna string Base64 pronta para o <img src="">
 */
function converterImagemBase64($origem, $tipo, $ext) {
    list($largura, $altura) = getimagesize($origem);
    
    // Cria recurso de imagem
    if ($ext == 'png') $img = imagecreatefrompng($origem);
    elseif ($ext == 'webp') $img = imagecreatefromwebp($origem);
    else $img = imagecreatefromjpeg($origem);

    if (!$img) return false;

    // Definições de Redimensionamento
    if ($tipo === 'avatar') {
        // Quadrado 400x400 (Otimizado)
        $novaLargura = 400; $novaAltura = 400;
        $menorLado = min($largura, $altura);
        $x = ($largura - $menorLado) / 2;
        $y = ($altura - $menorLado) / 2;
        
        $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);
        imagealphablending($novaImg, false);
        imagesavealpha($novaImg, true);
        imagecopyresampled($novaImg, $img, 0, 0, $x, $y, $novaLargura, $novaAltura, $menorLado, $menorLado);
    } else {
        // Background - Max 1024px largura (Otimizado para não estourar o banco)
        $maxLargura = 1024;
        if ($largura > $maxLargura) {
            $ratio = $maxLargura / $largura;
            $novaLargura = $maxLargura;
            $novaAltura = $altura * $ratio;
        } else {
            $novaLargura = $largura;
            $novaAltura = $altura;
        }
        $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);
        imagecopyresampled($novaImg, $img, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
    }

    // Buffer de Saída para capturar os bytes da imagem
    ob_start();
    // Salva como JPEG qualidade 80 (bom balanço tamanho/qualidade) ou PNG se precisar transparência
    if ($ext == 'png') imagepng($novaImg); 
    else imagejpeg($novaImg, null, 80); 
    $imageData = ob_get_clean();

    imagedestroy($img);
    imagedestroy($novaImg);

    // Retorna formato Data URI
    $mime = ($ext == 'png') ? 'image/png' : 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode($imageData);
}