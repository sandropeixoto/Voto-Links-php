<?php
require_once '../functions.php';
verificarAutenticacao();

$usuario_id = $_SESSION['usuario_id'];
$slug = $_SESSION['usuario_slug'];

// Carregar dados
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

// --- CORREÇÃO DO PROTOCOLO (FIX PREVIEW) ---
$protocol = 'http';
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocol = 'https';
}
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
    
    <style>
        /* CSS Dark Mode */
        body { background-color: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .navbar { background-color: #1e1e1e; border-bottom: 1px solid #333; }
        .main-container { max-width: 1400px; margin: 0 auto; padding-top: 30px; }
        .nav-pills .nav-link { color: #aaa; border-radius: 8px; padding: 10px 20px; font-weight: 600; }
        .nav-pills .nav-link.active { background-color: #f8f9fa; color: #121212; }
        
        /* Cards */
        .card-dark { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .form-control-dark { background-color: #2c2c2c; border: 1px solid #444; color: #fff; }
        .form-control-dark:focus { background-color: #2c2c2c; color: #fff; border-color: #8a2be2; box-shadow: none; }
        
        /* Avatar Upload */
        .avatar-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #8a2be2; background: #333; }
        
        /* Preview Celular */
        .phone-mockup { border: 12px solid #000; border-radius: 40px; height: 700px; width: 350px; overflow: hidden; position: relative; background-color: #fff; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 25px; background-color: #000; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; z-index: 10; }
        .preview-iframe { width: 100%; height: 100%; border: none; }
        .col-preview { position: sticky; top: 20px; }

        /* Links */
        .link-card { background-color: #1e1e1e; border: 1px solid #333; border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; align-items: center; }
        .drag-handle { cursor: grab; color: #666; padding-right: 15px; }

        /* Seletores */
        .theme-option { width: 100%; height: 50px; border-radius: 8px; cursor: pointer; border: 2px solid transparent; }
        .theme-option.selected { border-color: #fff; transform: scale(1.05); }
        .icon-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .icon-option { height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 5px; cursor: pointer; color: #ddd; background: #333; }
        .icon-option:hover { background: #8a2be2; color: #fff; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-bolt me-2"></i>Voto Links</a>
            <ul class="nav nav-pills mx-auto" id="mainTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="links-tab" data-bs-toggle="pill" data-bs-target="#tab-links">Links</button></li>
                <li class="nav-item"><button class="nav-link" id="appearance-tab" data-bs-toggle="pill" data-bs-target="#tab-appearance">Aparência</button></li>
            </ul>
            <a href="../index.php?logout=true" class="btn btn-sm btn-outline-secondary">Sair</a>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <div class="row">
            
            <div class="col-lg-7 pb-5">
                <div class="tab-content">
                    
                    <div class="tab-pane fade show active" id="tab-links">
                        <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-4" onclick="abrirModalNovo()" style="background: linear-gradient(90deg, #8a2be2, #4b0082); border: none;">
                            <i class="fa-solid fa-plus me-2"></i> Adicionar Link
                        </button>
                        <div id="lista-links">
                            <div class="text-center text-muted">Carregando links...</div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-appearance">
                        <form id="form-aparencia">
                            <input type="hidden" name="acao" value="salvar_dados">
                            
                            <div class="card-dark">
                                <h5 class="mb-4 text-white">Perfil</h5>
                                <div class="d-flex align-items-center gap-4 mb-4">
                                    <img src="<?php echo $user['foto'] ? $user['foto'] : 'https://via.placeholder.com/150'; ?>" class="avatar-preview" id="preview-avatar">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-primary mb-2" onclick="document.getElementById('upload-avatar').click()">Alterar Foto</button>
                                        <input type="file" id="upload-avatar" class="d-none" accept="image/*" onchange="uploadImagem(this, 'avatar')">
                                    </div>
                                </div>
                                <div class="mb-3"><label class="small text-secondary">Título</label><input type="text" name="titulo_perfil" class="form-control form-control-dark" value="<?php echo h($user['titulo_perfil']); ?>"></div>
                                <div class="mb-3"><label class="small text-secondary">Bio</label><textarea name="bio" class="form-control form-control-dark" rows="2"><?php echo h($user['bio']); ?></textarea></div>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="btnAliado" name="exibir_botao_aliado" <?php echo $user['exibir_botao_aliado'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label text-white" for="btnAliado">Botão "Aliado"</label>
                                </div>
                            </div>

                            <div class="card-dark">
                                <h5 class="mb-3 text-white">Fundo</h5>
                                <input type="hidden" name="tipo_fundo" id="tipo_fundo" value="<?php echo h($user['tipo_fundo']); ?>">
                                <input type="hidden" name="cor_fundo" id="cor_fundo" value="<?php echo h($user['cor_fundo']); ?>">

                                <div class="row g-2 mb-3">
                                    <div class="col-3"><div class="theme-option" style="background:#121212" onclick="selectTheme('cor', '#121212', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background:#ffffff" onclick="selectTheme('cor', '#ffffff', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(45deg, #8a2be2, #4b0082)" onclick="selectTheme('cor', 'linear-gradient(45deg, #8a2be2, #4b0082)', this)"></div></div>
                                    <div class="col-3"><div class="theme-option" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)" onclick="selectTheme('cor', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', this)"></div></div>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-light btn-sm w-100" onclick="document.getElementById('upload-bg').click()">Enviar Imagem de Fundo</button>
                                    <input type="file" id="upload-bg" class="d-none" accept="image/*" onchange="uploadImagem(this, 'fundo')">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-3 fw-bold">Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="col-preview text-center">
                    <div class="small fw-bold text-secondary mb-2">LIVE PREVIEW</div>
                    <div class="phone-mockup">
                        <div class="phone-notch"></div>
                        <iframe src="<?php echo $urlPreview; ?>" class="preview-iframe" id="iframePreview"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLink" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:#1e1e1e; border:1px solid #333; color:#fff;">
                <div class="modal-body">
                    <form id="form-link">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id="link_id">
                        
                        <div class="mb-3">
                            <label class="small text-secondary">Ícone</label>
                            <input type="hidden" name="icone" id="icone_input">
                            <div class="icon-grid">
                                <div class="icon-option" onclick="selectIcon('fa-brands fa-whatsapp')"><i class="fa-brands fa-whatsapp"></i></div>
                                <div class="icon-option" onclick="selectIcon('fa-brands fa-instagram')"><i class="fa-brands fa-instagram"></i></div>
                                <div class="icon-option" onclick="selectIcon('fa-solid fa-envelope')"><i class="fa-solid fa-envelope"></i></div>
                                <div class="icon-option" onclick="selectIcon('fa-solid fa-globe')"><i class="fa-solid fa-globe"></i></div>
                                <div class="icon-option" onclick="selectIcon('')"><i class="fa-solid fa-ban"></i></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-secondary">Título</label>
                            <input type="text" name="titulo" id="titulo" class="form-control form-control-dark" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-secondary">URL</label>
                            <input type="text" name="url" id="url" class="form-control form-control-dark" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salvar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
    // Inicialização
    $(document).ready(function() {
        carregarLinks(); // ⚠️ Agora é chamado explicitamente aqui
    });

    // --- FUNÇÕES DE LINKS ---
    function carregarLinks() {
        $.get('ajax_links.php?acao=listar', function(res) {
            let html = '';
            if(res.dados && res.dados.length > 0) {
                res.dados.forEach(function(link) {
                    let icone = link.icone ? `<i class="${link.icone}"></i>` : `<i class="fa-solid fa-link"></i>`;
                    let opacity = link.ativo == 1 ? '1' : '0.5';
                    
                    html += `
                    <div class="link-card" data-id="${link.id}" style="opacity: ${opacity}">
                        <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
                        <div class="me-3 d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px; background: #2c2c2c; border-radius: 50%;">${icone}</div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-white text-truncate">${link.titulo}</div>
                            <div class="small text-secondary text-truncate">${link.url}</div>
                        </div>
                        <div class="d-flex gap-2 ms-2">
                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="editarLink(${link.id}, '${link.titulo}', '${link.url}', '${link.icone}')"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="excluirLink(${link.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>`;
                });
            } else {
                html = '<div class="text-center text-secondary py-5">Nenhum link criado.</div>';
            }
            $('#lista-links').html(html);

            new Sortable(document.getElementById('lista-links'), {
                handle: '.drag-handle', animation: 150,
                onEnd: function () {
                    var ordem = [];
                    $('#lista-links .link-card').each(function() { ordem.push($(this).data('id')); });
                    $.post('ajax_links.php', { acao: 'reordenar', ordem: ordem }, atualizarPreview);
                }
            });
        }, 'json');
    }

    // Modal e Edição
    function abrirModalNovo() { $('#form-link')[0].reset(); $('#link_id').val(''); new bootstrap.Modal(document.getElementById('modalLink')).show(); }
    function editarLink(id, t, u, i) { $('#link_id').val(id); $('#titulo').val(t); $('#url').val(u); $('#icone_input').val(i); new bootstrap.Modal(document.getElementById('modalLink')).show(); }
    function selectIcon(i) { $('#icone_input').val(i); alert('Ícone selecionado!'); }
    
    $('#form-link').on('submit', function(e) {
        e.preventDefault();
        $.post('ajax_links.php', $(this).serialize(), function(res) {
            $('#modalLink').modal('hide');
            carregarLinks();
            atualizarPreview();
        }, 'json');
    });

    function excluirLink(id) { if(confirm('Excluir?')) $.post('ajax_links.php', {acao:'excluir', id:id}, function(){ carregarLinks(); atualizarPreview(); }, 'json'); }

    // --- FUNÇÕES DE APARÊNCIA ---
    function uploadImagem(input, tipo) {
        if (input.files && input.files[0]) {
            let fd = new FormData();
            fd.append('arquivo', input.files[0]);
            fd.append('acao', 'upload_imagem');
            fd.append('tipo_upload', tipo);

            let btn = $(input).prev();
            let txt = btn.text();
            btn.text('Enviando...').prop('disabled', true);

            $.ajax({
                url: 'ajax_perfil.php', type: 'POST', data: fd,
                contentType: false, processData: false, dataType: 'json',
                success: function(res) {
                    btn.text(txt).prop('disabled', false);
                    if(res.status === 'sucesso') {
                        if(tipo === 'avatar') $('#preview-avatar').attr('src', res.dados.url);
                        else alert('Fundo atualizado!');
                        atualizarPreview();
                    } else alert(res.msg);
                },
                error: function() { btn.text(txt).prop('disabled', false); alert('Erro no envio (Arquivo muito grande?)'); }
            });
        }
    }

    function selectTheme(t, v, el) {
        $('.theme-option').removeClass('selected');
        $(el).addClass('selected');
        $('#tipo_fundo').val(t); $('#cor_fundo').val(v);
    }

    $('#form-aparencia').on('submit', function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Salvando...');
        $.post('ajax_perfil.php', $(this).serialize(), function(res) {
            btn.prop('disabled', false).text('Salvar Alterações');
            if(res.status === 'sucesso') atualizarPreview();
            else alert(res.msg);
        }, 'json');
    });

    function atualizarPreview() {
        document.getElementById('iframePreview').src = document.getElementById('iframePreview').src;
    }
    </script>
</body>
</html>