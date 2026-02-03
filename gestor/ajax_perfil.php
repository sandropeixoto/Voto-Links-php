<?php
require_once '../functions.php';
verificarAutenticacao();

$acao = $_POST['acao'] ?? '';
$usuario_id = $_SESSION['usuario_id'];
$usuario_slug = $_SESSION['usuario_slug'];

// ============================================================================
// 1. SALVAR DADOS DE TEXTO E OPÇÕES
// ============================================================================
if ($acao === 'salvar_dados') {
    $titulo_perfil = trim($_POST['titulo_perfil'] ?? '');
    $nome          = trim($_POST['nome'] ?? '');
    $bio           = trim($_POST['bio'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');
    $exibir_aliado = isset($_POST['exibir_botao_aliado']) ? 1 : 0;
    
    // Aparência
    $estilo_fonte  = $_POST['estilo_fonte'] ?? 'sans';
    $tipo_fundo    = $_POST['tipo_fundo'] ?? 'cor'; // 'cor' ou 'imagem'
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

        // Atualiza sessão se mudou o nome
        $_SESSION['usuario_nome'] = $nome;

        jsonResponse(true, 'Perfil atualizado com sucesso!');
    } catch (PDOException $e) {
        jsonResponse(false, 'Erro ao salvar: ' . $e->getMessage());
    }
}

// ============================================================================
// 2. UPLOAD DE IMAGEM (AVATAR OU FUNDO)
// ============================================================================
if ($acao === 'upload_imagem') {
    $tipo = $_POST['tipo_upload'] ?? 'avatar'; // 'avatar' ou 'fundo'
    
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, 'Nenhum arquivo enviado ou erro no upload.');
    }

    $file = $_FILES['arquivo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    // Validações
    if (!in_array($ext, $allowed)) {
        jsonResponse(false, 'Formato inválido. Use JPG, PNG ou WebP.');
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        jsonResponse(false, 'Arquivo muito grande. Máximo 5MB.');
    }

    // Nome Único: slug_tipo_random.ext
    $novoNome = $usuario_slug . '_' . $tipo . '_' . uniqid() . '.' . $ext;
    $caminhoRelativo = 'uploads/' . $novoNome;
    $caminhoAbsoluto = __DIR__ . '/../' . $caminhoRelativo;

    // Processamento da Imagem (Redimensionar e Cortar)
    if (processarImagem($file['tmp_name'], $caminhoAbsoluto, $tipo, $ext)) {
        // Salva caminho no banco
        $coluna = ($tipo === 'avatar') ? 'foto' : 'imagem_fundo';
        
        // Remove imagem antiga se existir (opcional, para limpar lixo)
        // ... (lógica de limpeza pode ser adicionada aqui)

        $stmt = $pdo->prepare("UPDATE usuarios SET $coluna = ? WHERE id = ?");
        $stmt->execute([$caminhoRelativo, $usuario_id]);

        jsonResponse(true, 'Imagem atualizada!', ['url' => '../' . $caminhoRelativo]);
    } else {
        jsonResponse(false, 'Erro ao processar imagem.');
    }
}

/**
 * Função Auxiliar para Redimensionar Imagens usando GD
 */
function processarImagem($origem, $destino, $tipo, $ext) {
    list($largura, $altura) = getimagesize($origem);
    
    // Cria imagem da fonte
    if ($ext == 'png') $img = imagecreatefrompng($origem);
    elseif ($ext == 'webp') $img = imagecreatefromwebp($origem);
    else $img = imagecreatefromjpeg($origem);

    if (!$img) return false;

    // Definições de tamanho alvo
    if ($tipo === 'avatar') {
        $novaLargura = 512;
        $novaAltura = 512;
        // Lógica de corte quadrado (Crop)
        $menorLado = min($largura, $altura);
        $x = ($largura - $menorLado) / 2;
        $y = ($altura - $menorLado) / 2;
        
        $novaImg = imagecreatetruecolor($novaLargura, $novaAltura);
        
        // Preserva transparência PNG/WebP
        imagealphablending($novaImg, false);
        imagesavealpha($novaImg, true);

        imagecopyresampled($novaImg, $img, 0, 0, $x, $y, $novaLargura, $novaAltura, $menorLado, $menorLado);

    } else {
        // Background (Apenas redimensiona se for gigante, mas mantém proporção)
        $maxLargura = 1920;
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

    // Salva no destino
    if ($ext == 'png') imagepng($novaImg, $destino, 8);
    elseif ($ext == 'webp') imagewebp($novaImg, $destino, 90);
    else imagejpeg($novaImg, $destino, 90);

    imagedestroy($img);
    imagedestroy($novaImg);
    return true;
}