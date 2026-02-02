<?php
// Inclui configurações iniciais (caso precise de algo no load da página)
require_once 'functions.php';

// Se já estiver logado, manda direto pro painel
if (isset($_SESSION['usuario_id'])) {
    header('Location: gestor/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linktree Voto Solutions - Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-auth { max-width: 450px; width: 100%; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .nav-tabs .nav-link { color: #495057; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="card card-auth p-4">
        <div class="text-center mb-4">
            <h3>Voto Solutions</h3>
            <p class="text-muted">Gerenciador de Links</p>
        </div>

        <ul class="nav nav-tabs nav-fill mb-4" id="authTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">Login</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="cadastro-tab" data-bs-toggle="tab" data-bs-target="#cadastro" type="button">Criar Conta</button>
            </li>
        </ul>

        <div class="tab-content" id="authTabContent">
            
            <div class="tab-pane fade show active" id="login" role="tabpanel">
                <div id="msg-login" class="alert d-none"></div> <form id="form-login">
                    <input type="hidden" name="acao" value="login">
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Entrar</button>
                </form>
            </div>

            <div class="tab-pane fade" id="cadastro" role="tabpanel">
                <div id="msg-cadastro" class="alert d-none"></div>

                <form id="form-cadastro">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="mb-3">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuário (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text">voto.sol/</span>
                            <input type="text" name="usuario" class="form-control" placeholder="seu-nome" pattern="[a-zA-Z0-9]+" title="Apenas letras e números" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-lg">Criar Conta</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Função genérica para envio AJAX
        function enviarFormulario(formId, msgId) {
            $(formId).on('submit', function(e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                var originalText = btn.text();
                
                // Feedback visual de carregamento
                btn.prop('disabled', true).text('Processando...');
                $(msgId).addClass('d-none').removeClass('alert-success alert-danger');

                $.ajax({
                    url: 'gestor/ajax_auth.php', // O arquivo que vamos criar a seguir
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        btn.prop('disabled', false).text(originalText);
                        if(res.debug_log) {
                            console.group("🐞 PHP Debug Log");
                            res.debug_log.forEach(log => console.log(log));
                            console.groupEnd();
                        }
                        
                        if (res.status === 'sucesso') {
                            $(msgId).removeClass('d-none').addClass('alert-success').text(res.msg);
                            // Redireciona após 1 segundo
                            setTimeout(function() {
                                window.location.href = res.dados.redirect;
                            }, 1000);
                        } else {
                            $(msgId).removeClass('d-none').addClass('alert-danger').text(res.msg);
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).text(originalText);
                        console.error("Erro Fatal:", xhr.responseText);
                        $(msgId).removeClass('d-none').addClass('alert-danger').text('Erro no servidor.');
                    }
                });
            });
        }

        // Ativa os listeners nos formulários
        enviarFormulario('#form-login', '#msg-login');
        enviarFormulario('#form-cadastro', '#msg-cadastro');
    });
    </script>
    
    <?php include 'includes/debug_footer.php'; ?>
</body>
</html>