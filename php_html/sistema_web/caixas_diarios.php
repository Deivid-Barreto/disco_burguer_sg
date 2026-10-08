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
    } elseif ($acao === 'editar_caixa') {
        $stmt = $pdo->prepare("UPDATE caixas_diarios SET saldo_inicial=? WHERE id=? AND status='aberto'");
        $stmt->execute([$_POST['saldo_inicial'], $_POST['id']]);
    } elseif ($acao === 'fechar') {
        $stmt = $pdo->prepare("UPDATE caixas_diarios SET funcionario_fechamento_id=?, data_fechamento=CURRENT_TIMESTAMP, saldo_final_sistema=?, saldo_final_gaveta=?, status='fechado' WHERE id=?");
        $stmt->execute([$funcionario_id, $_POST['saldo_sistema'], $_POST['saldo_gaveta'], $_POST['id']]);
    } elseif ($acao === 'movimentacao') {
        $stmt = $pdo->prepare("INSERT INTO fluxo_caixa (categoria_id, funcionario_id, descricao, valor) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['categoria_id'], $funcionario_id, $_POST['descricao'], $_POST['valor']]);
    } elseif ($acao === 'editar_movimentacao') {
        $stmt = $pdo->prepare("UPDATE fluxo_caixa SET categoria_id=?, descricao=?, valor=? WHERE id=?");
        $stmt->execute([$_POST['categoria_id'], $_POST['descricao'], $_POST['valor'], $_POST['id']]);
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
    <title>Gestão de Caixa</title>
</head>
<body>
    <h1>Caixas Diários e Movimentações</h1>

    <?php if (!$caixa_aberto): ?>
        <button onclick="document.getElementById('modalAbertura').showModal()">ABRIR NOVO CAIXA</button>
    <?php else: ?>
        <button onclick="document.getElementById('modalFluxo').showModal()">LANÇAR MOVIMENTAÇÃO MANUAL</button>
    <?php endif; ?>

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
                    <button onclick='editarCaixa(<?= $c['id'] ?>, <?= $c['saldo_inicial'] ?>)'>Editar</button>
                    <button onclick='fecharCaixa(<?= $c['id'] ?>)'>Fechar Caixa</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Histórico de Movimentações (Sangrias/Suprimentos)</h2>
    <table>
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Tipo</th>
                <th>Valor</th>
                <th>Ações</th>
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
                <td>
                    <button onclick='editarMovimentacao(<?= json_encode($f) ?>)'>Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

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

    <!-- MODAL DE EDIÇÃO DO CAIXA -->
    <dialog id="modalEditarCaixa">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar as alterações do caixa inicial?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="editar_caixa">
            <input type="hidden" name="id" id="edit_caixa_id">
            <div>
                <label>Saldo Inicial na Gaveta (R$):</label>
                <input type="number" step="0.01" name="saldo_inicial" id="edit_caixa_saldo" required>
            </div>
            <button type="submit">Salvar Alterações</button>
            <button type="button" onclick="document.getElementById('modalEditarCaixa').close()">Cancelar</button>
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

    <!-- MODAL DE EDITAR MOVIMENTAÇÃO -->
    <dialog id="modalEditarFluxo">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar as alterações desta movimentação?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="editar_movimentacao">
            <input type="hidden" name="id" id="edit_fluxo_id">
            <div>
                <label>Descrição:</label>
                <input type="text" name="descricao" id="edit_fluxo_descricao" required>
            </div>
            <div>
                <label>Categoria:</label>
                <select name="categoria_id" id="edit_fluxo_categoria" required>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>">[<?= strtoupper($cat['tipo']) ?>] <?= htmlspecialchars($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Valor (R$):</label>
                <input type="number" step="0.01" name="valor" id="edit_fluxo_valor" required>
            </div>
            <button type="submit">Salvar Alterações</button>
            <button type="button" onclick="document.getElementById('modalEditarFluxo').close()">Cancelar</button>
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
    
    function editarCaixa(id, saldo_inicial) {
        document.getElementById('edit_caixa_id').value = id;
        document.getElementById('edit_caixa_saldo').value = saldo_inicial;
        document.getElementById('modalEditarCaixa').showModal();
    }

    function editarMovimentacao(mov) {
        document.getElementById('edit_fluxo_id').value = mov.id;
        document.getElementById('edit_fluxo_descricao').value = mov.descricao;
        document.getElementById('edit_fluxo_categoria').value = mov.categoria_id;
        document.getElementById('edit_fluxo_valor').value = mov.valor;
        document.getElementById('modalEditarFluxo').showModal();
    }
    </script>
</body>
</html>