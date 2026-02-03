<?php
require_once '../functions.php';
verificarAutenticacao();

$usuario_id = $_SESSION['usuario_id'];
$slug = $_SESSION['usuario_slug'];

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

// Fix HTTPS preview
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Merriweather:wght@400;700&family=Roboto+Mono:wght@400;700&family=Dancing+Script:wght@700&family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* TEMA CLARO (LIGHT MODE) */
        body { background-color: #f0f2f5; color: #333; font-family: 'Segoe UI', sans-serif; }
        
        /* Navbar Clean */
        .navbar { background-color: #ffffff; border-bottom: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .navbar-brand { color: #333 !important; font-weight: 700; }
        
        /* Containers */
        .main-container { max-width: 1400px; margin: 0 auto; padding-top: 30px; }
        
        /* Cards */
        .card-panel { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        
        /* Inputs Light */
        .form-control { background-color: #f8f9fa; border: 1px solid #dee2e6; color: #333; }
        .form-control:focus { background-color: #fff; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.1); }
        .form-label { font-weight: 600; font-size: 0.85rem; color: #555; margin-bottom: 8px; }

        /* Abas */
        .nav-pills .nav-link { color: #666; font-weight: 600; padding: 10px 20px; border-radius: 50px; }
        .nav-pills .nav-link.active { background-color: #0d6efd; color: #fff; }

        /* Avatar */
        .avatar-wrapper { position: relative; width: 100px; height: 100px; margin: 0 auto; }
        .avatar-preview { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: #eee; }
        .btn-upload-avatar { position: absolute; bottom: 0; right: 0; background: #0d6efd; color: #fff; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; cursor: pointer; }

        /* Link Cards */
        .link-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; align-items: center; transition: all 0.2s; }
        .link-card:hover { border-color: #0d6efd; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .drag-handle { cursor: grab; color: #adb5bd; padding-right: 15px; }

        /* Preview Celular */
        .phone-mockup { border: 12px solid #212529; border-radius: 40px; height: 720px; width: 360px; overflow: hidden; position: relative; background-color: #fff; margin: 0 auto; box-shadow: 0 10px 40px rgba(0,0,0,0.15); }
        .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 140px; height: 25px; background-color: #212529; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; z-index: 10; }
        .col-preview { position: sticky; top: 20px; }

        /* Seletores Visuais (Grid) */
        .style-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .theme-option, .font-option { 
            cursor: pointer; border-radius: 8px; border: 2px solid transparent; 
            transition: all 0.2s; position: relative; overflow: hidden;
        }
        .theme-option { height: 60px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .font-option { padding: 15px; background: #f8f9fa; border-color: #e0e0e0; text-align: center; }
        
        .theme-option:hover, .font-option:hover { transform: translateY(-2px); }
        .theme-option.selected, .font-option.selected { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.2); }
        
        /* Icone de Check na seleção */
        .selected::after {
            content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.5); font-size: 1.2rem;
        }
        .font-option.selected::after { color: #0d6efd; top: 10px; left: auto; right: 5px; transform: none; font-size: 0.8rem; text-shadow: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-bolt me-2 text-primary"></i>Voto Links</a>
            <ul class="nav nav-pills mx-auto" id="mainTab" role="tablist">
                <li class="nav-item"><button class="nav-link active" id="links-tab" data-bs-toggle="pill" data-bs-target="#tab-links">Meus Links</button></li>
                <li class="nav-item"><button class="nav-link" id="appearance-tab" data-bs-toggle="pill" data-bs-target="#tab-appearance">Personalizar</button></li>
            </ul>
            <a href="../index.php?logout=true" class="btn btn-sm btn-outline-secondary">Sair</a>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <div class="row">
            
            <div class="col-lg-7 pb-5">
                <div class="tab-content">
                    
                    <div class="tab-pane fade show active" id="tab-links">
                        <button class="btn btn-primary w-100 py-3 rounded-pill fw-bold mb-4 shadow-sm" onclick="abrirModalNovo()">
                            <i class="fa-solid fa-plus me-2"></i> Adicionar Novo Link
                        </button>
                        <div id="lista-links">
                            <div class="text-center text-muted py-5"><i class="fa-solid fa-circle-notch fa-spin fs-2"></i></div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-appearance">
                        <form id="form-aparencia">
                            <input type="hidden" name="acao" value="salvar_dados">
                            
                            <div class="card-panel">
                                <h5 class="mb-4">Perfil</h5>
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="avatar-wrapper">
                                            <img src="<?php echo $user['foto'] ? $user['foto'] : 'https://via.placeholder.com/150'; ?>" class="avatar-preview" id="preview-avatar">
                                            <label class="btn-upload-avatar" for="upload-avatar"><i class="fa-solid fa-camera"></i></label>
                                            <input type="file" id="upload-avatar" class="d-none" accept="image/*" onchange="uploadImagem(this, 'avatar')">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-2">
                                            <label class="form-label">Título do Perfil</label>
                                            <input type="text" name="titulo_perfil" class="form-control" value="<?php echo h($user['titulo_perfil']); ?>" placeholder="@seuusuario">
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label">Bio (Descrição)</label>
                                            <textarea name="bio" class="form-control" rows="2" placeholder="Conte um pouco sobre você..."><?php echo h($user['bio']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="btnAliado" name="exibir_botao_aliado" <?php echo $user['exibir_botao_aliado'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="btnAliado">Exibir Botão "Aliado" (Destaque)</label>
                                </div>
                            </div>

                            <div class="card-panel">
                                <h5 class="mb-3">Fontes</h5>
                                <input type="hidden" name="estilo_fonte" id="input_fonte" value="<?php echo h($user['estilo_fonte']); ?>">
                                <div class="style-grid" id="container-fontes">
                                    </div>
                            </div>

                            <div class="card-panel">
                                <h5 class="mb-3">Plano de Fundo</h5>
                                <input type="hidden" name="tipo_fundo" id="tipo_fundo" value="<?php echo h($user['tipo_fundo']); ?>">
                                <input type="hidden" name="cor_fundo" id="cor_fundo" value="<?php echo h($user['cor_fundo']); ?>">

                                <div class="style-grid" id="container-temas">
                                    </div>

                                <div class="mt-4 pt-3 border-top">
                                    <label class="form-label">Ou envie sua própria imagem:</label>
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="document.getElementById('upload-bg').click()">
                                        <i class="fa-regular fa-image me-2"></i> Upload de Fundo
                                    </button>
                                    <input type="file" id="upload-bg" class="d-none" accept="image/*" onchange="uploadImagem(this, 'fundo')">
                                    <?php if($user['tipo_fundo'] == 'imagem'): ?>
                                        <div class="small text-success mt-2"><i class="fa-solid fa-check-circle"></i> Imagem personalizada ativa</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-grid mb-5">
                                <button type="submit" class="btn btn-success btn-lg shadow">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="col-preview text-center">
                    <div class="small fw-bold text-secondary mb-2">VISUALIZAÇÃO EM TEMPO REAL</div>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerenciar Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-link">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id="link_id">
                        <div class="mb-3">
                            <label class="form-label">Ícone</label>
                            <input type="hidden" name="icone" id="icone_input">
                            <div class="d-flex gap-2 flex-wrap" id="icon-selector">
                                <button type="button" class="btn btn-light border" onclick="selectIcon('fa-brands fa-whatsapp')"><i class="fa-brands fa-whatsapp"></i></button>
                                <button type="button" class="btn btn-light border" onclick="selectIcon('fa-brands fa-instagram')"><i class="fa-brands fa-instagram"></i></button>
                                <button type="button" class="btn btn-light border" onclick="selectIcon('fa-solid fa-globe')"><i class="fa-solid fa-globe"></i></button>
                                <button type="button" class="btn btn-light border" onclick="selectIcon('fa-solid fa-envelope')"><i class="fa-solid fa-envelope"></i></button>
                                <button type="button" class="btn btn-light border" onclick="selectIcon('')">X</button>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label">Título</label><input type="text" name="titulo" id="titulo" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">URL</label><input type="text" name="url" id="url" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary w-100">Salvar Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
    // =========================================================
    // CONFIGURAÇÕES JSON (Adicione mais opções aqui!)
    // =========================================================
    
    const listaFontes = [
        { id: 'sans',    nome: 'Moderno',  family: "'Inter', sans-serif" },
        { id: 'serif',   nome: 'Clássico', family: "'Merriweather', serif" },
        { id: 'mono',    nome: 'Técnico',  family: "'Roboto Mono', monospace" },
        { id: 'cursive', nome: 'Elegante', family: "'Dancing Script', cursive" },
        { id: 'poppins', nome: 'Bold',     family: "'Poppins', sans-serif" }
    ];

    const listaTemas = [
        { tipo: 'cor', valor: '#ffffff', rotulo: 'Branco' },
        { tipo: 'cor', valor: '#f0f2f5', rotulo: 'Cinza' },
        { tipo: 'cor', valor: '#121212', rotulo: 'Dark' },
        { tipo: 'cor', valor: '#d1fae5', rotulo: 'Menta' },
        { tipo: 'cor', valor: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', rotulo: 'Roxo' },
        { tipo: 'cor', valor: 'linear-gradient(to right, #ff416c, #ff4b2b)', rotulo: 'Sunset' },
        { tipo: 'cor', valor: 'linear-gradient(to top, #09203f 0%, #537895 100%)', rotulo: 'Oceano' },
        { tipo: 'cor', valor: 'linear-gradient(120deg, #f6d365 0%, #fda085 100%)', rotulo: 'Pêssego' },
        { tipo: 'cor', valor: 'linear-gradient(to top, #30cfd0 0%, #330867 100%)', rotulo: 'Neon' }
    ];

    // =========================================================
    // LÓGICA DO PAINEL
    // =========================================================

    $(document).ready(function() {
        carregarLinks();
        renderizarOpcoes(); // Renderiza o JSON na tela
    });

    // 1. Renderizar Fontes e Cores (JSON -> HTML)
    function renderizarOpcoes() {
        const fonteAtual = $('#input_fonte').val();
        const corAtual = $('#cor_fundo').val();
        const tipoAtual = $('#tipo_fundo').val();

        // Fontes
        let htmlFontes = '';
        listaFontes.forEach(f => {
            const active = (f.id === fonteAtual) ? 'selected' : '';
            htmlFontes += `
                <div class="font-option ${active}" onclick="selectFont('${f.id}', this)" style="font-family: ${f.family}">
                    <div class="fs-4">Aa</div>
                    <div class="small text-muted">${f.nome}</div>
                </div>`;
        });
        $('#container-fontes').html(htmlFontes);

        // Temas
        let htmlTemas = '';
        listaTemas.forEach(t => {
            const active = (tipoAtual === 'cor' && t.valor === corAtual) ? 'selected' : '';
            htmlTemas += `
                <div class="theme-option ${active}" onclick="selectTheme('cor', '${t.valor}', this)" 
                     style="background: ${t.valor};" title="${t.rotulo}">
                </div>`;
        });
        $('#container-temas').html(htmlTemas);
    }

    // 2. Upload de Imagem (Com tratamento de erro visual)
    function uploadImagem(input, tipo) {
        if (input.files && input.files[0]) {
            let fd = new FormData();
            fd.append('arquivo', input.files[0]);
            fd.append('acao', 'upload_imagem');
            fd.append('tipo_upload', tipo);

            let label = $(input).prev(); // Botão ou Label
            let originalContent = label.html();
            label.html('<i class="fa-solid fa-spinner fa-spin"></i>'); // Loader

            $.ajax({
                url: 'ajax_perfil.php', type: 'POST', data: fd,
                contentType: false, processData: false, dataType: 'json',
                success: function(res) {
                    label.html(originalContent);
                    if(res.status === 'sucesso') {
                        if(tipo === 'avatar') $('#preview-avatar').attr('src', res.dados.url);
                        else {
                            // Limpa seleção de cores pois agora é imagem
                            $('.theme-option').removeClass('selected');
                            $('#tipo_fundo').val('imagem');
                            alert('Fundo atualizado!');
                        }
                        atualizarPreview();
                    } else {
                        alert('Erro: ' + res.msg);
                    }
                },
                error: function() {
                    label.html(originalContent);
                    alert('Erro de conexão. O arquivo pode ser muito grande para o servidor.');
                }
            });
        }
    }

    // 3. Seleção de Opções
    function selectFont(id, el) {
        $('.font-option').removeClass('selected');
        $(el).addClass('selected');
        $('#input_fonte').val(id);
    }

    function selectTheme(tipo, valor, el) {
        $('.theme-option').removeClass('selected');
        $(el).addClass('selected');
        $('#tipo_fundo').val(tipo);
        $('#cor_fundo').val(valor);
    }

    // 4. Salvar Dados
    $('#form-aparencia').on('submit', function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        let txt = btn.text();
        btn.prop('disabled', true).text('Salvando...');

        $.post('ajax_perfil.php', $(this).serialize(), function(res) {
            btn.prop('disabled', false).text(txt);
            if(res.status === 'sucesso') {
                atualizarPreview();
            } else alert(res.msg);
        }, 'json');
    });

    // 5. Funções de Links (CRUD)
    function carregarLinks() {
        $.get('ajax_links.php?acao=listar', function(res) {
            let html = '';
            if(res.dados && res.dados.length > 0) {
                res.dados.forEach(l => {
                    let icon = l.icone ? `<i class="${l.icone}"></i>` : `<i class="fa-solid fa-link"></i>`;
                    html += `
                    <div class="link-card" data-id="${l.id}">
                        <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
                        <div class="me-3 d-flex align-items-center justify-content-center bg-light rounded-circle text-secondary" style="width:40px;height:40px">${icon}</div>
                        <div class="flex-grow-1 text-truncate">
                            <div class="fw-bold text-dark">${l.titulo}</div>
                            <div class="small text-muted">${l.url}</div>
                        </div>
                        <div class="ms-2">
                            <button class="btn btn-sm btn-light text-primary" onclick="editarLink(${l.id}, '${l.titulo}', '${l.url}', '${l.icone}')"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-light text-danger" onclick="excluirLink(${l.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>`;
                });
            } else html = '<div class="text-center text-muted py-4">Nenhum link.</div>';
            $('#lista-links').html(html);
            
            new Sortable(document.getElementById('lista-links'), {
                handle: '.drag-handle', animation: 150,
                onEnd: function() {
                    let ordem = [];
                    $('#lista-links .link-card').each(function() { ordem.push($(this).data('id')); });
                    $.post('ajax_links.php', {acao:'reordenar', ordem:ordem}, atualizarPreview);
                }
            });
        }, 'json');
    }

    // Modais e Helpers
    function atualizarPreview() { document.getElementById('iframePreview').src += ''; }
    function abrirModalNovo() { $('#form-link')[0].reset(); $('#link_id').val(''); $('#icone_input').val(''); new bootstrap.Modal(document.getElementById('modalLink')).show(); }
    function editarLink(id, t, u, i) { $('#link_id').val(id); $('#titulo').val(t); $('#url').val(u); $('#icone_input').val(i); new bootstrap.Modal(document.getElementById('modalLink')).show(); }
    function selectIcon(i) { $('#icone_input').val(i); alert('Ícone selecionado'); }
    $('#form-link').on('submit', function(e){ e.preventDefault(); $.post('ajax_links.php', $(this).serialize(), function(){ $('#modalLink').modal('hide'); carregarLinks(); atualizarPreview(); }, 'json'); });
    function excluirLink(id) { if(confirm('Excluir?')) $.post('ajax_links.php', {acao:'excluir', id:id}, function(){ carregarLinks(); atualizarPreview(); }, 'json'); }
    </script>
</body>
</html>