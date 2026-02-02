<?php
// Só exibe se o debug estiver ativo
if (defined('DEBUG_MODE') && DEBUG_MODE): 
    global $debug_log, $pdo;
    $statusDb = $pdo ? '<span style="color:#2ecc71">● Conectado</span>' : '<span style="color:#e74c3c">● Desconectado</span>';
?>
<style>
    #debug-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 200px;
        background: #212529;
        color: #00ff41;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        z-index: 9999;
        border-top: 3px solid #00ff41;
        overflow-y: scroll;
        padding: 10px;
        opacity: 0.95;
    }
    #debug-bar h6 { color: #fff; border-bottom: 1px solid #555; padding-bottom: 5px; }
    #debug-toggle {
        position: fixed;
        bottom: 10px;
        right: 10px;
        z-index: 10000;
    }
</style>

<button id="debug-toggle" class="btn btn-sm btn-dark border-white" onclick="document.getElementById('debug-bar').style.display = (document.getElementById('debug-bar').style.display === 'none' ? 'block' : 'none')">🐞 Debug</button>

<div id="debug-bar">
    <h6>STATUS DO SISTEMA (DEBUG MODE ON)</h6>
    <div><strong>Banco de Dados:</strong> <?php echo $statusDb; ?></div>
    <div><strong>Sessão ID:</strong> <?php echo session_id(); ?></div>
    <div><strong>PHP Versão:</strong> <?php echo phpversion(); ?></div>
    <hr style="border-color:#555">
    
    <h6>LOG DE EXECUÇÃO:</h6>
    <?php 
    if(!empty($debug_log)) {
        foreach($debug_log as $line) {
            echo "<div>> " . htmlspecialchars($line) . "</div>";
        }
    } else {
        echo "<div>> Nenhum log registrado.</div>";
    }
    ?>
    
    <hr style="border-color:#555">
    <h6>DADOS POST:</h6>
    <pre><?php print_r($_POST); ?></pre>
</div>

<?php endif; ?>