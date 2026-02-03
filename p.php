<?php
require_once 'functions.php';

$slug = $_GET['u'] ?? '';
if (!$slug) { header('Location: index.php'); exit; }

// Busca perfil
$stmt = $pdo->prepare("SELECT id, nome, bio, foto FROM usuarios WHERE usuario = ? LIMIT 1");
$stmt->execute([$slug]);
$perfil = $stmt->fetch();

if (!$perfil) {
    http_response_code(404);
    die("<h1>Usuário não encontrado</h1>");
}

// Busca Links (já ordenados)
$stmtLinks = $pdo->prepare("SELECT titulo, url, icone FROM links WHERE usuario_id = ? AND ativo = 1 ORDER BY ordem ASC, id DESC");
$stmtLinks->execute([$perfil['id']]);
$links = $stmtLinks->fetchAll();

$bgBody = '#f8f9fa'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($perfil['nome']); ?> | Voto Links</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: <?php echo $bgBody; ?>;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 50px;
        }
        .profile-container { width: 100%; max-width: 680px; padding: 20px; text-align: center; }
        .avatar { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 2px solid #ddd; }
        .link-btn {
            display: block; width: 100%;
            background-color: #fff; color: #333;
            border: 1px solid #ddd;
            padding: 18px 20px; margin-bottom: 16px;
            border-radius: 50px; text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            position: relative;
        }
        .link-btn:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; }
    </style>
</head>
<body>

    <div class="profile-container">
        <?php if (!empty($perfil['foto'])): ?>
            <img src="<?php echo h($perfil['foto']); ?>" class="avatar">
        <?php else: ?>
            <div class="avatar d-flex align-items-center justify-content-center bg-secondary text-white mx-auto fs-2">
                <?php echo strtoupper(substr($perfil['nome'], 0, 1)); ?>
            </div>
        <?php endif; ?>

        <h1 class="h4 fw-bold">@<?php echo h($slug); ?></h1>
        <?php if (!empty($perfil['bio'])): ?>
            <p class="text-muted"><?php echo h($perfil['bio']); ?></p>
        <?php endif; ?>

        <div class="mt-4">
            <?php foreach ($links as $link): ?>
                <a href="<?php echo h($link['url']); ?>" target="_blank" class="link-btn">
                    <?php if(!empty($link['icone'])): ?>
                        <i class="<?php echo h($link['icone']); ?> btn-icon"></i>
                    <?php endif; ?>
                    
                    <?php echo h($link['titulo']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-5 text-muted small">
            Voto Solutions Linktree
        </div>
    </div>
    <?php include 'includes/debug_footer.php'; ?>
</body>
</html>