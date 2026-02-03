<?php
require_once '../functions.php';
verificarAutenticacao();

$nome = $_SESSION['usuario_nome'];
$slug = $_SESSION['usuario_slug'];
$linkPublico = "http://" . $_SERVER['HTTP_HOST'] . "/p.php?u=" . $slug;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Voto Solutions</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/css/fontawesome-iconpicker.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fa; }
        .cursor-move { cursor: move; } /* Mãozinha para arrastar */
        .iconpicker-popover { z-index: 1060 !important; } /* Fix para aparecer acima do modal */
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Voto Solutions</a>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Olá, <?php echo h($nome); ?></span>
                <a href="../index.php?logout=true" class="btn btn-outline-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card mb-4 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="card-title">Seu Linktree Público</h5>
                    <a href="<?php echo $linkPublico; ?>" target="_blank" class="text-decoration-none">
                        <?php echo $linkPublico; ?> <i class="fa-solid fa-up-right-from-square"></i>
                    </a>
                </div>
                <button class="btn btn-primary mt-2 mt-md-0" onclick="abrirModal()">
                    <i class="fa-solid fa-plus"></i> Novo Link
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                Meus Links (Arraste para reordenar)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%"></th>
                                <th width="10%">Ícone</th>
                                <th width="40%">Título</th>
                                <th width="30%">URL</th>
                                <th width="15%" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-links">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLink" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Gerenciar Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-link">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" id="link_id">

                        <div class="mb-3">
                            <label class="form-label">Título do Botão</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Meu WhatsApp">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ícone</label>
                            <div class="input-group">
                                <input type="text" name="icone" id="icone" class="form-control icp icp-auto" placeholder="Clique para escolher...">
                                <span class="input-group-text"><i class="fas fa-archive"></i></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL de Destino</label>
                            <input type="url" name="url" id="url" class="form-control" required placeholder="https://...">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Salvar Link</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.2.0/js/fontawesome-iconpicker.min.js"></script>

    <script>
    $(document).ready(function() {
        carregarLinks();

        // 1. INICIALIZA O ICONPICKER
        $('.icp-auto').iconpicker({
            title: 'Escolha um ícone',
            placement: 'bottomAsync', // Performance
            templates: { popover: '<div class="iconpicker-popover popover"><div class="arrow"></div><div class="popover-title"></div><div class="popover-content"></div></div>' }
        });

        // Atualiza o ícone visual ao lado do input quando seleciona
        $('.icp').on('iconpickerSelected', function(e) {
            $(this).parent().find('.input-group-text i').attr('class', e.iconpickerValue);
        });

        // 2. SUBMIT DO FORM (Salvar)
        $('#form-link').on('submit', function(e) {
            e.preventDefault();
            $.post('ajax_links.php', $(this).serialize(), function(res) {
                if(res.status === 'sucesso') {
                    $('#modalLink').modal('hide');
                    carregarLinks();
                    $('#form-link')[0].reset();
                    $('#link_id').val('');
                    // Reseta icone visual
                    $('.input-group-text i').attr('class', 'fas fa-archive');
                } else {
                    alert(res.msg);
                }
            }, 'json');
        });
    });

    // 3. FUNÇÃO LISTAR E ATIVAR DRAG & DROP
    function carregarLinks() {
        $.get('ajax_links.php?acao=listar', function(res) {
            let html = '';
            if(res.dados.length > 0) {
                res.dados.forEach(function(link) {
                    let iconeHTML = link.icone ? `<i class="${link.icone} fa-lg"></i>` : '-';
                    
                    html += `
                        <tr data-id="${link.id}" class="cursor-move">
                            <td class="text-secondary"><i class="fa-solid fa-grip-vertical"></i></td>
                            <td>${iconeHTML}</td>
                            <td class="fw-bold">${link.titulo}</td>
                            <td class="text-truncate" style="max-width: 150px;">${link.url}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editarLink(${link.id}, '${link.titulo}', '${link.url}', '${link.icone}')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="excluirLink(${link.id})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">Nenhum link cadastrado.</td></tr>';
            }
            $('#lista-links').html(html);

            // ATIVA O SORTABLE (DRAG AND DROP)
            var el = document.getElementById('lista-links');
            var sortable = new Sortable(el, {
                animation: 150,
                handle: '.cursor-move', // Pode arrastar clicando na linha toda ou no ícone
                onEnd: function (evt) {
                    // Pega a nova ordem dos IDs
                    var ordem = [];
                    $('#lista-links tr').each(function() {
                        ordem.push($(this).data('id'));
                    });
                    
                    // Envia pro backend salvar a ordem
                    $.post('ajax_links.php', { acao: 'reordenar', ordem: ordem });
                }
            });

        }, 'json');
    }

    function editarLink(id, titulo, url, icone) {
        $('#link_id').val(id);
        $('#titulo').val(titulo);
        $('#url').val(url);
        $('#icone').val(icone);
        
        // Atualiza o visual do ícone no modal
        let classeIcone = icone ? icone : 'fas fa-archive';
        $('.input-group-text i').attr('class', classeIcone);
        
        $('#modalTitulo').text('Editar Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    function abrirModal() {
        $('#form-link')[0].reset();
        $('#link_id').val('');
        $('#icone').val('');
        $('.input-group-text i').attr('class', 'fas fa-archive');
        $('#modalTitulo').text('Novo Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    function excluirLink(id) {
        if(confirm('Excluir este link?')) {
            $.post('ajax_links.php', { acao: 'excluir', id: id }, function(res) {
                if(res.status === 'sucesso') carregarLinks();
            }, 'json');
        }
    }
    </script>
    <?php include '../includes/debug_footer.php'; ?>
</body>
</html>