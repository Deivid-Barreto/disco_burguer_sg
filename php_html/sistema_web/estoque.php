<?php
session_start();
require_once __DIR__ . '/../../php/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$funcionario_id = $_SESSION['funcionario_id'] ?? null;
$hoje = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }

    $acao = $_POST['acao'] ?? '';

    /* ================= NOVO ITEM ================= */
    if ($acao === 'add_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        $minimo = (float)($_POST['estoque_minimo'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO estoque (nome, codigo, unidade_medida, estoque_minimo, eh_revenda) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $minimo, $eh_revenda]);
        $id = (int)$pdo->lastInsertId();

        $nome_esc = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8');
        $cod_esc  = htmlspecialchars($_POST['codigo'], ENT_QUOTES, 'UTF-8');
        $un_esc   = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $nome_js  = addslashes($nome_esc);
        $cod_js   = addslashes($cod_esc);
        $un_js    = addslashes($un_esc);
        $min_txt  = number_format($minimo, 2, '.', '');

        echo "
        <div id='estoque-{$id}' class='card-estoque mt-3'>
            <div class='card-estoque-header'>
                <div id='cabecalho-estoque-{$id}' class='card-estoque-info'>
                    <span class='item-nome'>{$nome_esc}</span>
                    <span class='item-meta'>Cód: <b>{$cod_esc}</b></span>
                    <span class='item-meta'>Mín: <b>{$min_txt}{$un_esc}</b></span>
                </div>
                <div class='card-estoque-acoes'>
                    <button class='btn-editar-estoque' onclick='abrirModalEditEstoque({$id}, \"{$nome_js}\", \"{$cod_js}\", \"{$un_js}\", {$min_txt}, {$eh_revenda})'>EDITAR</button>
                    <button class='btn-excluir-estoque' onclick='deletarEstoque({$id})'>EXCLUIR</button>
                </div>
            </div>
            <div id='lista-lotes-{$id}'>
                <p id='sem-lotes-{$id}' class='sem-lotes'>Nenhum lote cadastrado.</p>
                <div id='container-btn-{$id}'>
                    <button class='btn-cadastrar-lote' onclick='abrirModalLote({$id}, \"{$un_js}\")'>+ LOTE</button>
                </div>
            </div>
        </div>";
        exit;
    }

    /* ================= EDITAR ITEM ================= */
    if ($acao === 'edit_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        $minimo = (float)($_POST['estoque_minimo'] ?? 0);

        $stmt = $pdo->prepare("UPDATE estoque SET nome=?, codigo=?, unidade_medida=?, estoque_minimo=?, eh_revenda=? WHERE id=?");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $minimo, $eh_revenda, (int)$_POST['estoque_id']]);

        $nome_esc = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8');
        $cod_esc  = htmlspecialchars($_POST['codigo'], ENT_QUOTES, 'UTF-8');
        $un_esc   = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $min_txt  = number_format($minimo, 2, '.', '');

        echo "
        <span class='item-nome'>{$nome_esc}</span>
        <span class='item-meta'>Cód: <b>{$cod_esc}</b></span>
        <span class='item-meta'>Mín: <b>{$min_txt}{$un_esc}</b></span>";
        exit;
    }

    /* ================= EXCLUIR ITEM ================= */
    if ($acao === 'del_estoque') {
        try {
            $pdo->beginTransaction();

            $stmtDelLotes = $pdo->prepare("DELETE FROM lotes_estoque WHERE estoque_id = ?");
            $stmtDelLotes->execute([$_POST['estoque_id']]);

            $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
            $stmt->execute([$_POST['estoque_id']]);

            $pdo->commit();
            echo "ok";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "erro_fk";
        }
        exit;
    }

    /* ================= NOVO LOTE ================= */
    if ($acao === 'add_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
        $qtd   = (float)($_POST['quantidade'] ?? 0);
        $preco = (float)($_POST['preco_custo'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO lotes_estoque (estoque_id, fornecedor_id, numero_lote, data_validade, quantidade_estoque, preco_custo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['estoque_id'], $fornecedor_id, $_POST['numero_lote'], $_POST['data_validade'], $qtd, $preco]);
        $id_lote = (int)$pdo->lastInsertId();

        $stmtMov = $pdo->prepare("INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao) VALUES (?, ?, 'entrada', ?, ?)");
        $stmtMov->execute([$id_lote, $funcionario_id, $qtd, $_POST['observacao'] ?? null]);

        echo "ok";
        exit;
    }

    /* ================= EDITAR LOTE ================= */
    if ($acao === 'edit_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
        $qtd   = (float)($_POST['quantidade'] ?? 0);
        $preco = (float)($_POST['preco_custo'] ?? 0);

        $stmt = $pdo->prepare("UPDATE lotes_estoque SET numero_lote=?, fornecedor_id=?, data_validade=?, quantidade_estoque=?, preco_custo=? WHERE id=?");
        $stmt->execute([$_POST['numero_lote'], $fornecedor_id, $_POST['data_validade'], $qtd, $preco, $_POST['lote_id']]);

        $stmtMov = $pdo->prepare("INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao) VALUES (?, ?, ?, ?, ?)");
        $stmtMov->execute([$_POST['lote_id'], $funcionario_id, $_POST['tipo_movimentacao'], $qtd, $_POST['observacao'] ?? null]);

        $dataVal = $_POST['data_validade'];
        $avisoValidade = '';
        if ($dataVal < $hoje) {
            $avisoValidade = ' <span class="tag-vencido">VENCIDO</span>';
        } elseif ($dataVal === $hoje) {
            $avisoValidade = ' <span class="tag-vence-hoje">VENCE HOJE</span>';
        }

        $dataBR       = date('d/m/Y', strtotime($dataVal));
        $un           = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $num_lote_esc = htmlspecialchars($_POST['numero_lote'], ENT_QUOTES, 'UTF-8');
        $qtd_txt      = number_format($qtd, 2, '.', '');

        echo "
        <span class='lote-campo'><span class='lote-label'>Lote</span> <span class='lote-valor'>{$num_lote_esc}</span></span>
        <span class='lote-campo'><span class='lote-label'>Validade</span> <span class='lote-valor'>{$dataBR}</span>{$avisoValidade}</span>
        <span class='lote-campo'><span class='lote-label'>Quantidade</span> <span class='lote-valor'>{$qtd_txt}{$un}</span></span>";
        exit;
    }

    /* ================= EXCLUIR LOTE ================= */
    if ($acao === 'del_lote') {
        try {
            $pdo->beginTransaction();

            $stmtLote = $pdo->prepare("SELECT numero_lote FROM lotes_estoque WHERE id = ?");
            $stmtLote->execute([$_POST['lote_id']]);
            $numLote = $stmtLote->fetchColumn();

            $obsFormulario = $_POST['observacao'] ?? '';
            $novaObservacao = trim("Lote excluído: " . $numLote . ". " . $obsFormulario);

            $stmtMov = $pdo->prepare("INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao) VALUES (NULL, ?, ?, 0, ?)");
            $stmtMov->execute([$funcionario_id, $_POST['tipo_movimentacao'], $novaObservacao]);

            $stmt = $pdo->prepare("DELETE FROM lotes_estoque WHERE id = ?");
            $stmt->execute([$_POST['lote_id']]);

            $pdo->commit();
            echo "ok";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "erro";
        }
        exit;
    }
}

