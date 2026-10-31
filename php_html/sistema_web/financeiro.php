<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
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

   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 
    
    <link rel="stylesheet" href="../../css/painel.css">
    <link rel="stylesheet" href="../../css/financeiro.css">
    <link rel="stylesheet" href="../../css/sistema_web/financeiro.css">

    
    
         
</head>
<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>
    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <main class="conteudo container-fluid">

    <div class="nav-botoes d-flex mt-4 gap-3">
        <button type="button" id="btnTabCategorias" class="btn-filtro-cat ativo" onclick="mudarAba('categorias')">Categorias Financeiras</button>
        <button type="button" id="btnTabContas" class="btn-filtro-cat" onclick="mudarAba('contas')">Contas a Pagar</button>

        <button type="button" id="btnNovaCategoria" class="ms-auto btn-cadastrar-fin" onclick="abrirModalCategoria()">+NOVA CATEGORIA</button>
        <button type="button" id="btnNovaConta" class="ms-auto btn-cadastrar-fin" onclick="abrirModalConta()">+NOVA CONTA</button>
    </div>

    <!-- ================= CATEGORIAS ================= -->
    <div id="secaoCategorias" class="tab-content ativo">

        <h2 class="titulo-fin mt-4">Categorias Financeiras</h2>

        <table class="tabela-fin">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Tipo de Saída</th>
                    <th>Grupo DRE</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                    <?php $classeTipo = preg_replace('/[^a-z_]/', '', strtolower($cat['tipo'])); ?>
                    <tr>
                        <td class="fin-nome"><?= htmlspecialchars($cat['nome']) ?></td>
                        <td><span class="tag-fin tag-fin-<?= $classeTipo ?>"><?= htmlspecialchars(ucfirst($cat['tipo'])) ?></span></td>
                        <td class="fin-sub"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cat['tipo_saida']))) ?></td>
                        <td class="fin-sub"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cat['grupo_dre']))) ?></td>
                        <td>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <button type="button" class="btn-editar-fin" onclick="editarCategoria(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)">EDITAR</button>

                                <form id="formDelCat_<?= $cat['id'] ?>" method="POST" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="origem" value="categoria">
                                    <input type="hidden" name="acao" value="deletar">
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="button" class="btn-excluir-fin" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR esta categoria?', document.getElementById('formDelCat_<?= $cat['id'] ?>'))">EXCLUIR</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($categorias)): ?>
                    <tr>
                        <td colspan="5" class="fin-vazio">Nenhuma categoria cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ================= CONTAS A PAGAR ================= -->
    <div id="secaoContas" class="tab-content">

        <h2 class="titulo-fin mt-4">Contas a Pagar</h2>

        <table class="tabela-fin">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Fornecedor</th>
                    <th>Categoria</th>
                    <th class="text-end">Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contas as $c): ?>
                    <?php
                        $avisoVencimento = '';
                        if ($c['status_pagamento'] === 'pendente') {
                            if ($c['data_vencimento'] < $hoje) {
                                $avisoVencimento = '<span class="tag-vencido">VENCIDO</span>';
                            } elseif ($c['data_vencimento'] === $hoje) {
                                $avisoVencimento = '<span class="tag-vence-hoje">VENCE HOJE</span>';
                            }
                        }
                        $classeStatus = preg_replace('/[^a-z_]/', '', strtolower($c['status_pagamento']));
                    ?>
                    <tr>
                        <td class="fin-nome"><?= htmlspecialchars($c['descricao']) ?></td>
                        <td class="fin-sub"><?= htmlspecialchars($c['nome_razao_social'] ?? 'Avulso') ?></td>
                        <td class="fin-sub"><?= htmlspecialchars($c['categoria_nome']) ?></td>
                        <td class="fin-valor text-end">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></span>
                                <?= $avisoVencimento ?>
                            </div>
                        </td>
                        <td><span class="tag-fin tag-fin-<?= $classeStatus ?>"><?= htmlspecialchars(ucfirst($c['status_pagamento'])) ?></span></td>
                        <td>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <?php if ($c['status_pagamento'] === 'pendente'): ?>
                                    <form id="formPagar_<?= $c['id'] ?>" method="POST" class="m-0">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="origem" value="conta">
                                        <input type="hidden" name="acao" value="pagar">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="button" class="btn-pagar-fin" onclick="dispararConfirmacao('Confirmar baixa de pagamento desta conta?', document.getElementById('formPagar_<?= $c['id'] ?>'))">MARCAR PAGO</button>
                                    </form>
                                <?php endif; ?>

                                <form id="formDelConta_<?= $c['id'] ?>" method="POST" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="origem" value="conta">
                                    <input type="hidden" name="acao" value="deletar">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="button" class="btn-excluir-fin" onclick="dispararConfirmacao('Tem certeza que deseja EXCLUIR esta conta?', document.getElementById('formDelConta_<?= $c['id'] ?>'))">EXCLUIR</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($contas)): ?>
                    <tr>
                        <td colspan="7" class="fin-vazio">Nenhuma conta cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

    <dialog id="modalCategoria" class="ModalFinanceiro">
        <h1>Cadastrar / Editar Categoria</h1>
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar as alterações da categoria?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="origem" value="categoria">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="form_cat_id">
            <div><label for="form_cat_nome">Nome</label><input type="text" name="nome" id="form_cat_nome" required></div>
            <div>
                <label for="form_cat_tipo">Tipo</label>
                <select name="tipo" id="form_cat_tipo" required>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            <div>
                <label for="form_cat_tipo_saida">Tipo de Saída</label>
                <select name="tipo_saida" id="form_cat_tipo_saida" required>
                    <option value="nao_aplicavel">Não Aplicável</option>
                    <option value="fixo">Fixo</option>
                    <option value="variavel">Variável</option>
                </select>
            </div>
            <div>
                <label for="form_cat_grupo_dre">Grupo DRE</label>
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
            <div class="btn-dialog-cadastrar">
                <button type="submit">Salvar Registro</button>
                <button type="button" onclick="document.getElementById('modalCategoria').close()">Cancelar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalConta" class="ModalFinanceiro">
        <h1>Cadastrar Conta a Pagar</h1>
        <form method="POST" onsubmit="event.preventDefault(); dispararConfirmacao('Deseja salvar esta nova conta?', this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="origem" value="conta">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id" id="form_conta_id">
            <div><label for="form_conta_descricao">Descrição</label><input type="text" name="descricao" id="form_conta_descricao" required></div>
            <div>
                <label for="form_conta_fornecedor">Fornecedor</label>
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
                <label for="form_conta_categoria">Categoria</label>
                <select name="categoria_id" id="form_conta_categoria" required>
                    <?php foreach ($categorias_saida as $cs): ?>
                        <option value="<?= $cs['id'] ?>">
                            [<?= strtoupper($cs['tipo']) ?>] <?= htmlspecialchars($cs['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label for="form_conta_valor">Valor (R$)</label><input type="number" step="0.01" name="valor" id="form_conta_valor" required></div>
            <div><label for="form_conta_vencimento">Vencimento</label><input type="date" name="data_vencimento" id="form_conta_vencimento" required></div>
            <div class="btn-dialog-cadastrar">
                <button type="submit">Salvar Registro</button>
                <button type="button" onclick="document.getElementById('modalConta').close()">Cancelar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalConfirmacaoCustom" class="ModalFinanceiro ModalConfirmacao">
        <h1 id="tituloConfirmacao">Confirmação</h1>
        <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
        <div class="btn-dialog-cadastrar">
            <button type="button" id="btnSimConfirmacao">Sim</button>
            <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
        </div>
    </dialog>

    <script>
    let formParaSubmeter = null;

   function mudarAba(aba) {
    const secaoCat = document.getElementById('secaoCategorias');
    const secaoContas = document.getElementById('secaoContas');
    const btnCat = document.getElementById('btnTabCategorias');
    const btnContas = document.getElementById('btnTabContas');

    secaoCat.classList.remove('ativo');
    secaoContas.classList.remove('ativo');
    btnCat.classList.remove('ativo');
    btnContas.classList.remove('ativo');

    if (aba === 'categorias') {
        secaoCat.classList.add('ativo');
        btnCat.classList.add('ativo');
    } else if (aba === 'contas') {
        secaoContas.classList.add('ativo');
        btnContas.classList.add('ativo');
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