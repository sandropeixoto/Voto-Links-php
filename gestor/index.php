<?php
require_once '../functions.php';
verificarAutenticacao();

$usuario_id = $_SESSION['usuario_id'];
$slug = $_SESSION['usuario_slug'];

// Carregar dados completos do usuário para preencher o form de aparência
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

// Protocolo seguro
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') $protocol = 'https';
$urlPreview = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/p.php?u=" . $slug;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Voto Links</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&family=Merriweather:wght@300;700&family=Roboto+Mono:wght@400;700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">

    <style>
        /* (Mantenha o CSS Dark Mode anterior aqui) */
        body { background-color: #121212; color: #e0e0e0; font-family: 'Inter', sans-serif; }
        .navbar { background-color: #1e1e1e; border-bottom: 1px solid #333; }
        .main-container { max-width: 1400px; margin: 0 auto; padding-top: 30px; }
        
        /* Abas de Navegação */
        .nav-pills .nav-link { color: #aaa; border-radius: 8px; padding: 10px 20px; font-weight: 600; }
        .nav-pills .nav-link.active { background-color: #f8f9fa; color: #121212; }
        
        /* Estilos do Formulario de Aparencia */
        .card-dark { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .form-control-dark { background-color: #2c2c2c; border: 1px solid #444; color: #fff; }
        .form-control-dark:focus { background-color: #2c2c2c; color: #fff; border-color: #8a2be2; box-shadow: none; }
        
        /* Seletores de Tema */
        .theme-option { width: 100%; height: 60px; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
        .theme-option.selected { border-color: #fff; transform: scale(1.05); }
        
        /* Tipografia */
        .font-option { border: 1px solid #444; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.2s; background: #2c2c2c; }
        .font-option.selected { border-color: #8a2be2; background: #3a2a4a; }

        /* Avatar Upload */
        .avatar-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #8a2be2; }
        
        /* Celular Preview */
        .phone-mockup { border: 12px solid #000; border-radius: 40px; height: 750px; width: 370px; overflow: hidden; position: relative; background-color: #fff; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 25px; background-color: #000; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; z-index: 10; }
        .preview-iframe { width: 100%; height: 100%; border: none; }
        .col-preview { position: sticky; top: 20px; height: calc(100vh - 100px); display: flex; justify-content: center; }
        
        /* Link Cards (CSS Anterior) */
        .link-card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; align-items: center; }
        .drag-handle { cursor: grab; color: #666; padding-right: 15px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-bolt me-2"></i>Voto Links</a>
            
            <ul class="nav nav-pills mx-auto" id="mainTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="links-tab" data-bs-toggle="pill" data-bs-target="#tab-links" type="button">
                        <i class="fa-solid fa-link me-2"></i>Links
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="appearance-tab" data-bs-toggle="pill" data-bs-target="#tab-appearance" type="button">
                        <i class="fa-solid fa-palette me-2"></i>Aparência
                    </button>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <a href="<?php echo $urlPreview; ?>" target="_blank" class="btn btn-sm btn-outline-light me-3 d-md-none">Ver <i class="fa-solid fa-external-link-alt"></i></a>
                <a href="../index.php?logout=true" class="btn btn-sm btn-outline-secondary">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <div class="row">
            
            <div class="col-lg-7 pb-5">
                <div class="tab-content" id="mainTabContent">
                    
                    <div class="tab-pane fade show active" id="tab-links">
                        <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-4" onclick="abrirModalNovo()" style="background: linear-gradient(90deg, #8a2be2, #4b0082); border: none;">
                            <i class="fa-solid fa-plus me-2"></i> Adicionar Novo Link
                        </button>
                        <div id="lista-links"></div> </div>

                    <div class="tab-pane fade" id="tab-appearance">
                        <form id="form-aparencia" enctype="multipart/form-data">
                            <input type="hidden" name="acao" value="salvar_dados">
                            
                            <div class="card-dark">
                                <h5 class="mb-4 text-white">Perfil</h5>
                                <div class="d-flex align-items-center gap-4 mb-4">
                                    <div class="position-relative">
                                        <img src="<?php echo $user['foto'] ? '../'.$user['foto'] : 'https://via.placeholder.com/150'; ?>" class="avatar-preview" id="preview-avatar">
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-primary mb-2" onclick="document.getElementById('upload-avatar').click()">Carregar Foto</button>
                                        <input type="file" id="upload-avatar" class="d-none" accept="image/*" onchange="uploadImagem(this, 'avatar')">
                                        <div class="text-secondary small">Recomendado: 512x512px (JPG, PNG)</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Título do Perfil</label>
                                    <input type="text" name="titulo_perfil" class="form-control form-control-dark" value="<?php echo h($user['titulo_perfil']); ?>" placeholder="@seuusuario">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Nome de Exibição</label>
                                    <input type="text" name="nome" class="form-control form-control-dark" value="<?php echo h($user['nome']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Bio</label>
                                    <textarea name="bio" class="form-control form-control-dark" rows="3"><?php echo h($user['bio']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Celular / WhatsApp</label>
                                    <input type="text" name="telefone" class="form-control form-control-dark" value="<?php echo h($user['telefone']); ?>">
                                </div>
                                
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="btnAliado" name="exibir_botao_aliado" <?php echo $user['exibir_botao_aliado'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-white" for="btnAliado">Botão "Torne-se um Aliado"</label>
                                    <div class="text-secondary small">Exibe um botão de destaque para capturar contatos.</div>
                                </div>
                            </div>

                            <div class="card-dark">
                                <h5 class="mb-3 text-white">Tipografia</h5>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="font-option <?php echo $user['estilo_fonte']=='sans'?'selected':''; ?>" onclick="selectFont('sans', this)">
                                            <div class="fw-bold fs-5" style="font-family: 'Inter', sans-serif;">Aa</div>
                                            <div class="small text-secondary">Moderno</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="font-option <?php echo $user['estilo_fonte']=='serif'?'selected':''; ?>" onclick="selectFont('serif', this)">
                                            <div class="fw-bold fs-5" style="font-family: 'Merriweather', serif;">Aa</div>
                                            <div class="small text-secondary">Clássico</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="font-option <?php echo $user['estilo_fonte']=='mono'?'selected':''; ?>" onclick="selectFont('mono', this)">
                                            <div class="fw-bold fs-5" style="font-family: 'Roboto Mono', monospace;">Aa</div>
                                            <div class="small text-secondary">Técnico</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="estilo_fonte" id="input_fonte" value="<?php echo h($user['estilo_fonte']); ?>">
                                </div>
                            </div>

                            <div class="card-dark">
                                <h5 class="mb-3 text-white">Tema da Página</h5>
                                <input type="hidden" name="tipo_fundo" id="tipo_fundo" value="<?php echo h($user['tipo_fundo']); ?>">
                                <input type="hidden" name="cor_fundo" id="cor_fundo" value="<?php echo h($user['cor_fundo']); ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-3"><div class="theme-option" style="background:#121212" onclick="selectTheme('cor', '#121212', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background:#ffffff" onclick="selectTheme('cor', '#ffffff', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background:#2ecc71" onclick="selectTheme('cor', '#2ecc71', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background:#3498db" onclick="selectTheme('cor', '#3498db', this)"></div></div>
                                    
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(45deg, #8a2be2, #4b0082)" onclick="selectTheme('cor', 'linear-gradient(45deg, #8a2be2, #4b0082)', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(to right, #ff416c, #ff4b2b)" onclick="selectTheme('cor', 'linear-gradient(to right, #ff416c, #ff4b2b)', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(to top, #09203f 0%, #537895 100%)" onclick="selectTheme('cor', 'linear-gradient(to top, #09203f 0%, #537895 100%)', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)" onclick="selectTheme('cor', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', this)"></div></div>
                                </div>

                                <div class="border-top border-secondary pt-3 mt-3">
                                    <label class="form-label text-white mb-2">Ou imagem personalizada:</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <button type="button" class="btn btn-outline-light btn-sm" onclick="document.getElementById('upload-bg').click()">Enviar Fundo</button>
                                        <input type="file" id="upload-bg" class="d-none" accept="image/*" onchange="uploadImagem(this, 'fundo')">
                                        <?php if($user['tipo_fundo'] == 'imagem'): ?>
                                            <span class="badge bg-success" id="bg-status">Imagem ativa</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success py-3 fw-bold fs-5">Salvar Personalização</button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>

            <div class="col-lg-5 col-preview">
                <div class="text-center mb-2 text-secondary small fw-bold">LIVE PREVIEW</div>
                <div class="phone-mockup">
                    <div class="phone-notch"></div>
                    <iframe src="<?php echo $urlPreview; ?>" class="preview-iframe" id="iframePreview"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Scripts da Aba Aparência
        
        // 1. Upload de Imagens (Avatar/Bg)
        function uploadImagem(input, tipo) {
            if (input.files && input.files[0]) {
                let formData = new FormData();
                formData.append('arquivo', input.files[0]);
                formData.append('acao', 'upload_imagem');
                formData.append('tipo_upload', tipo);

                // Feedback visual
                let btn = $(input).prev();
                let txtOriginal = btn.text();
                btn.text('Enviando...').prop('disabled', true);

                $.ajax({
                    url: 'ajax_perfil.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        btn.text(txtOriginal).prop('disabled', false);
                        if (res.status === 'sucesso') {
                            if(tipo === 'avatar') {
                                $('#preview-avatar').attr('src', res.dados.url);
                            } else {
                                alert('Fundo carregado! Clique em Salvar para aplicar.');
                                selectTheme('imagem', '', null); // Marca como imagem
                            }
                            atualizarPreview();
                        } else {
                            alert(res.msg);
                        }
                    }
                });
            }
        }

        // 2. Seleção de Fonte
        function selectFont(font, el) {
            $('.font-option').removeClass('selected');
            $(el).addClass('selected');
            $('#input_fonte').val(font);
        }

        // 3. Seleção de Tema
        function selectTheme(tipo, valor, el) {
            $('.theme-option').removeClass('selected');
            if(el) $(el).addClass('selected');
            
            $('#tipo_fundo').val(tipo);
            if(tipo === 'cor') $('#cor_fundo').val(valor);
        }

        // 4. Salvar Dados Gerais
        $('#form-aparencia').on('submit', function(e) {
            e.preventDefault();
            let btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Salvando...');

            $.post('ajax_perfil.php', $(this).serialize(), function(res) {
                btn.prop('disabled', false).text('Salvar Personalização');
                if(res.status === 'sucesso') {
                    atualizarPreview();
                } else {
                    alert(res.msg);
                }
            }, 'json');
        });

        function atualizarPreview() {
            document.getElementById('iframePreview').src = document.getElementById('iframePreview').src;
        }
        
        // Carrega links ao iniciar (se estiver na aba links)
        // ... (seu carregarLinks() anterior)
    </script>
</body>
</html>