$estoques = $pdo->query("SELECT * FROM estoque ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$lotes_raw = $pdo->query("SELECT * FROM lotes_estoque ORDER BY data_validade ASC")->fetchAll(PDO::FETCH_ASSOC);
$fornecedores = $pdo->query("SELECT id, nome_razao_social, cnpj_cpf FROM fornecedores WHERE ativo = 1 ORDER BY nome_razao_social ASC")->fetchAll(PDO::FETCH_ASSOC);

$movimentacoes_estoque = $pdo->query("SELECT m.*, l.numero_lote, e.nome as nome_insumo FROM movimentacoes_estoque m LEFT JOIN lotes_estoque l ON m.lote_estoque_id = l.id LEFT JOIN estoque e ON l.estoque_id = e.id ORDER BY m.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$lotes_agrupados = [];
foreach ($lotes_raw as $l) {
    $lotes_agrupados[$l['estoque_id']][] = $l;
}
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
    <link rel="stylesheet" href="../../css/sistema_web/estoque.css">

    <title>Controle de Estoque e Movimentações</title>
</head>
<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>
    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <main class="conteudo container-fluid">

        <!-- Botões de navegação no começo (SEM o botão de novo item aqui) -->
        <div class="nav-botoes d-flex mt-4 gap-3">
            <button type="button" id="btnTabEstoque" class="ativo btn-filtro-cat" onclick="mudarAba('estoque')">Gestão de Estoque</button>
            <button type="button" id="btnTabMovimentacoes" class="btn-filtro-cat" onclick="mudarAba('movimentacoes')">Histórico de Movimentações</button>
        </div>

        <!-- Seção de Gerenciar Estoque -->
        <div id="secaoEstoque" class="tab-content ativo">
            
            <!-- Botão + NOVO ITEM posicionado exclusivamente dentro desta aba -->
            <div class="d-flex justify-content-end my-3">
                <button class="btn-cadastrar-estoque" onclick="document.getElementById('modalEstoque').showModal()">
                    + NOVO ITEM
                </button>
            </div>

            <div id="container-estoque">
                <?php foreach ($estoques as $item): ?>
                    <?php $eh_rev = isset($item['eh_revenda']) ? $item['eh_revenda'] : 0; ?>

                    <div id="estoque-<?= $item['id'] ?>" class="card-estoque mt-3">

                        <div class="card-estoque-header">
                            <div id="cabecalho-estoque-<?= $item['id'] ?>" class="card-estoque-info">
                                <span class="item-nome"><?= htmlspecialchars($item['nome']) ?></span>
                                <span class="item-meta">Cód: <b><?= htmlspecialchars($item['codigo']) ?></b></span>
                                <span class="item-meta">Mín: <b><?= $item['estoque_minimo'] ?><?= htmlspecialchars($item['unidade_medida']) ?></b></span>
                            </div>

                            <div class="card-estoque-acoes">
                                <button class="btn-editar-estoque" onclick="abrirModalEditEstoque(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['nome'], ENT_QUOTES)) ?>', '<?= addslashes(htmlspecialchars($item['codigo'], ENT_QUOTES)) ?>', '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>', <?= $item['estoque_minimo'] ?>, <?= $eh_rev ?>)">EDITAR</button>
                                <button class="btn-excluir-estoque" onclick="deletarEstoque(<?= $item['id'] ?>)">EXCLUIR</button>
                            </div>
                        </div>

                        <div id="lista-lotes-<?= $item['id'] ?>">

                            <?php if (isset($lotes_agrupados[$item['id']])): ?>
                                <?php foreach ($lotes_agrupados[$item['id']] as $lote): ?>

                                    <?php
                                        $avisoValidade = '';
                                        if ($lote['data_validade'] < $hoje) {
                                            $avisoValidade = ' <span class="tag-vencido">VENCIDO</span>';
                                        } elseif ($lote['data_validade'] === $hoje) {
                                            $avisoValidade = ' <span class="tag-vence-hoje">VENCE HOJE</span>';
                                        }
                                    ?>

                                    <div id="lote-<?= $lote['id'] ?>" class="lote-linha">
                                        <div id="info-lote-<?= $lote['id'] ?>" class="lote-info">
                                            <span class="lote-campo">
                                                <span class="lote-label">Lote</span>
                                                <span class="lote-valor"><?= htmlspecialchars($lote['numero_lote']) ?></span>
                                            </span>
                                            <span class="lote-campo">
                                                <span class="lote-label">Validade</span>
                                                <span class="lote-valor"><?= date('d/m/Y', strtotime($lote['data_validade'])) ?></span><?= $avisoValidade ?>
                                            </span>
                                            <span class="lote-campo">
                                                <span class="lote-label">Quantidade</span>
                                                <span class="lote-valor"><?= $lote['quantidade_estoque'] ?><?= htmlspecialchars($item['unidade_medida']) ?></span>
                                            </span>
                                        </div>

                                        <div class="lote-acoes">
                                            <button class="btn-editar-estoque" onclick="abrirModalEditLote(<?= $lote['id'] ?>, '<?= addslashes(htmlspecialchars($lote['numero_lote'], ENT_QUOTES)) ?>', '<?= $lote['fornecedor_id'] ?>', '<?= $lote['data_validade'] ?>', <?= $lote['quantidade_estoque'] ?>, <?= $lote['preco_custo'] ?>, '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>')">EDITAR</button>
                                            <button class="btn-excluir-estoque" onclick="deletarLote(<?= $lote['id'] ?>)">EXCLUIR</button>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <p id="sem-lotes-<?= $item['id'] ?>" class="sem-lotes">Nenhum lote cadastrado.</p>
                            <?php endif; ?>

                            <div id="container-btn-<?= $item['id'] ?>">
                                <button class="btn-cadastrar-lote" onclick="abrirModalLote(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>')">+ LOTE</button>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>

        <!-- Seção de Histórico de Movimentações -->
        <div id="secaoMovimentacoes" class="tab-content">
            <table class="tabela-mov mt-3">
                <thead>
                    <tr>
                        <th>Item / Produto</th>
                        <th>Número do Lote</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Observações</th>
                        <th>Data e Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimentacoes_estoque as $mov): ?>
                        <?php
                            $tipo = $mov['tipo_movimentacao'];
                            $rotulos = [
                                'entrada'         => 'Entrada',
                                'ajuste'          => 'Ajuste',
                                'perda_validade'  => 'Perda por validade',
                                'descarte'        => 'Descarte',
                            ];
                            $rotulo = $rotulos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
                            $classeTipo = preg_replace('/[^a-z_]/', '', strtolower($tipo));
                        ?>
                        <tr>
                            <td class="mov-item"><?= htmlspecialchars($mov['nome_insumo'] ?? 'Item desvinculado') ?></td>
                            <td><?= htmlspecialchars($mov['numero_lote'] ?? '-') ?></td>
                            <td><span class="tag-mov tag-mov-<?= $classeTipo ?>"><?= htmlspecialchars($rotulo) ?></span></td>
                            <td class="mov-qtd"><?= htmlspecialchars($mov['quantidade']) ?></td>
                            <td class="mov-obs"><?= htmlspecialchars($mov['observacao'] ?? '-') ?></td>
                            <td class="mov-data"><?= isset($mov['data_movimentacao']) ? date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($movimentacoes_estoque)): ?>
                        <tr>
                            <td colspan="6" class="mov-vazio">Nenhuma movimentação registrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <dialog id="modalEstoque">
        <h3>Cadastrar Insumo ou Item de Revenda</h3>
        <form id="formEstoque" onsubmit="salvarEstoque(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="add_estoque">
            <div>
                <label>Nome do Item:</label>
                <input type="text" name="nome" required>
            </div>
            <div>
                <label>Código:</label>
                <input type="text" name="codigo" required>
            </div>
            <div>
                <label>Unidade de Medida:</label>
                <select name="unidade_medida" required>
                    <option hidden></option>
                    <option value="g">Gramas (g)</option>
                    <option value="un">Unidade (un)</option>
                    <option value="ml">Mililitros (ml)</option>
                </select>
            </div>
            <div>
                <label>Estoque Mínimo:</label>
                <input type="number" step="0.01" name="estoque_minimo" required>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="eh_revenda" value="1">Item para Revenda
                </label>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalEstoque').close()">Cancelar</button>
                <button type="submit">Salvar Registro</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalEditEstoque">
        <h3>Editar Insumo / Revenda</h3>
        <form id="formEditEstoque" onsubmit="editarEstoque(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="edit_estoque">
            <input type="hidden" name="estoque_id" id="edit_estoque_id">
            <div>
                <label>Nome do Item:</label>
                <input type="text" name="nome" id="edit_nome" required>
            </div>
            <div>
                <label>Código:</label>
                <input type="text" name="codigo" id="edit_codigo" required>
            </div>
            <div>
                <label>Unidade de Medida:</label>
                <select name="unidade_medida" id="edit_unidade" required>
                    <option value="g">Gramas (g)</option>
                    <option value="un">Unidade (un)</option>
                    <option value="ml">Mililitros (ml)</option>
                </select>
            </div>
            <div>
                <label>Estoque Mínimo:</label>
                <input type="number" step="0.01" name="estoque_minimo" id="edit_minimo" required>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="eh_revenda" id="edit_revenda" value="1">Item para Revenda
                </label>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalEditEstoque').close()">Cancelar</button>
                <button type="button" onclick="pedirConfirmacaoEditarEstoque()">Salvar Alterações</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalLote">
        <h3>Adicionar Novo Lote</h3>
        <form id="formLote" onsubmit="salvarLote(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="add_lote">
            <input type="hidden" name="estoque_id" id="input_estoque_id">
            <input type="hidden" name="unidade_medida" id="input_lote_unidade">
            
            <div>
                <label>Número do Lote:</label>
                <input type="text" name="numero_lote" required>
            </div>
            <div>
                <label>Fornecedor:</label>
                <select name="fornecedor_id">
                    <option value="">-- Selecione o Fornecedor (Opcional) --</option>
                    <?php foreach($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_razao_social']) ?> <?= !empty($f['cnpj_cpf']) ? '- Doc: ' . htmlspecialchars($f['cnpj_cpf']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Data de Validade:</label>
                <input type="date" name="data_validade" required>
            </div>
            <div>
                <label>Quantidade de Entrada (<span id="txt_un_add"></span>):</label>
                <input type="number" step="0.01" name="quantidade" required>
            </div>
            <div>
                <label>Preço de Custo Unitário:</label>
                <input type="number" step="0.01" name="preco_custo" required>
            </div>

            <hr>
            <h4>Informações da Movimentação</h4>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Recebido conforme NF 1234">
            </div>

            <div>
                <button type="button" onclick="document.getElementById('modalLote').close()">Cancelar</button>
                <button type="submit">Cadastrar Lote</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalEditLote">
        <h3>Editar Lote</h3>
        <form id="formEditLote" onsubmit="editarLote(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="edit_lote">
            <input type="hidden" name="lote_id" id="edit_lote_id">
            <input type="hidden" name="unidade_medida" id="edit_lote_unidade">
            
            <div>
                <label>Número do Lote:</label>
                <input type="text" name="numero_lote" id="edit_num_lote" required>
            </div>
            <div>
                <label>Fornecedor:</label>
                <select name="fornecedor_id" id="edit_fornecedor">
                    <option value="">-- Selecione o Fornecedor (Opcional) --</option>
                    <?php foreach($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_razao_social']) ?> <?= !empty($f['cnpj_cpf']) ? '- Doc: ' . htmlspecialchars($f['cnpj_cpf']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Data de Validade:</label>
                <input type="date" name="data_validade" id="edit_validade" required>
            </div>
            <div>
                <label>Quantidade (<span id="txt_un_edit"></span>):</label>
                <input type="number" step="0.01" name="quantidade" id="edit_qtd" required>
            </div>
            <div>
                <label>Preço de Custo:</label>
                <input type="number" step="0.01" name="preco_custo" id="edit_preco" required>
            </div>

            <hr>
            <h4>Informações da Movimentação</h4>
            <div>
                <label>Motivo da Alteração:</label>
                <select name="tipo_movimentacao" required>
                    <option value="ajuste">Ajuste de Estoque (Correção)</option>
                    <option value="perda_validade">Perda por Validade</option>
                    <option value="descarte">Descarte Operacional</option>
                </select>
            </div>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Recalculo de inventário">
            </div>

            <div>
                <button type="button" onclick="document.getElementById('modalEditLote').close()">Cancelar</button>
                <button type="button" onclick="pedirConfirmacaoEditarLote()">Salvar Alterações</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalDelLote">
        <h3>Confirmar Exclusão de Lote</h3>
        <p>A exclusão será registrada no histórico com as informações abaixo.</p>
        <form id="formDelLote" onsubmit="confirmarDeletarLote(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="del_lote">
            <input type="hidden" name="lote_id" id="del_lote_id">
            
            <div>
                <label>Motivo da Exclusão:</label>
                <select name="tipo_movimentacao" required>
                    <option value="descarte">Descarte</option>
                    <option value="perda_validade">Perda por Validade</option>
                    <option value="ajuste">Ajuste por Erro de Cadastro</option>
                </select>
            </div>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Lote cadastrado duplicado">
            </div>
            
            <div>
                <button type="submit">Confirmar Exclusão</button>
                <button type="button" onclick="document.getElementById('modalDelLote').close()">Cancelar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalDelEstoque">
        <h3>Confirmar Exclusão de Insumo</h3>
        <p>
            <strong>Atenção:</strong> Excluir este item removerá permanentemente <b>todos</b> os lotes associados a ele. Deseja prosseguir?
        </p>
        <form id="formDelEstoque" onsubmit="confirmarDeletarEstoque(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="del_estoque">
            <input type="hidden" name="estoque_id" id="del_estoque_id">
            
            <div>
                <button type="button" onclick="document.getElementById('modalDelEstoque').close()">Cancelar</button>
                <button type="submit">Sim, Excluir</button>
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
        let callbackConfirmacao = null;

        function mudarAba(aba) {
            const secaoEstoque = document.getElementById('secaoEstoque');
            const secaoMov = document.getElementById('secaoMovimentacoes');
            
            const btnEstoque = document.getElementById('btnTabEstoque');
            const btnMov = document.getElementById('btnTabMovimentacoes');

            secaoEstoque.classList.remove('ativo');
            secaoMov.classList.remove('ativo');
            btnEstoque.classList.remove('ativo');
            btnMov.classList.remove('ativo');

            if (aba === 'estoque') {
                secaoEstoque.classList.add('ativo');
                btnEstoque.classList.add('ativo');
            } else if (aba === 'movimentacoes') {
                secaoMov.classList.add('ativo');
                btnMov.classList.add('ativo');
            }
        }

        function dispararConfirmacao(mensagem, callback) {
            document.getElementById('mensagemConfirmacao').innerText = mensagem;
            callbackConfirmacao = callback;
            document.getElementById('modalConfirmacaoCustom').showModal();
        }

        document.getElementById('btnSimConfirmacao').addEventListener('click', function() {
            document.getElementById('modalConfirmacaoCustom').close();
            if (callbackConfirmacao) callbackConfirmacao();
        });

        function abrirModalLote(id_estoque, unidade) {
            document.getElementById('input_estoque_id').value = id_estoque;
            document.getElementById('input_lote_unidade').value = unidade;
            document.getElementById('txt_un_add').innerText = unidade;
            document.getElementById('modalLote').showModal();
        }

        function abrirModalEditEstoque(id, nome, codigo, unidade, minimo, revenda) {
            document.getElementById('edit_estoque_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_codigo').value = codigo;
            document.getElementById('edit_unidade').value = unidade;
            document.getElementById('edit_minimo').value = minimo;
            document.getElementById('edit_revenda').checked = (revenda == 1);
            document.getElementById('modalEditEstoque').showModal();
        }

        function abrirModalEditLote(id, lote, fornecedor, validade, qtd, preco, unidade) {
            document.getElementById('edit_lote_id').value = id;
            document.getElementById('edit_num_lote').value = lote;
            document.getElementById('edit_fornecedor').value = fornecedor;
            document.getElementById('edit_validade').value = validade;
            document.getElementById('edit_qtd').value = qtd;
            document.getElementById('edit_preco').value = preco;
            
            document.getElementById('edit_lote_unidade').value = unidade;
            document.getElementById('txt_un_edit').innerText = unidade;
            
            document.getElementById('modalEditLote').showModal();
        }

        function pedirConfirmacaoEditarEstoque() {
            dispararConfirmacao("Tem certeza que deseja salvar as alterações do insumo?", function() {
                document.getElementById('formEditEstoque').requestSubmit();
            });
        }

        function pedirConfirmacaoEditarLote() {
            dispararConfirmacao("Tem certeza que deseja salvar as alterações do lote?", function() {
                document.getElementById('formEditLote').requestSubmit();
            });
        }

        async function salvarEstoque(e) {
            e.preventDefault();
            let form = e.target;
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            document.getElementById('container-estoque').insertAdjacentHTML('beforeend', await req.text());
            
            let csrfVal = form.querySelector('input[name="csrf_token"]').value;
            form.reset();
            form.querySelector('input[name="csrf_token"]').value = csrfVal;
            
            document.getElementById('modalEstoque').close();
        }

        // Atualiza a página ao salvar lote para garantir que o histórico de movimentações exiba o novo registro instantaneamente
        async function salvarLote(e) {
            e.preventDefault();
            let form = e.target;
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            let resp = await req.text();
            
            if (resp.trim() === 'ok') {
                location.reload();
            } else {
                alert('Erro ao cadastrar lote.');
            }
        }

        async function editarEstoque(e) {
            e.preventDefault();
            let formData = new FormData(e.target);
            let id = formData.get('estoque_id');
            let req = await fetch('', { method: 'POST', body: formData });
            
            document.getElementById('cabecalho-estoque-' + id).innerHTML = await req.text();
            document.getElementById('modalEditEstoque').close();
        }

        async function editarLote(e) {
            e.preventDefault();
            let formData = new FormData(e.target);
            let id = formData.get('lote_id');
            let req = await fetch('', { method: 'POST', body: formData });
            
            document.getElementById('info-lote-' + id).innerHTML = await req.text();
            document.getElementById('modalEditLote').close();
        }

        function deletarEstoque(id) {
            document.getElementById('del_estoque_id').value = id;
            document.getElementById('modalDelEstoque').showModal();
        }

        async function confirmarDeletarEstoque(e) {
            e.preventDefault();
            let formData = new FormData(e.target);
            let id = formData.get('estoque_id');
            let req = await fetch('', { method: 'POST', body: formData });
            let resp = await req.text();
            
            if (resp.trim() === 'ok') {
                document.getElementById('estoque-' + id).remove();
            } else {
                alert('Erro ao excluir. O item pode estar atrelado a outros registros.');
            }
            document.getElementById('modalDelEstoque').close();
        }

        function deletarLote(id) {
            document.getElementById('del_lote_id').value = id;
            document.getElementById('modalDelLote').showModal();
        }

        async function confirmarDeletarLote(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);
            let id = formData.get('lote_id');
            
            let req = await fetch('', { method: 'POST', body: formData });
            let resp = await req.text();
            
            if (resp.trim() === 'ok') {
                document.getElementById('lote-' + id).remove();
            } else {
                alert('Erro ao excluir o lote.');
            }
            
            let csrfVal = form.querySelector('input[name="csrf_token"]').value;
            form.resett();
            form.querySelector('input[name="csrf_token"]').value = csrfVal;
            
            document.getElementById('modalDelLote').close();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>
</body>
</html>