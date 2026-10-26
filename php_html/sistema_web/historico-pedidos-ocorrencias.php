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
    $origem = $_POST['origem'] ?? '';

    if ($origem === 'ocorrencia' && $acao === 'inserir') {
        $retornou_estoque = isset($_POST['retornou_estoque']) ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO ocorrencias_pedido (pedido_id, item_pedido_id, funcionario_id, tipo, motivo, retornou_estoque) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['pedido_id'],
            $_POST['item_pedido_id'],
            $funcionario_id,
            $_POST['tipo'],
            $_POST['motivo'],
            $retornou_estoque
        ]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$historico_pedidos = $pdo->query("
    SELECT p.id as pedido_id, p.data_pedido, p.status, p.valor_total, p.nome_cliente,
           i.quantidade, pr.preco_venda, pr.nome as produto_nome
    FROM pedidos p
    LEFT JOIN itens_pedidos i ON p.id = i.pedido_id
    LEFT JOIN produtos pr ON i.produto_id = pr.id
    WHERE p.status = 'finalizado'
    ORDER BY p.data_pedido DESC
")->fetchAll(PDO::FETCH_ASSOC);

$ocorrencias = $pdo->query("
    SELECT o.*, p.id as pedido_codigo, pr.nome as produto_nome, f.nome as funcionario_nome
    FROM ocorrencias_pedido o
    LEFT JOIN pedidos p ON o.pedido_id = p.id
    LEFT JOIN itens_pedidos ip ON o.item_pedido_id = ip.id
    LEFT JOIN produtos pr ON ip.produto_id = pr.id
    LEFT JOIN funcionarios f ON o.funcionario_id = f.id
    ORDER BY o.data_ocorrencia DESC
")->fetchAll(PDO::FETCH_ASSOC) ?? [];

$itens_disponiveis = $pdo->query("
    SELECT ip.id as item_id, ip.pedido_id, pr.nome as produto_nome 
    FROM itens_pedidos ip 
    JOIN produtos pr ON ip.produto_id = pr.id 
    ORDER BY ip.id DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../../css/painel.css">
    <link rel="stylesheet" href="../../css/financeiro.css">

    <title>Histórico de Pedidos e Ocorrências</title>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.ativo {
            display: block;
        }
        .nav-botoes {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .nav-botoes button {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: normal;
        }
        .nav-botoes button.ativo {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        .topo-secao {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>
    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <main class="conteudo">
        <h1>Gestão de Histórico e Ocorrências</h1>

        <div class="nav-botoes">
            <button type="button" id="btnTabPedidos" class="ativo" onclick="mudarAba('pedidos')">Histórico de Pedidos</button>
            <button type="button" id="btnTabOcorrencias" onclick="mudarAba('ocorrencias')">Registro de Ocorrências</button>
        </div>

        <div id="secaoPedidos" class="tab-content ativo">
            <h2>Histórico de Pedidos Finalizados e Itens</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Cód. Pedido</th>
                        <th>Cliente</th>
                        <th>Produto / Item</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Valor Total</th>
                        <th>Data do Pedido</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($historico_pedidos)): ?>
                        <?php foreach ($historico_pedidos as $p): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($p['pedido_id']) ?></td>
                            <td><?= htmlspecialchars($p['nome_cliente'] ?? 'Cliente Avulso') ?></td>
                            <td><?= htmlspecialchars($p['produto_nome'] ?? 'Item Diversos') ?></td>
                            <td><?= htmlspecialchars($p['quantidade'] ?? '-') ?></td>
                            <td>R$ <?= isset($p['preco_venda']) ? number_format($p['preco_venda'], 2, ',', '.') : '-' ?></td>
                            <td>R$ <?= number_format($p['valor_total'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?></td>
                            <td><span class="badge bg-success"><?= htmlspecialchars(ucfirst($p['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Nenhum pedido finalizado registrado no momento.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="secaoOcorrencias" class="tab-content">
            <div class="topo-secao">
                <button type="button" onclick="abrirModalOcorrencia()">REGISTRAR NOVA OCORRÊNCIA</button>
            </div>

            <h2>Histórico de Ocorrências Relacionadas</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Cód. Ocorrência</th>
                        <th>Pedido Vinculado</th>
                        <th>Item Vinculado</th>
                        <th>Funcionário</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Retornou ao Estoque</th>
                        <th>Data da Ocorrência</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ocorrencias)): ?>
                        <?php foreach ($ocorrencias as $o): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($o['id'] ?? '') ?></td>
                            <td>#<?= htmlspecialchars($o['pedido_codigo'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($o['produto_nome'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($o['funcionario_nome'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(ucfirst($o['tipo'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($o['motivo'] ?? '') ?></td>
                            <td><?= $o['retornou_estoque'] == 1 ? 'Sim' : 'Não' ?></td>
                            <td><?= isset($o['data_ocorrencia']) ? date('d/m/Y H:i', strtotime($o['data_ocorrencia'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Nenhuma ocorrência registrada no sistema.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <dialog id="modalOcorrencia">
        <h3>Registrar Nova Ocorrência</h3>
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja realmente registrar esta ocorrência?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="origem" value="ocorrencia">
            <input type="hidden" name="acao" value="inserir">
            <input type="hidden" name="pedido_id" id="input_pedido_id" required>

            <div>
                <label>Selecionar Item do Pedido:</label>
                <select name="item_pedido_id" id="select_item_pedido" required onchange="atualizarPedidoId(this)">
                    <option value="">-- Selecione o Item --</option>
                    <?php foreach ($itens_disponiveis as $item): ?>
                        <option value="<?= $item['item_id'] ?>" data-pedido="<?= $item['pedido_id'] ?>">
                            Pedido #<?= $item['pedido_id'] ?> - <?= htmlspecialchars($item['produto_nome']) ?> (Item #<?= $item['item_id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tipo de Ocorrência:</label>
                <select name="tipo" required>
                    <option value="cancelamento">Cancelamento</option>
                    <option value="troca">Troca</option>
                </select>
            </div>
            <div>
                <label>Motivo Detalhado:</label>
                <textarea name="motivo" rows="3" placeholder="Descreva o motivo da ocorrência..." required></textarea>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="retornou_estoque" value="1"> Retornou ao Estoque
                </label>
            </div>

            <div>
                <button type="submit">Salvar Registro</button>
                <button type="button" onclick="document.getElementById('modalOcorrencia').close()">Cancelar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalConfirmacaoCustom">
        <h3 id="tituloConfirmacao">Confirmação</h3>
        <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
        <button type="button" id="btnSimConfirmacao">Sim</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
    </dialog>

    <script>
    let formParaSubmeter = null;

    function mudarAba(aba) {
        const secaoPedidos = document.getElementById('secaoPedidos');
        const secaoOcorrencias = document.getElementById('secaoOcorrencias');
        
        const btnPedidos = document.getElementById('btnTabPedidos');
        const btnOcorrencias = document.getElementById('btnTabOcorrencias');

        secaoPedidos.classList.remove('ativo');
        secaoOcorrencias.classList.remove('ativo');
        btnPedidos.classList.remove('ativo');
        btnOcorrencias.classList.remove('ativo');

        if (aba === 'pedidos') {
            secaoPedidos.classList.add('ativo');
            btnPedidos.classList.add('ativo');
        } else if (aba === 'ocorrencias') {
            secaoOcorrencias.classList.add('ativo');
            btnOcorrencias.classList.add('ativo');
        }
    }

    function atualizarPedidoId(select) {
        const selectedOption = select.options[select.selectedIndex];
        const pedidoId = selectedOption.getAttribute('data-pedido');
        document.getElementById('input_pedido_id').value = pedidoId || '';
    }

    function abrirModalOcorrencia() {
        document.getElementById('modalOcorrencia').showModal();
    }

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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>
</body>
</html>