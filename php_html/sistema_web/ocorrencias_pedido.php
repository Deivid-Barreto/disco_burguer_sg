<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php'; 
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar_ocorrencia') {
        $pedido_id = $_POST['pedido_id'] ?? null;
        $item_pedido_id = $_POST['item_pedido_id'] ?? null;
        $tipo = $_POST['tipo'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $retornou_estoque = $_POST['retornou_estoque'] ?? 0;

        if (!$pedido_id || !$item_pedido_id || !$tipo || !$motivo) {
            echo "Erro: preencha todos os campos obrigatórios.";
            exit;
        }
        
        if (!in_array($tipo, ['cancelamento', 'troca'])) {
            echo "Erro: tipo de ocorrência inválido.";
            exit;
        }

        $retornou_estoque = (int)$retornou_estoque;

        if (!in_array($retornou_estoque, [0, 1])) {
            echo "Erro: valor de retorno ao estoque inválido.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);

        if (!$stmt->fetch()) {
            echo "Erro: pedido não encontrado.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, pedido_id FROM itens_pedidos WHERE id = ?");
        $stmt->execute([$item_pedido_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo "Erro: item do pedido não encontrado.";
            exit;
        }

        if ($item['pedido_id'] != $pedido_id) {
            echo "Erro: o item selecionado não pertence ao pedido informado.";
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO ocorrencias_pedido (
                pedido_id,
                item_pedido_id,
                funcionario_id,
                tipo,
                motivo,
                retornou_estoque
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $pedido_id,
            $item_pedido_id,
            $funcionario_id,
            $tipo,
            $motivo,
            $retornou_estoque
        ]);

        echo "Ocorrência adicionada com sucesso!";
        exit;
    }

    if ($acao === 'listar_ocorrencias') {
        $stmt = $pdo->query("
            SELECT
                op.id,
                op.pedido_id,
                op.item_pedido_id,
                op.tipo,
                op.motivo,
                op.retornou_estoque,
                op.data_ocorrencia
            FROM ocorrencias_pedido op
            ORDER BY op.data_ocorrencia DESC
        ");

        $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$ocorrencias) {
            echo "<p>Nenhuma ocorrência cadastrada.</p>";
            exit;
        }

        foreach ($ocorrencias as $ocorrencia) {
            $tipo = [
                'cancelamento' => 'Cancelamento',
                'troca' => 'Troca'
            ];

            $estoque = [
                0 => 'Não',
                1 => 'Sim'
            ];

            echo "
                <div>
                    <strong>Ocorrência #{$ocorrencia['id']}</strong><br>
                    Pedido: #{$ocorrencia['pedido_id']}<br>
                    Item: #{$ocorrencia['item_pedido_id']}<br>
                    Tipo: " . ($tipo[$ocorrencia['tipo']] ?? $ocorrencia['tipo']) . "<br>
                    Motivo: " . htmlspecialchars($ocorrencia['motivo']) . "<br>
                    Retornou ao estoque: " . ($estoque[$ocorrencia['retornou_estoque']] ?? 'Não') . "<br>
                    Data: " . date('d/m/Y H:i', strtotime($ocorrencia['data_ocorrencia'])) . "
                </div>
                <hr>
            ";
        }
        exit;
    }

    if ($acao === 'listar_pedidos') {
        $stmt = $pdo->query("SELECT id FROM pedidos ORDER BY id DESC");
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pedidos) {
            echo "<option value=''>Nenhum pedido encontrado</option>";
            exit;
        }

        echo "<option value=''>Selecione o pedido</option>";
        foreach ($pedidos as $pedido) {
            echo "<option value='{$pedido['id']}'>Pedido #{$pedido['id']}</option>";
        }
        exit;
    }

    if ($acao === 'listar_itens') {
        $pedido_id = $_POST['pedido_id'] ?? null;

        if (!$pedido_id) {
            echo "<option value=''>Selecione um pedido primeiro</option>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM itens_pedidos WHERE pedido_id = ? ORDER BY id ASC");
        $stmt->execute([$pedido_id]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$itens) {
            echo "<option value=''>Nenhum item encontrado</option>";
            exit;
        }

        echo "<option value=''>Selecione o item</option>";
        foreach ($itens as $item) {
            echo "<option value='{$item['id']}'>Item #{$item['id']}</option>";
        }
        exit;
    }
}

