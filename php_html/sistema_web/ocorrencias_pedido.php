<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php'; 
require_once __DIR__ . '/verificar_login.php';


if (empty($_SESSION['csrf_token'])) {$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id =$_SESSION['funcionario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !==$_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }

    $acao =$_POST['acao'] ?? '';

   
    if ($acao === 'listar_itens') {
        $pedido_id =$_POST['pedido_id'] ?? null;

        if (!$pedido_id) {
            echo "<option value=''>Selecione um pedido primeiro</option>";
            exit;
        }

        $stmt =$pdo->prepare("SELECT id FROM itens_pedidos WHERE pedido_id = ? ORDER BY id ASC");
        $stmt->execute([$pedido_id]);
        $itens =$stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$itens) {
            echo "<option value=''>Nenhum item encontrado</option>";
            exit;
        }

        echo "<option value=''>Selecione o item</option>";
        foreach ($itens as$item) {
            echo "<option value='{$item['id']}'>Item #{$item['id']}</option>";
        }
        exit;
    }

  
    if ($acao === 'salvar_ocorrencia') {
        $id =$_POST['id'] ?? null;
        $pedido_id =$_POST['pedido_id'] ?? null;
        $item_pedido_id =$_POST['item_pedido_id'] ?? null;
        $tipo =$_POST['tipo'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $retornou_estoque =$_POST['retornou_estoque'] ?? 0;

        if ($id) {
        
            $stmt =$pdo->prepare("UPDATE ocorrencias_pedido SET pedido_id=?, item_pedido_id=?, tipo=?, motivo=?, retornou_estoque=? WHERE id=?");
            $stmt->execute([$pedido_id,$item_pedido_id, $tipo,$motivo, $retornou_estoque,$id]);
        } else {
            
            $stmt =$pdo->prepare("INSERT INTO ocorrencias_pedido (pedido_id, item_pedido_id, funcionario_id, tipo, motivo, retornou_estoque) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pedido_id,$item_pedido_id, $funcionario_id,$tipo, $motivo,$retornou_estoque]);
        }
        
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$ocorrencias =$pdo->query("SELECT * FROM ocorrencias_pedido ORDER BY data_ocorrencia DESC")->fetchAll(PDO::FETCH_ASSOC);
$pedidos =$pdo->query("SELECT id FROM pedidos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ocorrências</title>
</head>
<body>
    <h1>Ocorrências dos Pedidos</h1>
    <button onclick="abrirModalOcorrencia()">NOVA OCORRÊNCIA</button>

    <div id="container-ocorrencias" style="margin-top: 20px;">
        <?php if (!$ocorrencias): ?>
            <p>Nenhuma ocorrência cadastrada.</p>
        <?php else: ?>
            <?php foreach ($ocorrencias as$ocorrencia): ?>
                <?php
                $tipos = [
                    'cancelamento' => 'Cancelamento',
                    'troca' => 'Troca'
                ];
                ?>
                <div>
                    <strong>Ocorrência #<?= $ocorrencia['id'] ?></strong><br>
                    Pedido: #<?= $ocorrencia['pedido_id'] ?><br>
                    Item: #<?= $ocorrencia['item_pedido_id'] ?><br>
                    Tipo: <?= htmlspecialchars($tipos[$ocorrencia['tipo']] ?? $ocorrencia['tipo']) ?><br>
                    Motivo: <?= htmlspecialchars($ocorrencia['motivo']) ?><br>
                    Retornou ao estoque: <?= $ocorrencia['retornou_estoque'] ? 'Sim' : 'Não' ?><br>
                    Data: <?= date('d/m/Y H:i', strtotime($ocorrencia['data_ocorrencia'])) ?><br><br>
                    <button onclick='editarOcorrencia(<?= json_encode($ocorrencia) ?>)'>Editar</button>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- MODAL DE OCORRÊNCIA (Criar / Editar) -->
    <dialog id="modalOcorrencia">
        <h2 id="tituloModal">Nova Ocorrência</h2>
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar as informações desta ocorrência?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="salvar_ocorrencia">
            <input type="hidden" name="id" id="form_id">

            <div>
                <label>Pedido:</label>
                <select name="pedido_id" id="form_pedido_id" required>
                    <option value="">Selecione o pedido</option>
                    <?php foreach ($pedidos as$pedido): ?>
                        <option value="<?= $pedido['id'] ?>">Pedido #<?= $pedido['id'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <br>

            <div>
                <label>Item do pedido:</label>
                <select name="item_pedido_id" id="form_item_id" required>
                    <option value="">Selecione o pedido primeiro</option>
                </select>
            </div>
            <br>

            <div>
                <label>Tipo:</label>
                <select name="tipo" id="form_tipo" required>
                    <option value="">Selecione</option>
                    <option value="cancelamento">Cancelamento</option>
                    <option value="troca">Troca</option>
                </select>
            </div>
            <br>

            <div>
                <label>Motivo:</label><br>
                <textarea name="motivo" id="form_motivo" rows="4" required></textarea>
            </div>
            <br>

            <div>
                <label>Retornou ao estoque?</label>
                <select name="retornou_estoque" id="form_estoque" required>
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                </select>
            </div>
            <br>

            <button type="submit">SALVAR</button>
            <button type="button" onclick="document.getElementById('modalOcorrencia').close()">CANCELAR</button>
        </form>
    </dialog>

    <!-- MODAL DE CONFIRMAÇÃO PADRÃO -->
    <dialog id="modalConfirmacaoCustom">
        <h3 id="tituloConfirmacao">Confirmação</h3>
        <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
        <button type="button" id="btnSimConfirmacao">Sim</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
    </dialog>

    <script>
        let formParaSubmeter = null;

        // Função do modal de confirmação
        function dispararConfirmacao(mensagem, form) {
            formParaSubmeter = form;
            document.getElementById('mensagemConfirmacao').innerText = mensagem;
            document.getElementById('modalConfirmacaoCustom').showModal();
        }

        document.getElementById('btnSimConfirmacao').addEventListener('click', function() {
            document.getElementById('modalConfirmacaoCustom').close();
            if (formParaSubmeter) {
                formParaSubmeter.submit(); 
            }
        });

        
        async function carregarItens(pedido_id, item_selecionado = null) {
            let selectItem = document.getElementById('form_item_id');
            if (!pedido_id) {
                selectItem.innerHTML = `<option value="">Selecione o pedido primeiro</option>`;
                return;
            }

            let formData = new FormData();
            formData.append('acao', 'listar_itens');
            formData.append('pedido_id', pedido_id);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

            let req = await fetch('', { method: 'POST', body: formData });
            selectItem.innerHTML = await req.text();

            if (item_selecionado) {
                selectItem.value = item_selecionado;
            }
        }

       
        document.getElementById('form_pedido_id').addEventListener('change', function() {
            carregarItens(this.value);
        });

        function abrirModalOcorrencia() {
            document.getElementById('form_id').value = '';
            document.getElementById('form_pedido_id').value = '';
            document.getElementById('form_item_id').innerHTML = '<option value="">Selecione o pedido primeiro</option>';
            document.getElementById('form_tipo').value = '';
            document.getElementById('form_motivo').value = '';
            document.getElementById('form_estoque').value = '0';
            
            document.getElementById('tituloModal').innerText = 'Nova Ocorrência';
            document.getElementById('modalOcorrencia').showModal();
        }

        function editarOcorrencia(oc) {
            document.getElementById('form_id').value = oc.id;
            document.getElementById('form_pedido_id').value = oc.pedido_id;
            document.getElementById('form_tipo').value = oc.tipo;
            document.getElementById('form_motivo').value = oc.motivo;
            document.getElementById('form_estoque').value = oc.retornou_estoque;
            
            document.getElementById('tituloModal').innerText = 'Editar Ocorrência';
            
            
            carregarItens(oc.pedido_id, oc.item_pedido_id);
            
            document.getElementById('modalOcorrencia').showModal();
        }
    </script>
</body>
</html>