<?php
// gestor/index.php
require_once '../functions.php';
verificarAutenticacao();

// Dados do usuário logado
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .table-links td { vertical-align: middle; }
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
                        <?php echo $linkPublico; ?> <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <button class="btn btn-primary mt-2 mt-md-0" onclick="abrirModal()">
                    <i class="bi bi-plus-lg"></i> Novo Link
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">Meus Links Ativos</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-links mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">Ordem</th>
                                <th width="30%">Título</th>
                                <th width="40%">URL</th>
                                <th width="20%" class="text-end">Ações</th>
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
                        <input type="hidden" name="id" id="link_id"> <div class="mb-3">
                            <label class="form-label">Título do Botão</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" required placeholder="Ex: Meu WhatsApp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL de Destino</label>
                            <input type="url" name="url" id="url" class="form-control" required placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ordem de Exibição</label>
                            <input type="number" name="ordem" id="ordem" class="form-control" value="0">
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

    <script>
    $(document).ready(function() {
        carregarLinks(); // Carrega a lista ao abrir a página

        // 1. SUBMIT DO FORMULÁRIO (Salvar)
        $('#form-link').on('submit', function(e) {
            e.preventDefault();
            $.post('ajax_links.php', $(this).serialize(), function(res) {
                if(res.status === 'sucesso') {
                    $('#modalLink').modal('hide');
                    carregarLinks(); // Atualiza a tabela
                    $('#form-link')[0].reset(); // Limpa form
                    $('#link_id').val(''); // Reseta ID
                } else {
                    alert(res.msg);
                }
            }, 'json');
        });
    });

    // 2. FUNÇÃO PARA LISTAR LINKS (Read)
    function carregarLinks() {
        $.get('ajax_links.php?acao=listar', function(res) {
            let html = '';
            if(res.dados.length > 0) {
                res.dados.forEach(function(link) {
                    html += `
                        <tr>
                            <td><span class="badge bg-secondary">${link.ordem}</span></td>
                            <td class="fw-bold">${link.titulo}</td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <a href="${link.url}" target="_blank" class="text-muted small">${link.url}</a>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editarLink(${link.id}, '${link.titulo}', '${link.url}', ${link.ordem})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="excluirLink(${link.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                });
            } else {
                html = '<tr><td colspan="4" class="text-center py-4 text-muted">Nenhum link cadastrado ainda.</td></tr>';
            }
            $('#lista-links').html(html);
        }, 'json');
    }

    // 3. PREPARAR MODAL PARA EDIÇÃO
    function editarLink(id, titulo, url, ordem) {
        $('#link_id').val(id);
        $('#titulo').val(titulo);
        $('#url').val(url);
        $('#ordem').val(ordem);
        $('#modalTitulo').text('Editar Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    // 4. RESETAR MODAL AO ABRIR PARA NOVO
    function abrirModal() {
        $('#form-link')[0].reset();
        $('#link_id').val('');
        $('#modalTitulo').text('Novo Link');
        new bootstrap.Modal(document.getElementById('modalLink')).show();
    }

    // 5. EXCLUIR LINK
    function excluirLink(id) {
        if(confirm('Tem certeza que deseja excluir este link?')) {
            $.post('ajax_links.php', { acao: 'excluir', id: id }, function(res) {
                if(res.status === 'sucesso') carregarLinks();
            }, 'json');
        }
    }
    </script>
</body>
</html>