$ocorrencias = $pdo->query("
    SELECT
        op.id,
        op.pedido_id,
        op.item_pedido_id,
        op.tipo,
        op.motivo,
        op.retornou_estoque,
        op.data_ocorrencia
    FROM ocorrencias_pedido op
    ORDER BY op.data_ocorrencia DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pedidos = $pdo->query("SELECT id FROM pedidos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ocorrências</title>
</head>
<body>
    <h1>Ocorrências dos Pedidos</h1>
    <button onclick="abrirOcorrencia()">NOVA OCORRÊNCIA</button>
    <button onclick="carregarOcorrencias()">ATUALIZAR</button>

    <div id="container-ocorrencias">
        <?php if (!$ocorrencias): ?>
            <p>Nenhuma ocorrência cadastrada.</p>
        <?php else: ?>
            <?php foreach ($ocorrencias as $ocorrencia): ?>
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
                    Data: <?= date('d/m/Y H:i', strtotime($ocorrencia['data_ocorrencia'])) ?>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <dialog id="modalOcorrencia">
        <h2>Nova Ocorrência</h2>
        <form id="formOcorrencia" onsubmit="salvarOcorrencia(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="adicionar_ocorrencia">

            <label>Pedido:</label>
            <select name="pedido_id" id="pedidoSelect" required>
                <option value="">Selecione o pedido</option>
                <?php foreach ($pedidos as $pedido): ?>
                    <option value="<?= $pedido['id'] ?>">Pedido #<?= $pedido['id'] ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Item do pedido:</label>
            <select name="item_pedido_id" id="itemSelect" required>
                <option value="">Selecione o pedido primeiro</option>
            </select>
            <br><br>

            <label>Tipo:</label>
            <select name="tipo" required>
                <option value="">Selecione</option>
                <option value="cancelamento">Cancelamento</option>
                <option value="troca">Troca</option>
            </select>
            <br><br>

            <label>Motivo:</label><br>
            <textarea name="motivo" rows="4" required></textarea>
            <br><br>

            <label>Retornou ao estoque?</label>
            <select name="retornou_estoque" required>
                <option value="0">Não</option>
                <option value="1">Sim</option>
            </select>
            <br><br>

            <button type="submit">SALVAR</button>
            <button type="button" onclick="fecharOcorrencia()">CANCELAR</button>
        </form>
    </dialog>

    <script>
        function abrirOcorrencia() {
            document.getElementById('modalOcorrencia').showModal();
        }

        function fecharOcorrencia() {
            document.getElementById('modalOcorrencia').close();
        }

        document.getElementById('pedidoSelect').addEventListener('change', async function() {
            let pedido_id = this.value;
            let selectItem = document.getElementById('itemSelect');

            if (!pedido_id) {
                selectItem.innerHTML = `<option value="">Selecione o pedido primeiro</option>`;
                return;
            }

            let formData = new FormData();
            formData.append('acao', 'listar_itens');
            formData.append('pedido_id', pedido_id);

            let req = await fetch('', { method: 'POST', body: formData });
            selectItem.innerHTML = await req.text();
        });

        async function salvarOcorrencia(e) {
            e.preventDefault();
            let form = e.target;
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            let texto = await req.text();

            alert(texto);

            if (!texto.startsWith('Erro')) {
                form.reset();
                document.getElementById('itemSelect').innerHTML = `<option value="">Selecione o pedido primeiro</option>`;
                fecharOcorrencia();
                carregarOcorrencias();
            }
        }

        async function carregarOcorrencias() {
            let formData = new FormData();
            formData.append('acao', 'listar_ocorrencias');

            let req = await fetch('', { method: 'POST', body: formData });
            let html = await req.text();
            document.getElementById('container-ocorrencias').innerHTML = html;
        }
    </script>
</body>
</html>