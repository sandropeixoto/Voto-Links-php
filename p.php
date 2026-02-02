<?php
require_once 'functions.php';

// 1. Captura o usuário da URL (vindo do .htaccess) ou via GET normal
$slug = $_GET['u'] ?? '';

if (!$slug) {
    header('Location: index.php'); // Se não tiver usuário, manda pra home
    exit;
}

// 2. Busca os dados do Usuário
$stmt = $pdo->prepare("SELECT id, nome, bio, foto, cor_fundo FROM usuarios WHERE usuario = ? LIMIT 1");
$stmt->execute([$slug]);
$perfil = $stmt->fetch();

// Se o usuário não existir, mostra 404
if (!$perfil) {
    http_response_code(404);
    die("<h1>Página não encontrada :/</h1><p>O usuário '$slug' não existe.</p><a href='/'>Criar meu Linktree</a>");
}

// 3. Busca os Links ATIVOS desse usuário
$stmtLinks = $pdo->prepare("SELECT titulo, url FROM links WHERE usuario_id = ? AND ativo = 1 ORDER BY ordem ASC, id DESC");
$stmtLinks->execute([$perfil['id']]);
$links = $stmtLinks->fetchAll();

// Definição de cores (fallback se for null)
$bgBody = '#f8f9fa'; 
// Futuramente você pode adicionar cor_fundo na tabela usuarios e usar aqui:
// $bgBody = $perfil['cor_fundo'] ?? '#f8f9fa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($perfil['nome']); ?> | Voto Links</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

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
        .profile-container {
            width: 100%;
            max-width: 680px; /* Largura similar ao Linktree */
            padding: 20px;
            text-align: center;
        }
        .avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
            margin-bottom: 15px;
            background-color: #eee;
        }
        .user-name {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 5px;
            color: #333;
        }
        .user-bio {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }
        /* Estilo dos Botões de Link */
        .link-btn {
            display: block;
            width: 100%;
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            padding: 18px 20px;
            margin-bottom: 16px;
            border-radius: 50px; /* Borda redonda estilo Linktree */
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .link-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background-color: #fafafa;
            color: #000;
            border-color: #ccc;
        }
        /* Responsividade */
        @media (max-width: 576px) {
            .link-btn {
                padding: 16px;
                font-size: 0.9rem;
            }
        }
        .footer {
            margin-top: 40px;
            font-size: 0.8rem;
            color: #aaa;
        }
        .footer a { color: #aaa; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="profile-container">
        <?php if (!empty($perfil['foto'])): ?>
            <img src="<?php echo h($perfil['foto']); ?>" alt="Foto de <?php echo h($perfil['nome']); ?>" class="avatar">
        <?php else: ?>
            <div class="avatar d-flex align-items-center justify-content-center bg-secondary text-white mx-auto fs-2">
                <?php echo strtoupper(substr($perfil['nome'], 0, 1)); ?>
            </div>
        <?php endif; ?>

        <h1 class="user-name">@<?php echo h($slug); ?></h1>
        <?php if (!empty($perfil['bio'])): ?>
            <p class="user-bio"><?php echo h($perfil['bio']); ?></p>
        <?php endif; ?>

        <div class="links-wrapper">
            <?php if (count($links) > 0): ?>
                <?php foreach ($links as $link): ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener noreferrer" class="link-btn">
                        <?php echo h($link['titulo']); ?>
                        </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-light text-muted">
                    Este usuário ainda não cadastrou links.
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Voto Solutions Linktree<br>
            <a href="/">Crie o seu gratuitamente</a></p>
        </div>
    </div>

</body>
</html>