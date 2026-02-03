<?php
require_once 'functions.php';
$slug = $_GET['u'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
$stmt->execute([$slug]);
$perfil = $stmt->fetch();

if (!$perfil) die("Não encontrado.");

// Busca Links
$stmtLinks = $pdo->prepare("SELECT titulo, url, icone FROM links WHERE usuario_id = ? AND ativo = 1 ORDER BY ordem ASC, id DESC");
$stmtLinks->execute([$perfil['id']]);
$links = $stmtLinks->fetchAll();

// Estilos
$bgCSS = "background: #121212;";
if ($perfil['tipo_fundo'] === 'cor') {
    $bgCSS = "background: " . $perfil['cor_fundo'] . ";";
} elseif ($perfil['tipo_fundo'] === 'imagem' && !empty($perfil['imagem_fundo'])) {
    // A imagem_fundo agora é uma string Base64, então funciona direto no URL()
    $bgCSS = "background: url('" . $perfil['imagem_fundo'] . "') no-repeat center center fixed; background-size: cover;";
}

$textoCor = (strpos($perfil['cor_fundo'], '#fff') !== false) ? '#000' : '#fff';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($perfil['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { <?php echo $bgCSS; ?> color: <?php echo $textoCor; ?>; min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding-top: 50px; font-family: 'Segoe UI', sans-serif; }
        .profile-container { width: 100%; max-width: 680px; padding: 20px; text-align: center; }
        .avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid rgba(255,255,255,0.2); }
        .link-btn { display: block; width: 100%; background: rgba(255,255,255,0.9); color: #000; padding: 18px; margin-bottom: 16px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: transform 0.2s; position: relative; border: none; }
        .link-btn:hover { transform: scale(1.02); background: #fff; }
        .btn-aliado { background: #fff; color: #000; animation: pulse 2s infinite; text-transform: uppercase; letter-spacing: 1px; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
    </style>
</head>
<body>
    <div class="profile-container">
        <img src="<?php echo $perfil['foto'] ? $perfil['foto'] : 'https://via.placeholder.com/150'; ?>" class="avatar">
        
        <h1 class="h4 fw-bold"><?php echo h($perfil['titulo_perfil'] ?: '@'.$slug); ?></h1>
        <p class="small opacity-75 mb-4"><?php echo h($perfil['bio']); ?></p>

        <?php foreach ($links as $link): ?>
            <a href="<?php echo h($link['url']); ?>" target="_blank" class="link-btn">
                <?php if($link['icone']): ?><i class="<?php echo $link['icone']; ?> fa-lg position-absolute start-0 ms-4 top-50 translate-middle-y"></i><?php endif; ?>
                <?php echo h($link['titulo']); ?>
            </a>
        <?php endforeach; ?>

        <?php if ($perfil['exibir_botao_aliado']): ?>
            <div class="mt-4"><a href="#" class="link-btn btn-aliado">Torne-se um Aliado</a></div>
        <?php endif; ?>
    </div>
</body>
</html>