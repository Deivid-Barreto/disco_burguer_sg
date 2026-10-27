<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php'; 
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if (isset($_GET['obter_detalhes_pedido'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $pedido_id = (int)$_GET['obter_detalhes_pedido'];

    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.valor_total,
            COALESCE(SUM(pp.valor), 0) AS total_pago
        FROM pedidos p
        LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
        WHERE p.id = ?
        GROUP BY p.id, p.valor_total
    ");
    $stmt->execute([$pedido_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dados) {
        $valor_total = (float)$dados['valor_total'];
        $total_pago = (float)$dados['total_pago'];
        $saldo_restante = max(0, $valor_total - $total_pago);

        echo json_encode([
            'sucesso' => true,
            'valor_total' => $valor_total,
            'total_pago' => $total_pago,
            'saldo_restante' => $saldo_restante
        ]);
    } else {
        echo json_encode(['sucesso' => false]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar_pagamento') {
        $pedido_id = $_POST['pedido_id'] ?? null;
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
        $data_compensacao_prevista = !empty($_POST['data_compensacao_prevista']) ? $_POST['data_compensacao_prevista'] : date('Y-m-d');
        $status_compensacao = $_POST['status_compensacao'] ?? 'liquidado';

        if (!$pedido_id || !$forma_pagamento || $valor <= 0) {
            echo "Erro: preencha todos os campos obrigatórios com valores válidos.";
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

        $stmt = $pdo->prepare("
            SELECT 
                p.id, 
                p.valor_total,
                COALESCE(SUM(pp.valor), 0) AS total_pago
            FROM pedidos p
            LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
            WHERE p.id = ?
            GROUP BY p.id, p.valor_total
        ");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            echo "Erro: pedido não encontrado.";
            exit;
        }

        $valor_total_pedido = (float)$pedido['valor_total'];
        $total_pago = (float)$pedido['total_pago'];
        $saldo_restante = max(0, $valor_total_pedido - $total_pago);

        if ($saldo_restante <= 0) {
            echo "Erro: este pedido já está totalmente quitado.";
            exit;
        }

        $troco = 0;
        $valor_efetivo_pagamento = $valor;

        if ($valor > $saldo_restante) {
            if ($forma_pagamento === 'dinheiro') {
                $troco = $valor - $saldo_restante;
                $valor_efetivo_pagamento = $saldo_restante;
            } else {
                echo "Erro: o valor digitado (R$ " . number_format($valor, 2, ',', '.') . ") é maior do que o saldo restante do pedido (R$ " . number_format($saldo_restante, 2, ',', '.') . ").";
                exit;
            }
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
            $stmt->execute([$pedido_id, $forma_pagamento, $valor_efetivo_pagamento, $data_compensacao_prevista, $status_compensacao]);

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
            $stmtFluxo->execute([$pedido_id, $categoria_id, $funcionario_id, $descricao_fluxo, $valor_efetivo_pagamento]);

            $pdo->commit();

            if ($troco > 0) {
                echo "Pagamento registrado com sucesso! TROCO: R$ " . number_format($troco, 2, ',', '.');
            } else {
                echo "Pagamento adicionado com sucesso!";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "Erro ao processar o pagamento.";
        }
        exit;
    }

    if ($acao === 'listar_pagamentos') {
        $status_filtro = $_POST['status_filtro'] ?? 'pendente_repasse';

        $sql = "
            SELECT
                pp.id,
                pp.pedido_id,
                pp.forma_pagamento,
                pp.valor,
                pp.data_pagamento,
                pp.data_compensacao_prevista,
                pp.status_compensacao,
                p.valor_total,
                (
                    SELECT COALESCE(SUM(sub_pp.valor), 0)
                    FROM pagamentos_pedido sub_pp
                    WHERE sub_pp.pedido_id = pp.pedido_id
                ) AS total_pago
            FROM pagamentos_pedido pp
            INNER JOIN pedidos p ON p.id = pp.pedido_id
        ";

        $params = [];
        if (!empty($status_filtro)) {
            $sql .= " WHERE pp.status_compensacao = ? ";
            $params[] = $status_filtro;
        }

        $sql .= " ORDER BY pp.data_pagamento DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pagamentos) {
            echo "<p>Nenhum pagamento encontrado.</p>";
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

            $valor_total = (float)$pagamento['valor_total'];
            $total_pago = (float)$pagamento['total_pago'];
            $saldo_restante = max(0, $valor_total - $total_pago);

            echo "
                <div>
                    <strong>Pagamento #{$pagamento['id']}</strong><br>
                    Pedido: #{$pagamento['pedido_id']}<br>
                    Forma: " . ($forma[$pagamento['forma_pagamento']] ?? $pagamento['forma_pagamento']) . "<br>
                    Valor Pago Neste Lançamento: R$ " . number_format($pagamento['valor'], 2, ',', '.') . "<br>
                    Total do Pedido: R$ " . number_format($valor_total, 2, ',', '.') . "<br>
                    <span style='color: green; font-weight: bold;'>Total Pago no Pedido: R$ " . number_format($total_pago, 2, ',', '.') . "</span><br>
                    Saldo Restante: R$ " . number_format($saldo_restante, 2, ',', '.') . "<br>
                    Data do Pagamento: " . date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) . "<br>
                    Compensação Prevista: " . ($pagamento['data_compensacao_prevista'] ? date('d/m/Y', strtotime($pagamento['data_compensacao_prevista'])) : 'N/A') . "<br>
                    Status: " . ($status[$pagamento['status_compensacao']] ?? $pagamento['status_compensacao']) . "
                </div>
                <hr>
            ";
        }
        exit;
    }

    if ($acao === 'listar_pedidos') {
        $stmt = $pdo->query("
            SELECT 
                p.id, 
                p.valor_total,
                COALESCE(SUM(pp.valor), 0) AS total_pago
            FROM pedidos p
            LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
            GROUP BY p.id, p.valor_total
            ORDER BY p.id DESC
        ");
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pedidos) {
            echo "<option value=''>Nenhum pedido encontrado</option>";
            exit;
        }

        echo "<option value=''>Selecione o pedido</option>";
        foreach ($pedidos as $pedido) {
            $saldo = max(0, (float)$pedido['valor_total'] - (float)$pedido['total_pago']);
            echo "<option value='{$pedido['id']}' data-total='{$pedido['valor_total']}' data-pago='{$pedido['total_pago']}' data-saldo='{$saldo}'>Pedido #{$pedido['id']} - Total: R$ " . number_format($pedido['valor_total'], 2, ',', '.') . " (Restante: R$ " . number_format($saldo, 2, ',', '.') . ")</option>";
        }
        exit;
    }
}

$stmtPagamentos = $pdo->prepare("
    SELECT
        pp.id,
        pp.pedido_id,
        pp.forma_pagamento,
        pp.valor,
        pp.data_pagamento,
        pp.data_compensacao_prevista,
        pp.status_compensacao,
        p.valor_total,
        (
            SELECT COALESCE(SUM(sub_pp.valor), 0)
            FROM pagamentos_pedido sub_pp
            WHERE sub_pp.pedido_id = pp.pedido_id
        ) AS total_pago
    FROM pagamentos_pedido pp
    INNER JOIN pedidos p ON p.id = pp.pedido_id
    WHERE pp.status_compensacao = 'pendente_repasse'
    ORDER BY pp.data_pagamento DESC
");
$stmtPagamentos->execute();
$pagamentos = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);

$stmtPedidos = $pdo->query("
    SELECT 
        p.id, 
        p.valor_total,
        COALESCE(SUM(pp.valor), 0) AS total_pago
    FROM pedidos p
    LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
    GROUP BY p.id, p.valor_total
    ORDER BY p.id DESC
");
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pagamentos</title>
</head>
<body>
    <h1>Pagamentos</h1>

    <form id="formFiltroPagamentos" onsubmit="event.preventDefault()">
        <label for="select_filtro_status">Filtrar por Status:</label>
        <select id="select_filtro_status" name="status_filtro" onchange="carregarPagamentos()">
            <option value="pendente_repasse" selected>Pendente de Repasse</option>
            <option value="liquidado">Liquidado</option>
            <option value="">Todos os Status</option>
        </select>
        
        <button type="button" onclick="abrirPagamento()">NOVO PAGAMENTO</button>
        <button type="button" onclick="carregarPagamentos()">ATUALIZAR</button>
    </form>

    <br>

    <div id="container-pagamentos">
        <?php if (!$pagamentos): ?>
            <p>Nenhum pagamento pendente encontrado.</p>
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
                $valor_total = (float)$pagamento['valor_total'];
                $total_pago = (float)$pagamento['total_pago'];
                $saldo_restante = max(0, $valor_total - $total_pago);
                ?>
                <div>
                    <strong>Pagamento #<?= $pagamento['id'] ?></strong><br>
                    Pedido: #<?= $pagamento['pedido_id'] ?><br>
                    Forma: <?= htmlspecialchars($formas[$pagamento['forma_pagamento']] ?? $pagamento['forma_pagamento']) ?><br>
                    Valor Pago Neste Lançamento: R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?><br>
                    Total do Pedido: R$ <?= number_format($valor_total, 2, ',', '.') ?><br>
                    <span style="color: green; font-weight: bold;">Total Pago no Pedido: R$ <?= number_format($total_pago, 2, ',', '.') ?></span><br>
                    Saldo Restante: R$ <?= number_format($saldo_restante, 2, ',', '.') ?><br>
                    Data do Pagamento: <?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?><br>
                    Compensação Prevista: <?= $pagamento['data_compensacao_prevista'] ? date('d/m/Y', strtotime($pagamento['data_compensacao_prevista'])) : 'N/A' ?><br>
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

            <p>
                <label>Pedido:</label><br>
                <select name="pedido_id" id="select_pedido_id" onchange="atualizarValoresPedido(this)" required>
                    <option value="">Selecione o pedido</option>
                    <?php foreach ($pedidos as $pedido): ?>
                        <?php $saldo = max(0, (float)$pedido['valor_total'] - (float)$pedido['total_pago']); ?>
                        <option value="<?= $pedido['id'] ?>" data-total="<?= $pedido['valor_total'] ?>" data-pago="<?= $pedido['total_pago'] ?>" data-saldo="<?= $saldo ?>">
                            Pedido #<?= $pedido['id'] ?> - Total: R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?> (Restante: R$ <?= number_format($saldo, 2, ',', '.') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <div id="info_valores_pedido" style="display: none; background: #f9f9f9; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc;">
                <strong>Total do Pedido:</strong> R$ <span id="lbl_total_pedido">0,00</span><br>
                <strong>Já Pago:</strong> R$ <span id="lbl_total_pago">0,00</span><br>
                <strong>Saldo Restante:</strong> R$ <span id="lbl_saldo_restante">0,00</span>
            </div>

            <fieldset style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd;">
                <legend><strong>Dividir Conta / Pagamento Parcial</strong></legend>
                
                <p>
                    <label>Dividir conta em quantas pessoas?</label><br>
                    <input type="number" id="qtd_pessoas" name="qtd_pessoas" value="1" min="1" step="1" onchange="recalcularDivisao()" onkeyup="recalcularDivisao()">
                </p>

                <p>
                    <label>Tipo de Divisão:</label><br>
                    <select id="tipo_divisao" name="tipo_divisao" onchange="recalcularDivisao()">
                        <option value="igual">Valor Igual por Pessoa</option>
                        <option value="manual">Valor Personalizado</option>
                    </select>
                </p>

                <div id="box_resultado_divisao" style="display: none; background: #eef; padding: 6px; border: 1px dashed #99c;">
                    <strong>Valor por Pessoa:</strong> R$ <span id="lbl_valor_por_pessoa">0,00</span>
                </div>
            </fieldset>

            <p>
                <label>Forma de pagamento:</label><br>
                <select name="forma_pagamento" required>
                    <option value="">Selecione</option>
                    <option value="cartao_debito">Cartão de Débito</option>
                    <option value="cartao_credito">Cartão de Crédito</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="pix">PIX</option>
                </select>
            </p>

            <p>
                <label>Valor a Pagar (Este Lançamento):</label><br>
                <input type="number" id="input_valor_pagamento" name="valor" step="0.01" min="0.01" required>
            </p>

            <p>
                <label>Data de compensação prevista:</label><br>
                <input type="date" id="input_data_compensacao" name="data_compensacao_prevista">
            </p>

            <p>
                <label>Status da compensação:</label><br>
                <select name="status_compensacao" required>
                    <option value="liquidado">Liquidado</option>
                    <option value="pendente_repasse">Pendente de Repasse</option>
                </select>
            </p>

            <p>
                <button type="submit">SALVAR</button>
                <button type="button" onclick="fecharPagamento()">CANCELAR</button>
            </p>
        </form>
    </dialog>

    <script>
        function obterDataAtualFormatada() {
            let hoje = new Date();
            let ano = hoje.getFullYear();
            let mes = String(hoje.getMonth() + 1).padStart(2, '0');
            let dia = String(hoje.getDate()).padStart(2, '0');
            return `${ano}-${mes}-${dia}`;
        }

        function abrirPagamento() {
            document.getElementById('input_data_compensacao').value = obterDataAtualFormatada();
            document.getElementById('modalPagamento').showModal();
        }

        function fecharPagamento() {
            document.getElementById('info_valores_pedido').style.display = 'none';
            document.getElementById('box_resultado_divisao').style.display = 'none';
            document.getElementById('qtd_pessoas').value = '1';
            document.getElementById('tipo_divisao').value = 'igual';
            document.getElementById('formPagamento').reset();
            document.getElementById('modalPagamento').close();
        }

        function atualizarValoresPedido(select) {
            let option = select.options[select.selectedIndex];
            let infoBox = document.getElementById('info_valores_pedido');

            if (!select.value) {
                infoBox.style.display = 'none';
                document.getElementById('box_resultado_divisao').style.display = 'none';
                document.getElementById('input_valor_pagamento').value = '';
                return;
            }

            let total = parseFloat(option.dataset.total) || 0;
            let pago = parseFloat(option.dataset.pago) || 0;
            let saldo = parseFloat(option.dataset.saldo) || 0;

            document.getElementById('lbl_total_pedido').innerText = total.toFixed(2).replace('.', ',');
            document.getElementById('lbl_total_pago').innerText = pago.toFixed(2).replace('.', ',');
            document.getElementById('lbl_saldo_restante').innerText = saldo.toFixed(2).replace('.', ',');

            infoBox.style.display = 'block';

            if (!document.getElementById('input_data_compensacao').value) {
                document.getElementById('input_data_compensacao').value = obterDataAtualFormatada();
            }

            recalcularDivisao();
        }

        function recalcularDivisao() {
            let select = document.getElementById('select_pedido_id');
            if (!select.value) return;

            let option = select.options[select.selectedIndex];
            let saldo = parseFloat(option.dataset.saldo) || 0;
            let total = parseFloat(option.dataset.total) || 0;
            
            let baseCalculo = saldo > 0 ? saldo : total;
            let qtdPessoas = parseInt(document.getElementById('qtd_pessoas').value) || 1;
            let tipoDivisao = document.getElementById('tipo_divisao').value;
            let boxResultado = document.getElementById('box_resultado_divisao');
            let inputValor = document.getElementById('input_valor_pagamento');

            if (qtdPessoas > 1) {
                boxResultado.style.display = 'block';
                let valorPorPessoa = baseCalculo / qtdPessoas;
                document.getElementById('lbl_valor_por_pessoa').innerText = valorPorPessoa.toFixed(2).replace('.', ',');

                if (tipoDivisao === 'igual') {
                    inputValor.value = valorPorPessoa.toFixed(2);
                }
            } else {
                boxResultado.style.display = 'none';
                if (tipoDivisao === 'igual') {
                    inputValor.value = baseCalculo.toFixed(2);
                }
            }
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
            let status = document.getElementById('select_filtro_status').value;
            let formData = new FormData();
            formData.append('acao', 'listar_pagamentos');
            formData.append('status_filtro', status);

            let req = await fetch('', { method: 'POST', body: formData });
            let html = await req.text();
            document.getElementById('container-pagamentos').innerHTML = html;
        }
    </script>
</body>
</html>