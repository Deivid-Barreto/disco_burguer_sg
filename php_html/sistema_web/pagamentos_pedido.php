<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php'; 
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

// Função auxiliar para renderizar as seções de cards (usada tanto no carregamento inicial quanto via AJAX)
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
        WHERE p.status != 'cancelado'
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

    // Retorna um array com o HTML de cada seção e os dados para o select do modal
    ob_start();
    ?>
    <h2>Pedidos Pendentes</h2>
    <div id="lista-pendentes">
        <?php if (empty($pedidosPendentes)): ?>
            <p>Nenhum pedido pendente sem pagamento.</p>
        <?php else: ?>
            <?php foreach ($pedidosPendentes as $p): ?>
                <div style="border: 1px solid #ccc; padding: 12px; margin-bottom: 10px; cursor: pointer;" onclick="abrirPagamentoParaPedido(<?= $p['id'] ?>, <?= $p['valor_total'] ?>, '<?= htmlspecialchars($p['nome_cliente'], ENT_QUOTES) ?>')">
                    <strong>Pedido #<?= $p['id'] ?></strong> - Cliente: <?= htmlspecialchars($p['nome_cliente']) ?> (<?= ucfirst($p['tipo']) ?>)<br>
                    Valor Total: R$ <?= number_format($p['valor_total'], 2, ',', '.') ?> | Pago: R$ 0,00<br>
                    <strong>Status: Sem Pagamento</strong>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>

    <h2>Pedidos com Pagamento Parcial</h2>
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

    // Pedidos que possuem pendência para popular o select do modal
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
        $valor = (float)($_POST['valor'] ?? 0);
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

        $saldo_restante = $valor_total_pedido - $total_ja_pago;

        if ($valor > $saldo_restante + 0.01) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: O valor informado excede o saldo restante da conta (R$ ' . number_format($saldo_restante, 2, ',', '.') . ').']);
            exit;
        }

        $stmtCat = $pdo->prepare("SELECT id FROM categorias_financeiras WHERE tipo = 'entrada' LIMIT 1");
        $stmtCat->execute();
        $categoria_id = $stmtCat->fetchColumn();

        if (!$categoria_id) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: nenhuma categoria financeira de entrada cadastrada.']);
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
            $novo_saldo_restante = $valor_total_pedido - $novo_total_pago;

            if ($novo_saldo_restante <= 0.01) {
                $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET status = 'finalizado' WHERE id = ?");
                $stmtUpdatePedido->execute([$pedido_id]);
            }

            $pdo->commit();
            
            // Renderiza novamente os cards atualizados para retornar via AJAX
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
            $pdo->rollBack();
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar o pagamento: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Carga inicial dos cards
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

    <!-- Modal para Registrar Pagamento / Divisão de Conta -->
    <dialog id="modalPagamento">
        <h2 id="tituloModalPagamento">Registrar Pagamento</h2>
        <form id="formPagamento" onsubmit="salvarPagamento(event)">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="adicionar_pagamento">
            <input type="hidden" name="pedido_id" id="inputPedidoId">

            <p><strong>Cliente:</strong> <span id="spanNomeCliente"></span></p>
            <p><strong>Saldo Restante:</strong> R$ <span id="spanSaldoRestante">0,00</span></p>
            <br>

            <label>Forma de pagamento:</label><br>
            <select name="forma_pagamento" required>
                <option value="">Selecione</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="pix">PIX</option>
            </select>
            <br><br>

            <label>Valor a Pagar (R$):</label><br>
            <input type="number" name="valor" id="inputValor" step="0.01" min="0.01" required>
            <small>(Informe o valor referente à parte que está sendo paga agora).</small>
            <br><br>

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
            
            document.getElementById('modalPagamento').showModal();
        }

        function fecharPagamento() {
            document.getElementById('modalPagamento').close();
        }

        async function salvarPagamento(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);

            let req = await fetch('', { method: 'POST', body: formData });
            let resultado = await req.json();

            // Atualiza o token CSRF retornado ou mantido
            if (resultado.sucesso) {
                // Atualiza o HTML dos cards na tela via AJAX
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
                }
            } else {
                alert(resultado.mensagem);
            }
        }
    </script>
</body>
</html>