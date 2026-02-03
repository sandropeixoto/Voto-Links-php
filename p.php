<?php
require_once 'functions.php';
$slug = $_GET['u'] ?? '';
// ... (buscas no banco) ...
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
$stmt->execute([$slug]);
$perfil = $stmt->fetch();
// ... (busca links) ...

// Lógica de CSS Dinâmico
$fonteCSS = "font-family: 'Inter', sans-serif;"; // Padrão
if ($perfil['estilo_fonte'] === 'serif') $fonteCSS = "font-family: 'Merriweather', serif;";
if ($perfil['estilo_fonte'] === 'mono')  $fonteCSS = "font-family: 'Roboto Mono', monospace;";
if ($perfil['estilo_fonte'] === 'cursive') $fonteCSS = "font-family: 'Dancing Script', cursive;";

// Lógica de Fundo
$bgCSS = "background-color: #121212;";
if ($perfil['tipo_fundo'] === 'cor') {
    $bgCSS = "background: " . $perfil['cor_fundo'] . ";";
} elseif ($perfil['tipo_fundo'] === 'imagem' && $perfil['imagem_fundo']) {
    $bgCSS = "background: url('" . $perfil['imagem_fundo'] . "') no-repeat center center fixed; background-size: cover;";
}

$textoCor = ($perfil['tipo_fundo'] === 'cor' && $perfil['cor_fundo'] === '#ffffff') ? '#121212' : '#ffffff';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&family=Merriweather:wght@300;700&family=Roboto+Mono:wght@400;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            <?php echo $bgCSS; ?>
            <?php echo $fonteCSS; ?>
            color: <?php echo $textoCor; ?>;
            min-height: 100vh;
            /* ... flex layout ... */
        }
        .btn-aliado {
            background-color: #fff; color: #000; font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
        /* ... restante do CSS ... */
    </style>
</head>
<body>
    <div class="profile-container">
        <img src="<?php echo $perfil['foto'] ? $perfil['foto'] : 'default_avatar.png'; ?>" class="avatar">
        
        <h1 class="h4 fw-bold mt-3"><?php echo h($perfil['titulo_perfil'] ?: '@'.$slug); ?></h1>
        <p class="small opacity-75"><?php echo h($perfil['bio']); ?></p>

        <?php if ($perfil['exibir_botao_aliado']): ?>
            <div class="mt-4">
                <a href="#" class="link-btn btn-aliado">
                    <i class="fa-solid fa-handshake me-2"></i> Torne-se um Aliado
                </a>
                <div class="small mt-2 opacity-50">Junte-se a nossa comunidade</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>