<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';
require_once __DIR__ . '/verificar_login.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id']; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'abrir') {
        $stmt = $pdo->prepare("INSERT INTO caixas_diarios (funcionario_abertura_id, saldo_inicial, status) VALUES (?, ?, 'aberto')");
        $stmt->execute([$funcionario_id, $_POST['saldo_inicial']]);
    } elseif ($acao === 'fechar') {
        $stmt = $pdo->prepare("UPDATE caixas_diarios SET funcionario_fechamento_id=?, data_fechamento=CURRENT_TIMESTAMP, saldo_final_sistema=?, saldo_final_gaveta=?, status='fechado' WHERE id=?");
        $stmt->execute([$funcionario_id, $_POST['saldo_sistema'], $_POST['saldo_gaveta'], $_POST['id']]);
    } elseif ($acao === 'movimentacao') {
        $stmt = $pdo->prepare("INSERT INTO fluxo_caixa (categoria_id, funcionario_id, descricao, valor) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['categoria_id'], $funcionario_id, $_POST['descricao'], $_POST['valor']]);
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$caixa_aberto = $pdo->query("SELECT id FROM caixas_diarios WHERE status = 'aberto' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$caixas = $pdo->query("SELECT * FROM caixas_diarios ORDER BY data_abertura DESC")->fetchAll(PDO::FETCH_ASSOC);
$fluxo = $pdo->query("SELECT fc.*, c.nome as cat_nome, c.tipo FROM fluxo_caixa fc JOIN categorias_financeiras c ON fc.categoria_id = c.id ORDER BY fc.data_movimentacao DESC")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id, nome, tipo FROM categorias_financeiras")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fluxo de caixa</title>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.ativo {
            display: block;
        }
        .nav-botoes {
            margin-bottom: 20px;
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
    <h1>Fluxo de caixa</h1>

    <!-- Botões de navegação no começo -->
    <div class="nav-botoes">
        <button type="button" id="btnTabCaixas" class="ativo" onclick="mudarAba('caixas')">Caixas Diários</button>
        <button type="button" id="btnTabMovimentacoes" onclick="mudarAba('movimentacoes')">Movimentações</button>
    </div>

    <!-- Seção de Caixas Diários -->
    <div id="secaoCaixas" class="tab-content ativo">
        <div class="topo-secao">
            <?php if (!$caixa_aberto): ?>
                <button onclick="document.getElementById('modalAbertura').showModal()">ABRIR NOVO CAIXA</button>
            <?php endif; ?>
        </div>

        <h2>Caixas</h2>
        <table>
            <thead>
                <tr>
                    <th>Abertura</th>
                    <th>Fechamento</th>
                    <th>Saldo Inicial</th>
                    <th>Saldo Final (G)</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($caixas as $c): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($c['data_abertura'])) ?></td>
                    <td><?= $c['data_fechamento'] ? date('d/m/Y H:i', strtotime($c['data_fechamento'])) : '-' ?></td>
                    <td>R$ <?= number_format($c['saldo_inicial'], 2, ',', '.') ?></td>
                    <td><?= $c['saldo_final_gaveta'] !== null ? 'R$ ' . number_format($c['saldo_final_gaveta'], 2, ',', '.') : '-' ?></td>
                    <td><?= htmlspecialchars($c['status']) ?></td>
                    <td>
                        <?php if ($c['status'] === 'aberto'): ?>
                        <button onclick='fecharCaixa(<?= $c['id'] ?>)'>Fechar Caixa</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Seção de Movimentações -->
    <div id="secaoMovimentacoes" class="tab-content">
        <div class="topo-secao">
            <?php if ($caixa_aberto): ?>
                <button onclick="document.getElementById('modalFluxo').showModal()">LANÇAR MOVIMENTAÇÃO MANUAL</button>
            <?php endif; ?>
        </div>

        <h2>Histórico de Movimentações</h2>
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fluxo as $f): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($f['data_movimentacao'])) ?></td>
                    <td><?= htmlspecialchars($f['descricao']) ?></td>
                    <td><?= htmlspecialchars($f['cat_nome']) ?></td>
                    <td><?= htmlspecialchars($f['tipo']) ?></td>
                    <td>R$ <?= number_format($f['valor'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- MODAL DE ABERTURA -->
    <dialog id="modalAbertura">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja abrir um novo caixa com este saldo inicial?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="abrir">
            <div>
                <label>Saldo Inicial na Gaveta (R$):</label>
                <input type="number" step="0.01" name="saldo_inicial" required>
            </div>
            <button type="submit">Confirmar Abertura</button>
            <button type="button" onclick="document.getElementById('modalAbertura').close()">Cancelar</button>
        </form>
    </dialog>

    <!-- MODAL DE FECHAMENTO -->
    <dialog id="modalFechamento">
        <form id="formFechamento" method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Atenção: Os valores estão corretos? Deseja realmente FECHAR o caixa?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="fechar">
            <input type="hidden" name="id" id="fechar_id">
            <div>
                <label>Saldo do Sistema (R$):</label>
                <input type="number" step="0.01" name="saldo_sistema" required>
            </div>
            <div>
                <label>Dinheiro Físico na Gaveta (R$):</label>
                <input type="number" step="0.01" name="saldo_gaveta" required>
            </div>
            <button type="submit">Confirmar Fechamento</button>
            <button type="button" onclick="document.getElementById('modalFechamento').close()">Cancelar</button>
        </form>
    </dialog>

    <!-- MODAL DE LANÇAR MOVIMENTAÇÃO -->
    <dialog id="modalFluxo">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja gravar esta nova movimentação?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="movimentacao">
            <div>
                <label>Descrição:</label>
                <input type="text" name="descricao" required>
            </div>
            <div>
                <label>Categoria:</label>
                <select name="categoria_id" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>">[<?= strtoupper($cat['tipo']) ?>] <?= htmlspecialchars($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Valor (R$):</label>
                <input type="number" step="0.01" name="valor" required>
            </div>
            <button type="submit">Gravar Movimentação</button>
            <button type="button" onclick="document.getElementById('modalFluxo').close()">Cancelar</button>
        </form>
    </dialog>

    <!-- MODAL GLOBAL DE CONFIRMAÇÃO -->
    <dialog id="modalConfirmacaoCustom">
        <h3 id="tituloConfirmacao">Confirmação</h3>
        <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
        <button type="button" id="btnSimConfirmacao">Sim</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
    </dialog>

    <script>
    let formParaSubmeter = null;

    function mudarAba(aba) {
        const secaoCaixas = document.getElementById('secaoCaixas');
        const secaoMov = document.getElementById('secaoMovimentacoes');
        const btnCaixas = document.getElementById('btnTabCaixas');
        const btnMov = document.getElementById('btnTabMovimentacoes');

        if (aba === 'caixas') {
            secaoCaixas.classList.add('ativo');
            secaoMov.classList.remove('ativo');
            btnCaixas.classList.add('ativo');
            btnMov.classList.remove('ativo');
        } else {
            secaoCaixas.classList.remove('ativo');
            secaoMov.classList.add('ativo');
            btnCaixas.classList.remove('ativo');
            btnMov.classList.add('ativo');
        }
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

    function fecharCaixa(id) {
        document.getElementById('fechar_id').value = id;
        document.getElementById('modalFechamento').showModal();
    }
    </script>
</body>
</html>