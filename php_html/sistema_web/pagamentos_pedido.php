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

function renderizarSecoesPedidos($pdo) {
    $sqlPedidos = "
        SELECT 
            p.id, 
            p.nome_cliente, 
            p.tipo, 
            p.status AS status_pedido,
            p.valor_total,
            COALESCE(SUM(pp.valor), 0) AS total_pago
        FROM pedidos p
        LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
        WHERE p.status = 'aguardando_pagamento'
        GROUP BY p.id, p.nome_cliente, p.tipo, p.status, p.valor_total
        ORDER BY p.id DESC
    ";
    $todosPedidos = $pdo->query($sqlPedidos)->fetchAll(PDO::FETCH_ASSOC);

    $pedidosPendentes = []; 
    $pedidosParciais = [];  
    $pedidosLiquidados = [];

    foreach ($todosPedidos as $pedido) {
        $total = (float)$pedido['valor_total'];
        $pago = (float)$pedido['total_pago'];

        if ($pago <= 0.005) {
            $pedidosPendentes[] = $pedido;
        } elseif ($pago < $total - 0.005) {
            $pedidosParciais[] = $pedido;
        } else {
            $pedidosLiquidados[] = $pedido;
        }
    }

    ob_start();
    ?>
    <h2>Aguardando Pagamento</h2>
    <div id="lista-pendentes">
        <?php if (empty($pedidosPendentes)): ?>
            <p>Nenhum pedido aguardando pagamento.</p>
        <?php else: ?>
            <?php foreach ($pedidosPendentes as $p): ?>
                <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 10px; cursor: pointer;" onclick="abrirPagamentoParaPedido(<?= $p['id'] ?>, <?= $p['valor_total'] ?>, '<?= htmlspecialchars($p['nome_cliente'], ENT_QUOTES) ?>')">
                    <strong>Pedido #<?= $p['id'] ?></strong> - Cliente: <?= htmlspecialchars($p['nome_cliente']) ?> (<?= ucfirst($p['tipo']) ?>)<br>
                    Valor Total: R$ <?= number_format($p['valor_total'], 2, ',', '.') ?> | Pago: R$ 0,00<br>
                    <strong>Status: Aguardando Pagamento</strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>

    <h2>Pagamento Parcial</h2>
    <div id="lista-parciais">
        <?php if (empty($pedidosParciais)): ?>
            <p>Nenhum pedido com pagamento parcial no momento.</p>
        <?php else: ?>
            <?php foreach ($pedidosParciais as $p): ?>
                <?php $saldo_restante = $p['valor_total'] - $p['total_pago']; ?>
                <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 10px; cursor: pointer;" onclick="abrirPagamentoParaPedido(<?= $p['id'] ?>, <?= $saldo_restante ?>, '<?= htmlspecialchars($p['nome_cliente'], ENT_QUOTES) ?>')">
                    <strong>Pedido #<?= $p['id'] ?></strong> - Cliente: <?= htmlspecialchars($p['nome_cliente']) ?> (<?= ucfirst($p['tipo']) ?>)<br>
                    Valor Total: R$ <?= number_format($p['valor_total'], 2, ',', '.') ?> | Pago: R$ <?= number_format($p['total_pago'], 2, ',', '.') ?><br>
                    <strong>Saldo Restante: R$ <?= number_format($saldo_restante, 2, ',', '.') ?> [Parcialmente Pago]</strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>

    <h2>Pedidos Liquidados</h2>
    <div id="lista-liquidados">
        <?php if (empty($pedidosLiquidados)): ?>
            <p>Nenhum pedido liquidado.</p>
        <?php else: ?>
            <?php foreach ($pedidosLiquidados as $p): ?>
                <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 10px; background: #f9f9f9;">
                    <strong>Pedido #<?= $p['id'] ?></strong> - Cliente: <?= htmlspecialchars($p['nome_cliente']) ?> (<?= ucfirst($p['tipo']) ?>)<br>
                    Valor Total: R$ <?= number_format($p['valor_total'], 2, ',', '.') ?><br>
                    <span style="color: green; font-weight: bold;">Quitado com Êxito</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    $htmlCards = ob_get_clean();
    $pedidosParaPagamento = array_merge($pedidosPendentes, $pedidosParciais);

    return [
        'html' => $htmlCards,
        'pedidosParaPagamento' => $pedidosParaPagamento
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não autorizada.']);
        exit;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'carregar_cards') {
        $resultadoRender = renderizarSecoesPedidos($pdo);
        echo json_encode([
            'sucesso' => true,
            'html' => $resultadoRender['html'],
            'pedidosParaPagamento' => $resultadoRender['pedidosParaPagamento']
        ]);
        exit;
    }

    if ($acao === 'adicionar_pagamento') {
        $pedido_id = $_POST['pedido_id'] ?? null;
        $forma_pagamento = $_POST['forma_pagamento'] ?? '';
        $valor = round((float)($_POST['valor'] ?? 0), 2);
        
        // Captura o valor recebido e calcula o troco caso seja dinheiro
        $valor_recebido = ($forma_pagamento === 'dinheiro') ? round((float)($_POST['valor_recebido'] ?? $valor), 2) : $valor;
        $troco = max(0, round($valor_recebido - $valor, 2));

        $data_compensacao_prevista = $_POST['data_compensacao_prevista'] ?? null;
        $status_compensacao = $_POST['status_compensacao'] ?? 'pendente';

        if (!$pedido_id || !$forma_pagamento || $valor <= 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: preencha todos os campos obrigatórios corretamente.']);
            exit;
        }

        $formas_validas = ['cartao_debito', 'cartao_credito', 'dinheiro', 'pix'];
        if (!in_array($forma_pagamento, $formas_validas)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: forma de pagamento inválida.']);
            exit;
        }

        $status_validos = ['pendente', 'liquidado', 'pendente_repasse'];
        if (!in_array($status_compensacao, $status_validos)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: status de compensação inválido.']);
            exit;
        }

        $stmtPedido = $pdo->prepare("SELECT valor_total, status FROM pedidos WHERE id = ?");
        $stmtPedido->execute([$pedido_id]);
        $pedidoData = $stmtPedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedidoData) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: pedido não encontrado.']);
            exit;
        }

        $valor_total_pedido = (float)$pedidoData['valor_total'];

        $stmtPago = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) FROM pagamentos_pedido WHERE pedido_id = ?");
        $stmtPago->execute([$pedido_id]);
        $total_ja_pago = (float)$stmtPago->fetchColumn();

        $saldo_restante = round($valor_total_pedido - $total_ja_pago, 2);

        if ($valor > $saldo_restante + 0.01) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: O valor informado excede o saldo restante da conta (R$ ' . number_format($saldo_restante, 2, ',', '.') . ').']);
            exit;
        }

        // Busca a categoria financeira fixa do sistema
        $stmtCat = $pdo->prepare("SELECT id FROM categorias_financeiras WHERE tipo_saida = 'fixo' AND tipo = 'entrada' LIMIT 1");
        $stmtCat->execute();
        $categoria_id = $stmtCat->fetchColumn();

        if (!$categoria_id) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: nenhuma categoria financeira fixa cadastrada.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO pagamentos_pedido (
                    pedido_id,
                    forma_pagamento,
                    valor,
                    valor_recebido,
                    troco,
                    data_compensacao_prevista,
                    status_compensacao
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$pedido_id, $forma_pagamento, $valor, $valor_recebido, $troco, $data_compensacao_prevista, $status_compensacao]);

            $descricao_fluxo = "Recebimento referente ao Pedido #" . $pedido_id . " (" . str_replace('_', ' ', $forma_pagamento) . ")";
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

            $novo_total_pago = $total_ja_pago + $valor;
            $novo_saldo_restante = round($valor_total_pedido - $novo_total_pago, 2);

            if ($novo_saldo_restante <= 0.01) {
                $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET status = 'finalizado' WHERE id = ?");
                $stmtUpdatePedido->execute([$pedido_id]);
            }

            $pdo->commit();
            
            $resultadoRender = renderizarSecoesPedidos($pdo);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Pagamento registrado com sucesso!',
                'saldo_restante' => max(0, $novo_saldo_restante),
                'quitado' => ($novo_saldo_restante <= 0.01),
                'htmlCards' => $resultadoRender['html'],
                'pedidosParaPagamento' => $resultadoRender['pedidosParaPagamento']
            ]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar o pagamento: ' . $e->getMessage()]);
        }
        exit;
    }
}

