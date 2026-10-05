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

    if ($acao === 'adicionar_pagamento') {
        $pedido_id = $_POST['pedido_id'] ?? null;
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $valor = $_POST['valor'] ?? null;
        $data_compensacao_prevista = $_POST['data_compensacao_prevista'] ?? null;
        $status_compensacao = $_POST['status_compensacao'] ?? 'liquidado';

        if (!$pedido_id || !$forma_pagamento || !$valor) {
            echo "Erro: preencha todos os campos obrigatórios.";
            exit;
        }

        if ($valor <= 0) {
            echo "Erro: o valor deve ser maior que zero.";
            exit;
        }

        $formas_validas = ['cartao_debito', 'cartao_credito', 'dinheiro', 'pix'];
        if (!in_array($forma_pagamento, $formas_validas)) {
            echo "Erro: forma de pagamento inválida.";
            exit;
        }

        $status_validos = ['liquidado', 'pendente_repasse'];
        if (!in_array($status_compensacao, $status_validos)) {
            echo "Erro: status de compensação inválido.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);

        if (!$stmt->fetch()) {
            echo "Erro: pedido não encontrado.";
            exit;
        }

        $stmtCat = $pdo->prepare("SELECT id FROM categorias_financeiras WHERE tipo = 'entrada' LIMIT 1");
        $stmtCat->execute();
        $categoria_id = $stmtCat->fetchColumn();

        if (!$categoria_id) {
            echo "Erro: nenhuma categoria financeira de entrada cadastrada.";
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO pagamentos_pedido (
                    pedido_id,
                    forma_pagamento,
                    valor,
                    data_compensacao_prevista,
                    status_compensacao
                )
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$pedido_id, $forma_pagamento, $valor, $data_compensacao_prevista ?: null, $status_compensacao]);

            $descricao_fluxo = "Recebimento referente ao Pedido #" . $pedido_id . " (" . $forma_pagamento . ")";
            $stmtFluxo = $pdo->prepare("
                INSERT INTO fluxo_caixa (
                    pedido_id,
                    categoria_id,
                    funcionario_id,
                    descricao,
                    valor
                )
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtFluxo->execute([$pedido_id, $categoria_id, $funcionario_id, $descricao_fluxo, $valor]);

            $pdo->commit();
            echo "Pagamento adicionado com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Erro ao processar o pagamento.";
        }
        exit;
    }

    if ($acao === 'listar_pagamentos') {
        $stmt = $pdo->query("
            SELECT
                pp.id,
                pp.pedido_id,
                pp.forma_pagamento,
                pp.valor,
                pp.data_pagamento,
                pp.data_compensacao_prevista,
                pp.status_compensacao
            FROM pagamentos_pedido pp
            ORDER BY pp.data_pagamento DESC
        ");
        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pagamentos) {
            echo "<p>Nenhum pagamento cadastrado.</p>";
            exit;
        }

        foreach ($pagamentos as $pagamento) {
            $forma = [
                'cartao_debito' => 'Cartão de Débito',
                'cartao_credito' => 'Cartão de Crédito',
                'dinheiro' => 'Dinheiro',
                'pix' => 'PIX'
            ];
            $status = [
                'liquidado' => 'Liquidado',
                'pendente_repasse' => 'Pendente de Repasse'
            ];

            echo "
                <div>
                    <strong>Pagamento #{$pagamento['id']}</strong><br>
                    Pedido: #{$pagamento['pedido_id']}<br>
                    Forma: " . ($forma[$pagamento['forma_pagamento']] ?? $pagamento['forma_pagamento']) . "<br>
                    Valor: R$ " . number_format($pagamento['valor'], 2, ',', '.') . "<br>
                    Data: " . date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) . "<br>
                    Status: " . ($status[$pagamento['status_compensacao']] ?? $pagamento['status_compensacao']) . "
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
}

$pagamentos = $pdo->query("
    SELECT
        pp.id,
        pp.pedido_id,
        pp.forma_pagamento,
        pp.valor,
        pp.data_pagamento,
        pp.data_compensacao_prevista,
        pp.status_compensacao
    FROM pagamentos_pedido pp
    ORDER BY pp.data_pagamento DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pedidos = $pdo->query("SELECT id FROM pedidos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos</title>
</head>
<body>
    <h1>Pagamentos</h1>
    <button onclick="abrirPagamento()">NOVO PAGAMENTO</button>
    <button onclick="carregarPagamentos()">ATUALIZAR</button>

    <div id="container-pagamentos">
        <?php if (!$pagamentos): ?>
            <p>Nenhum pagamento cadastrado.</p>
        <?php else: ?>
            <?php foreach ($pagamentos as $pagamento): ?>
                <?php
                $formas = [
                    'cartao_debito' => 'Cartão de Débito',
                    'cartao_credito' => 'Cartão de Crédito',
                    'dinheiro' => 'Dinheiro',
                    'pix' => 'PIX'
                ];
                $status = [
                    'liquidado' => 'Liquidado',
                    'pendente_repasse' => 'Pendente de Repasse'
                ];
                ?>
                <div>
                    <strong>Pagamento #<?= $pagamento['id'] ?></strong><br>
                    Pedido: #<?= $pagamento['pedido_id'] ?><br>
                    Forma: <?= htmlspecialchars($formas[$pagamento['forma_pagamento']] ?? $pagamento['forma_pagamento']) ?><br>
                    Valor: R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?><br>
                    Data: <?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?><br>
                    Status: <?= htmlspecialchars($status[$pagamento['status_compensacao']] ?? $pagamento['status_compensacao']) ?>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <dialog id="modalPagamento">
        <h2>Novo Pagamento</h2>
        <form id="formPagamento" onsubmit="salvarPagamento(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="adicionar_pagamento">

            <label>Pedido:</label>
            <select name="pedido_id" required>
                <option value="">Selecione o pedido</option>
                <?php foreach ($pedidos as $pedido): ?>
                    <option value="<?= $pedido['id'] ?>">Pedido #<?= $pedido['id'] ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Forma de pagamento:</label>
            <select name="forma_pagamento" required>
                <option value="">Selecione</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="pix">PIX</option>
            </select>
            <br><br>

            <label>Valor:</label>
            <input type="number" name="valor" step="0.01" min="0.01" required>
            <br><br>

            <label>Data de compensação prevista:</label>
            <input type="date" name="data_compensacao_prevista">
            <br><br>

            <label>Status da compensação:</label>
            <select name="status_compensacao" required>
                <option value="liquidado">Liquidado</option>
                <option value="pendente_repasse">Pendente de Repasse</option>
            </select>
            <br><br>

            <button type="submit">SALVAR</button>
            <button type="button" onclick="fecharPagamento()">CANCELAR</button>
        </form>
    </dialog>

    <script>
        function abrirPagamento() {
            document.getElementById('modalPagamento').showModal();
        }

        function fecharPagamento() {
            document.getElementById('modalPagamento').close();
        }

        async function salvarPagamento(e) {
            e.preventDefault();
            let form = e.target;
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            let texto = await req.text();

            alert(texto);

            if (!texto.startsWith('Erro')) {
                form.reset();
                fecharPagamento();
                carregarPagamentos();
            }
        }

        async function carregarPagamentos() {
            let formData = new FormData();
            formData.append('acao', 'listar_pagamentos');

            let req = await fetch('', { method: 'POST', body: formData });
            let html = await req.text();
            document.getElementById('container-pagamentos').innerHTML = html;
        }
    </script>
</body>
</html>