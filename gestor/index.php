<?php
require_once '../functions.php';
verificarAutenticacao();

$nome = $_SESSION['usuario_nome'];
$slug = $_SESSION['usuario_slug'];
// URL pública para o iframe

// --- CORREÇÃO DO PROTOCOLO (HTTP vs HTTPS) ---
// O Cloud Run usa um Load Balancer, então $_SERVER['HTTPS'] as vezes vem vazio.
// Precisamos verificar o cabeçalho X-Forwarded-Proto também.
$protocol = 'http';
if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) {
    $protocol = 'https';
}

$urlPreview = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/p.php?u=" . $slug;
// $urlPreview = "http://" . $_SERVER['HTTP_HOST'] . "/p.php?u=" . $slug;
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
        /* --- TEMA ESCURO --- */
        body { background-color: #121212; color: #e0e0e0; font-family: 'Inter', sans-serif; }
        
        /* Navbar */
        .navbar { background-color: #1e1e1e; border-bottom: 1px solid #333; }
        .navbar-brand { font-weight: 800; letter-spacing: -0.5px; }

        /* Área Principal */
        .main-container { max-width: 1400px; margin: 0 auto; padding-top: 30px; }

        /* Cards de Links (Editor) */
        .link-card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            transition: transform 0.2s, border-color 0.2s;
        }
        .link-card:hover { border-color: #555; }
        .drag-handle { cursor: grab; color: #666; padding-right: 15px; }
        .drag-handle:active { cursor: grabbing; }

        /* Botão Adicionar Principal */
        .btn-add-main {
            background: linear-gradient(90deg, #8a2be2, #4b0082); /* Roxo da imagem */
            color: white;
            font-weight: 700;
            border: none;
            width: 100%;
            padding: 15px;
            border-radius: 50px;
            margin-bottom: 25px;
            font-size: 1.1rem;
            transition: opacity 0.3s;
        }
        .btn-add-main:hover { opacity: 0.9; color: white; }

        /* Inputs Estilizados */
        .form-dark { background-color: #2c2c2c; border: 1px solid #444; color: #fff; }
        .form-dark:focus { background-color: #2c2c2c; color: #fff; border-color: #8a2be2; box-shadow: none; }
        .form-dark::placeholder { color: #888; }

        /* Dropdown de Ícones */
        .btn-icon-select {
            background-color: #2c2c2c; border: 1px solid #444; color: #fff; width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center; border-radius: 8px;
        }
        .dropdown-menu-dark { background-color: #2c2c2c; border: 1px solid #444; min-width: 250px; padding: 10px; }
        .icon-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .icon-option {
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
            border-radius: 5px; cursor: pointer; transition: background 0.2s; color: #ddd;
        }
        .icon-option:hover { background-color: #8a2be2; color: white; }

        /* --- PREVIEW CELULAR --- */
        .phone-mockup {
            border: 12px solid #000;
            border-radius: 40px;
            height: 750px; /* Altura do celular */
            width: 370px;  /* Largura do celular */
            overflow: hidden;
            position: relative;
            background-color: #fff;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .phone-notch {
            position: absolute; top: 0; left: 50%; transform: translateX(-50%);
            width: 150px; height: 25px; background-color: #000;
            border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; z-index: 10;
        }
        .preview-iframe { width: 100%; height: 100%; border: none; }
        
        /* Layout Colunas */
        .col-preview { position: sticky; top: 20px; height: calc(100vh - 100px); display: flex; justify-content: center; }
        
        @media (max-width: 992px) {
            .phone-mockup { height: 600px; width: 300px; }
            .col-preview { position: relative; height: auto; margin-top: 40px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#"><i class="fa-solid fa-bolt me-2"></i>Voto Links</a>
            <div class="d-flex align-items-center">
                <span class="text-secondary me-3 d-none d-md-block">voto.sol/<?php echo $slug; ?></span>
                <a href="../index.php?logout=true" class="btn btn-sm btn-outline-secondary">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <div class="row">
            
            <div class="col-lg-7">
                
                <button class="btn btn-add-main shadow" onclick="abrirModalNovo()">
                    <i class="fa-solid fa-plus me-2"></i> Adicionar Novo Link
                </button>

                <div id="lista-links">
                    <div class="text-center text-muted py-5">Carregando links...</div>
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

    <div class="modal fade" id="modalLink" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: #1e1e1e; border: 1px solid #333;">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title text-white" id="modalTitulo">Link</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-link">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id="link_id">

                        <div class="mb-3">
                            <label class="form-label text-secondary small">Ícone</label>
                            <div class="dropdown">
                                <button class="btn btn-icon-select w-100 justify-content-start px-3" type="button" data-bs-toggle="dropdown">
                                    <i id="preview-icon-btn" class="fa-solid fa-link me-2"></i> 
                                    <span id="label-icon-btn" class="text-muted">Selecionar Ícone...</span>
                                </button>
                                <input type="hidden" name="icone" id="icone_input" value="">
                                
                                <div class="dropdown-menu dropdown-menu-dark p-2">
                                    <div class="icon-grid">
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-whatsapp')"><i class="fa-brands fa-whatsapp"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-instagram')"><i class="fa-brands fa-instagram"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-linkedin')"><i class="fa-brands fa-linkedin"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-github')"><i class="fa-brands fa-github"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-youtube')"><i class="fa-brands fa-youtube"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-tiktok')"><i class="fa-brands fa-tiktok"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-twitter')"><i class="fa-brands fa-twitter"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-brands fa-facebook')"><i class="fa-brands fa-facebook"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-globe')"><i class="fa-solid fa-globe"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-envelope')"><i class="fa-solid fa-envelope"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-phone')"><i class="fa-solid fa-phone"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-location-dot')"><i class="fa-solid fa-location-dot"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-star')"><i class="fa-solid fa-star"></i></div>
                                        <div class="icon-option" onclick="selectIcon('fa-solid fa-store')"><i class="fa-solid fa-store"></i></div>
                                        <div class="icon-option" onclick="selectIcon('')"><i class="fa-solid fa-ban"></i></div> </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small">Título</label>
                            <input type="text" name="titulo" id="titulo" class="form-control form-dark" placeholder="Ex: Meu Site Oficial" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small">URL</label>
                            <input type="text" name="url" id="url" class="form-control form-dark" placeholder="https://..." required>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
    $(document).ready(function() {
        carregarLinks();

        // Salvar Link
        $('#form-link').on('submit', function(e) {
            e.preventDefault();
            $.post('ajax_links.php', $(this).serialize(), function(res) {
                if(res.status === 'sucesso') {
                    $('#modalLink').modal('hide');
                    carregarLinks(); // Recarrega lista
                    atualizarPreview(); // Atualiza iframe
                } else {
                    alert(res.msg);
                }
            }, 'json');
        });
    });

    // Função para atualizar o Iframe sem piscar muito
    function atualizarPreview() {
        document.getElementById('iframePreview').src = document.getElementById('iframePreview').src;
    }

    // Selecionador de Ícone Customizado
    function selectIcon(iconClass) {
        $('#icone_input').val(iconClass);
        if(iconClass) {
            $('#preview-icon-btn').attr('class', iconClass + ' me-2');
            $('#label-icon-btn').text(iconClass.replace('fa-brands fa-', '').replace('fa-solid fa-', ''));
        } else {
            $('#preview-icon-btn').attr('class', 'fa-solid fa-link me-2');
            $('#label-icon-btn').text('Sem ícone');
        }
    }

    function abrirModalNovo() {
        $('#form-link')[0].reset();
        $('#link_id').val('');
        selectIcon(''); 
        $('#modalTitulo').text('Criar Novo Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    function editarLink(id, titulo, url, icone) {
        $('#link_id').val(id);
        $('#titulo').val(titulo);
        $('#url').val(url);
        selectIcon(icone);
        $('#modalTitulo').text('Editar Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    function excluirLink(id) {
        if(confirm('Excluir este link?')) {
            $.post('ajax_links.php', { acao: 'excluir', id: id }, function(res) {
                carregarLinks();
                atualizarPreview();
            }, 'json');
        }
    }

    function toggleAtivo(id, btn) {
        let novoStatus = $(btn).find('i').hasClass('fa-eye') ? 0 : 1;
        $.post('ajax_links.php', { acao: 'toggle_ativo', id: id, ativo: novoStatus }, function() {
            carregarLinks();
            atualizarPreview();
        }, 'json');
    }

    function carregarLinks() {
        $.get('ajax_links.php?acao=listar', function(res) {
            let html = '';
            if(res.dados.length > 0) {
                res.dados.forEach(function(link) {
                    let icone = link.icone ? `<i class="${link.icone}"></i>` : `<i class="fa-solid fa-link"></i>`;
                    let eyeIcon = link.ativo == 1 ? 'fa-eye' : 'fa-eye-slash text-muted';
                    let opacity = link.ativo == 1 ? '1' : '0.5';

                    html += `
                    <div class="link-card" data-id="${link.id}" style="opacity: ${opacity}">
                        <div class="drag-handle"><i class="fa-solid fa-grip-vertical"></i></div>
                        
                        <div class="me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #2c2c2c; border-radius: 50%;">
                            ${icone}
                        </div>

                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-white text-truncate">${link.titulo}</div>
                            <div class="small text-secondary text-truncate">${link.url}</div>
                        </div>

                        <div class="d-flex gap-2 ms-2">
                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="toggleAtivo(${link.id}, this)">
                                <i class="fa-regular ${eyeIcon}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary border-0" onclick="editarLink(${link.id}, '${link.titulo}', '${link.url}', '${link.icone}')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="excluirLink(${link.id})">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>`;
                });
            } else {
                html = '<div class="text-center text-secondary mt-5"><i class="fa-solid fa-ghost fs-1 mb-3"></i><br>Nenhum link criado ainda.</div>';
            }
            $('#lista-links').html(html);

            // Drag & Drop
            new Sortable(document.getElementById('lista-links'), {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () {
                    var ordem = [];
                    $('#lista-links .link-card').each(function() { ordem.push($(this).data('id')); });
                    $.post('ajax_links.php', { acao: 'reordenar', ordem: ordem }, function() {
                        atualizarPreview();
                    });
                }
            });
        }, 'json');
    }
    </script>
</body>
</html>