$dadosIniciais = renderizarSecoesPedidos($pdo);
$pedidosParaPagamento = $dadosIniciais['pedidosParaPagamento'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Pagamentos - Caixa</title>
</head>
<body>
    <h1>Caixa - Gestão de Pagamentos</h1>

    <div id="container-principal">
        <?= $dadosIniciais['html'] ?>
    </div>

    <dialog id="modalPagamento">
        <h2 id="tituloModalPagamento">Registrar Pagamento / Divisão</h2>
        <form id="formPagamento" onsubmit="salvarPagamento(event)">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="adicionar_pagamento">
            <input type="hidden" name="pedido_id" id="inputPedidoId">

            <p><strong>Cliente:</strong> <span id="spanNomeCliente"></span></p>
            <p><strong>Saldo Restante:</strong> R$ <span id="spanSaldoRestante">0,00</span></p>
            <br>

            <label>Forma de pagamento:</label><br>
            <select name="forma_pagamento" id="selectFormaPagto" onchange="verificarDinheiro()" required>
                <option value="">Selecione</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="pix">PIX</option>
            </select>
            <br><br>

            <label>Valor a Abater / Pagar (Permite divisão da conta):</label><br>
            <input type="number" name="valor" id="inputValor" step="0.01" min="0.01" oninput="calcularTroco()" required>
            <br><br>

            <div id="blocoDinheiro" style="display: none;">
                <label>Valor em Dinheiro Entregue pelo Cliente:</label><br>
                <!-- ATENÇÃO: Adicionado o atributo name="valor_recebido" aqui -->
                <input type="number" name="valor_recebido" id="inputValorRecebido" step="0.01" min="0.01" oninput="calcularTroco()">
                <br>
                <p style="margin-top: 8px;"><strong>Troco a Devolver: R$ <span id="spanTroco" style="color: green;">0,00</span></strong></p>
                <br>
            </div>

            <label>Data de compensação prevista:</label><br>
            <input type="date" name="data_compensacao_prevista">
            <br><br>

            <label>Status da compensação:</label><br>
            <select name="status_compensacao" required>
                <option value="pendente">Pendente</option>
                <option value="liquidado">Liquidado</option>
                <option value="pendente_repasse">Pendente de Repasse (Cartão de Crédito)</option>
            </select>
            <br><br>

            <button type="submit">SALVAR PAGAMENTO</button>
            <button type="button" onclick="fecharPagamento()">CONCLUÍDO / FECHAR</button>
        </form>
    </dialog>

    <script>
        let pedidoAtualId = null;
        let listaPedidosParaPagamento = <?= json_encode($pedidosParaPagamento) ?>;

        function abrirPagamentoParaPedido(pedidoId, saldoRestante, nomeCliente) {
            pedidoAtualId = pedidoId;
            document.getElementById('inputPedidoId').value = pedidoId;
            document.getElementById('spanNomeCliente').innerText = nomeCliente;
            document.getElementById('spanSaldoRestante').innerText = saldoRestante.toFixed(2).replace('.', ',');
            document.getElementById('inputValor').value = saldoRestante.toFixed(2);
            document.getElementById('inputValor').max = saldoRestante;
            
            document.getElementById('selectFormaPagto').value = '';
            document.getElementById('blocoDinheiro').style.display = 'none';
            document.getElementById('modalPagamento').showModal();
        }

        function fecharPagamento() {
            document.getElementById('formPagamento').reset();
            document.getElementById('modalPagamento').close();
        }

        function verificarDinheiro() {
            let forma = document.getElementById('selectFormaPagto').value;
            let bloco = document.getElementById('blocoDinheiro');
            if (forma === 'dinheiro') {
                bloco.style.display = 'block';
                document.getElementById('inputValorRecebido').value = document.getElementById('inputValor').value;
                calcularTroco();
            } else {
                bloco.style.display = 'none';
            }
        }

        function calcularTroco() {
            let valorDevido = parseFloat(document.getElementById('inputValor').value) || 0;
            let valorRecebido = parseFloat(document.getElementById('inputValorRecebido').value) || 0;
            let troco = valorRecebido - valorDevido;
            document.getElementById('spanTroco').innerText = troco > 0 ? troco.toFixed(2).replace('.', ',') : '0,00';
        }

        async function salvarPagamento(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);

            let req = await fetch('', { method: 'POST', body: formData });
            let resultado = await req.json();

            if (resultado.sucesso) {
                document.getElementById('container-principal').innerHTML = resultado.htmlCards;
                listaPedidosParaPagamento = resultado.pedidosParaPagamento;

                alert(resultado.mensagem);

                if (resultado.quitado) {
                    alert('Conta totalmente quitada!');
                    fecharPagamento();
                } else {
                    document.getElementById('spanSaldoRestante').innerText = resultado.saldo_restante.toFixed(2).replace('.', ',');
                    document.getElementById('inputValor').value = resultado.saldo_restante.toFixed(2);
                    document.getElementById('inputValor').max = resultado.saldo_restante;
                    form.querySelector('select[name="forma_pagamento"]').value = '';
                    document.getElementById('blocoDinheiro').style.display = 'none';
                }
            } else {
                alert(resultado.mensagem);
            }
        }
    </script>
</body>
</html>