<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autor izada.');
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $acao = $_POST['acao'] ?? '';
    $origem = $_POST['origem'] ?? '';

    if ($origem === 'categoria') {
        if ($acao === 'deletar') {
            $stmt = $pdo->prepare("DELETE FROM categorias_financeiras WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        } elseif ($acao === 'salvar') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE categorias_financeiras SET nome=?, tipo=?, tipo_saida=?, grupo_dre=? WHERE id=?");
                $stmt->execute([$_POST['nome'], $_POST['tipo'], $_POST['tipo_saida'], $_POST['grupo_dre'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categorias_financeiras (nome, tipo, tipo_saida, grupo_dre) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['nome'], $_POST['tipo'], $_POST['tipo_saida'], $_POST['grupo_dre']]);
            }
        }
    } elseif ($origem === 'conta') {
        if ($acao === 'salvar') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE contas_a_pagar SET fornecedor_id=?, categoria_id=?, descricao=?, valor=?, data_vencimento=? WHERE id=?");
                $stmt->execute([$_POST['fornecedor_id'] ?: null, $_POST['categoria_id'], $_POST['descricao'], $_POST['valor'], $_POST['data_vencimento'], $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO contas_a_pagar (fornecedor_id, categoria_id, descricao, valor, data_vencimento) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['fornecedor_id'] ?: null, $_POST['categoria_id'], $_POST['descricao'], $_POST['valor'], $_POST['data_vencimento']]);
            }
        } elseif ($acao === 'pagar') {
            $stmt = $pdo->prepare("UPDATE contas_a_pagar SET status_pagamento='pago', data_pagamento=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$_POST['id']]);
        } elseif ($acao === 'deletar') {
            $stmt = $pdo->prepare("DELETE FROM contas_a_pagar WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$categorias = $pdo->query("SELECT * FROM categorias_financeiras ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$contas = $pdo->query("SELECT c.*, f.nome_razao_social, f.cnpj_cpf, cat.nome as categoria_nome FROM contas_a_pagar c LEFT JOIN fornecedores f ON c.fornecedor_id = f.id JOIN categorias_financeiras cat ON c.categoria_id = cat.id ORDER BY c.data_vencimento ASC")->fetchAll(PDO::FETCH_ASSOC);
$fornecedores = $pdo->query("SELECT id, nome_razao_social, cnpj_cpf FROM fornecedores WHERE ativo = 1")->fetchAll(PDO::FETCH_ASSOC);
$categorias_saida = $pdo->query("SELECT id, nome, tipo FROM categorias_financeiras WHERE tipo = 'saida'")->fetchAll(PDO::FETCH_ASSOC);
$hoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- painel.css ANTES do css da página -->
    <link rel="stylesheet" href="../../css/painel.css">
    <link rel="stylesheet" href="../../css/financeiro.css">

    <meta charset="UTF-8">
    <title>Gestão Financeira</title>
</head>
<body >

    <?php include __DIR__ . "/../../includes/painel.php"; ?>

    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>
   

     <main class="conteudo">
    <h2>Categorias Financeiras</h2>
    <button onclick="abrirModalCategoria()">CADASTRAR CATEGORIA</button>
    <table>
        <thead><tr><th>Nome</th><th>Tipo</th><th>Tipo Saída</th><th>Grupo DRE</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($categorias as $cat): ?>
            <tr>
                <td><?= htmlspecialchars($cat['nome']) ?></td>
                <td><?= htmlspecialchars($cat['tipo']) ?></td>
                <td><?= htmlspecialchars($cat['tipo_saida']) ?></td>
                <td><?= htmlspecialchars($cat['grupo_dre']) ?></td>
                <td>
                    <button onclick='editarCategoria(<?= json_encode($cat) ?>)'>Editar</button>
                    <form id="formDelCat_<?= $cat['id'] ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="origem" value="categoria">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="button" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR esta categoria?', document.getElementById('formDelCat_<?= $cat['id'] ?>'))">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Contas a Pagar</h2>
    <button onclick="abrirModalConta()">CADASTRAR CONTA</button>
    <table>
        <thead><tr><th>Descrição</th><th>Fornecedor</th><th>Categoria</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($contas as $c): ?>
            <?php 
                $avisoVencimento = '';
                if ($c['status_pagamento'] === 'pendente') {
                    if ($c['data_vencimento'] < $hoje) {
                        $avisoVencimento = ' <span style="color:red; font-weight:bold;">(VENCIDO!)</span>';
                    } elseif ($c['data_vencimento'] === $hoje) {
                        $avisoVencimento = ' <span style="color:orange; font-weight:bold;">(VENCE HOJE!)</span>';
                    }
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($c['descricao']) ?></td>
                <td><?= htmlspecialchars($c['nome_razao_social'] ?? 'Avulso') ?></td>
                <td><?= htmlspecialchars($c['categoria_nome']) ?></td>
                <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?><?= $avisoVencimento ?></td>
                <td><?= htmlspecialchars($c['status_pagamento']) ?></td>
                <td>
                    <?php if ($c['status_pagamento'] === 'pendente'): ?>
                    <form id="formPagar_<?= $c['id'] ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="origem" value="conta">
                        <input type="hidden" name="acao" value="pagar">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="button" onclick="dispararConfirmacao('Confirmar baixa de pagamento desta conta?', document.getElementById('formPagar_<?= $c['id'] ?>'))">Marcar Pago</button>
                    </form>
                    <?php endif; ?>
                    <form id="formDelConta_<?= $c['id'] ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="origem" value="conta">
                        <input type="hidden" name="acao" value="deletar">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="button" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR esta conta?', document.getElementById('formDelConta_<?= $c['id'] ?>'))">Excluir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>

        </main>
    </table>

    <dialog id="modalCategoria">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar as alterações da categoria?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="origem" value="categoria">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="form_cat_id">
            <div><label>Nome:</label><input type="text" name="nome" id="form_cat_nome" required></div>
            <div>
                <label>Tipo:</label>
                <select name="tipo" id="form_cat_tipo" required>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            <div>
                <label>Tipo Saída:</label>
                <select name="tipo_saida" id="form_cat_tipo_saida" required>
                    <option value="nao_aplicavel">Não Aplicável</option>
                    <option value="fixo">Fixo</option>
                    <option value="variavel">Variável</option>
                </select>
            </div>
            <div>
                <label>Grupo DRE:</label>
                <select name="grupo_dre" id="form_cat_grupo_dre" required>
                    <option value="nao_aplicavel">Não Aplicável</option>
                    <option value="receita_bruta">Receita Bruta</option>
                    <option value="deducao_venda">Dedução Venda</option>
                    <option value="custo_mercadoria">Custo de Mercadoria</option>
                    <option value="despesa_operacional">Despesa Operacional</option>
                    <option value="despesa_financeira">Despesa Financeira</option>
                    <option value="receita_financeira">Receita Financeira</option>
                </select>
            </div>
            <button type="submit">Salvar</button>
            <button type="button" onclick="document.getElementById('modalCategoria').close()">Cancelar</button>
        </form>
    </dialog>

    <dialog id="modalConta">
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar esta nova conta?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="origem" value="conta">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="form_conta_id">
            <div><label>Descrição:</label><input type="text" name="descricao" id="form_conta_descricao" required></div>
            <div>
                <label>Fornecedor:</label>
                <select name="fornecedor_id" id="form_conta_fornecedor">
                    <option value="">Nenhum (Avulso)</option>
                    <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['nome_razao_social']) ?> 
                            <?= !empty($f['cnpj_cpf']) ? ' - Doc: ' . htmlspecialchars($f['cnpj_cpf']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Categoria:</label>
                <select name="categoria_id" id="form_conta_categoria" required>
                    <?php foreach ($categorias_saida as $cs): ?>
                        <option value="<?= $cs['id'] ?>">
                            [<?= strtoupper($cs['tipo']) ?>] <?= htmlspecialchars($cs['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Valor (R$):</label><input type="number" step="0.01" name="valor" id="form_conta_valor" required></div>
            <div><label>Vencimento:</label><input type="date" name="data_vencimento" id="form_conta_vencimento" required></div>
            <button type="submit">Salvar</button>
            <button type="button" onclick="document.getElementById('modalConta').close()">Cancelar</button>
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

    function abrirModalCategoria() {
        document.getElementById('form_cat_id').value = '';
        document.getElementById('form_cat_nome').value = '';
        document.getElementById('form_cat_tipo').value = 'entrada';
        document.getElementById('form_cat_tipo_saida').value = 'nao_aplicavel';
        document.getElementById('form_cat_grupo_dre').value = 'nao_aplicavel';
        document.getElementById('modalCategoria').showModal();
    }
    
    function editarCategoria(cat) {
        document.getElementById('form_cat_id').value = cat.id;
        document.getElementById('form_cat_nome').value = cat.nome;
        document.getElementById('form_cat_tipo').value = cat.tipo;
        document.getElementById('form_cat_tipo_saida').value = cat.tipo_saida;
        document.getElementById('form_cat_grupo_dre').value = cat.grupo_dre;
        document.getElementById('modalCategoria').showModal();
    }

    function abrirModalConta() {
        document.getElementById('form_conta_id').value = '';
        document.getElementById('form_conta_descricao').value = '';
        document.getElementById('form_conta_valor').value = '';
        document.getElementById('form_conta_vencimento').value = '';
        document.getElementById('form_conta_fornecedor').value = '';
        document.getElementById('form_conta_categoria').value = '';
        document.getElementById('modalConta').showModal();
    }
    </script>

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>
</body>
</html>