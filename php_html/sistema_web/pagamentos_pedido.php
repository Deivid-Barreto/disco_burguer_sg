<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php'; 
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

<<<<<<< HEAD
// Função auxiliar para renderizar as seções de cards (usada tanto no carregamento inicial quanto via AJAX)
function renderizarSecoesPedidos($pdo) {
    $sqlPedidos = "
        SELECT 
            p.id, 
            p.nome_cliente, 
            p.tipo, 
            p.status AS status_pedido,
=======
if (isset($_GET['obter_detalhes_pedido'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $pedido_id = (int)$_GET['obter_detalhes_pedido'];

    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
            p.valor_total,
            COALESCE(SUM(pp.valor), 0) AS total_pago
        FROM pedidos p
        LEFT JOIN pagamentos_pedido pp ON pp.pedido_id = p.id
<<<<<<< HEAD
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
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
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
<<<<<<< HEAD
        $valor = (float)($_POST['valor'] ?? 0);
        $data_compensacao_prevista = $_POST['data_compensacao_prevista'] ?? null;
        $status_compensacao = $_POST['status_compensacao'] ?? 'pendente';

        if (!$pedido_id || !$forma_pagamento || $valor <= 0) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: preencha todos os campos obrigatórios corretamente.']);
=======
        $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
        $data_compensacao_prevista = !empty($_POST['data_compensacao_prevista']) ? $_POST['data_compensacao_prevista'] : date('Y-m-d');
        $status_compensacao = $_POST['status_compensacao'] ?? 'liquidado';

        if (!$pedido_id || !$forma_pagamento || $valor <= 0) {
            echo "Erro: preencha todos os campos obrigatórios com valores válidos.";
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
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

<<<<<<< HEAD
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
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
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
            $stmt->execute([$pedido_id, $forma_pagamento, $valor_efetivo_pagamento, $data_compensacao_prevista, $status_compensacao]);

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
            $stmtFluxo->execute([$pedido_id, $categoria_id, $funcionario_id, $descricao_fluxo, $valor_efetivo_pagamento]);

            $novo_total_pago = $total_ja_pago + $valor;
            $novo_saldo_restante = $valor_total_pedido - $novo_total_pago;

            if ($novo_saldo_restante <= 0.01) {
                $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET status = 'finalizado' WHERE id = ?");
                $stmtUpdatePedido->execute([$pedido_id]);
            }

            $pdo->commit();
<<<<<<< HEAD
            
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
=======

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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
        }
        exit;
    }
}

<<<<<<< HEAD
// Carga inicial dos cards
$dadosIniciais = renderizarSecoesPedidos($pdo);
$pedidosParaPagamento = $dadosIniciais['pedidosParaPagamento'];
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Pagamentos - Caixa</title>
</head>
<body>
<<<<<<< HEAD
    <h1>Caixa - Gestão de Pagamentos</h1>

    <div id="container-principal">
        <?= $dadosIniciais['html'] ?>
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
    </div>

    <!-- Modal para Registrar Pagamento / Divisão de Conta -->
    <dialog id="modalPagamento">
        <h2 id="tituloModalPagamento">Registrar Pagamento</h2>
        <form id="formPagamento" onsubmit="salvarPagamento(event)">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="adicionar_pagamento">
            <input type="hidden" name="pedido_id" id="inputPedidoId">

<<<<<<< HEAD
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
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
        </form>
    </dialog>

    <script>
<<<<<<< HEAD
        let pedidoAtualId = null;
        let listaPedidosParaPagamento = <?= json_encode($pedidosParaPagamento) ?>;

        function abrirPagamentoParaPedido(pedidoId, saldoRestante, nomeCliente) {
            pedidoAtualId = pedidoId;
            document.getElementById('inputPedidoId').value = pedidoId;
            document.getElementById('spanNomeCliente').innerText = nomeCliente;
            document.getElementById('spanSaldoRestante').innerText = saldoRestante.toFixed(2).replace('.', ',');
            document.getElementById('inputValor').value = saldoRestante.toFixed(2);
            document.getElementById('inputValor').max = saldoRestante;
            
=======
        function obterDataAtualFormatada() {
            let hoje = new Date();
            let ano = hoje.getFullYear();
            let mes = String(hoje.getMonth() + 1).padStart(2, '0');
            let dia = String(hoje.getDate()).padStart(2, '0');
            return `${ano}-${mes}-${dia}`;
        }

        function abrirPagamento() {
            document.getElementById('input_data_compensacao').value = obterDataAtualFormatada();
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b
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
<<<<<<< HEAD
            let formData = new FormData(form);
=======
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
>>>>>>> c4b970da7695144844f8b03b13b67c207e3bc20b

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