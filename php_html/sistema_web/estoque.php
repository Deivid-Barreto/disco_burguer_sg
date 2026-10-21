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

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO estoque (nome, codigo, unidade_medida, estoque_minimo, eh_revenda) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $_POST['estoque_minimo'], $eh_revenda]);
        $id = $pdo->lastInsertId();
        
        $nome_esc = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8');
        $cod_esc = htmlspecialchars($_POST['codigo'], ENT_QUOTES, 'UTF-8');
        $un_esc = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $nome_js = addslashes($nome_esc);
        $cod_js = addslashes($cod_esc);
        $un_js = addslashes($un_esc);

        echo "
        <div id='estoque-{$id}'>
            <div>
                <span id='cabecalho-estoque-{$id}'>
                    <b>{$nome_esc}</b> (Cód: {$cod_esc} | Min: {$_POST['estoque_minimo']}{$un_esc})
                </span>
                <button onclick='abrirModalEditEstoque({$id}, \"{$nome_js}\", \"{$cod_js}\", \"{$un_js}\", {$_POST['estoque_minimo']}, {$eh_revenda})'>EDITAR INSUMO</button>
                <button onclick='deletarEstoque({$id})'>EXCLUIR INSUMO</button>
            </div>
            <div id='lista-lotes-{$id}'>
                <p id='sem-lotes-{$id}'>Nenhum lote cadastrado.</p>
                <div id='container-btn-{$id}'>
                    <button onclick='abrirModalLote({$id}, \"{$un_js}\")'>ADICIONAR NOVO LOTE</button>
                </div>
            </div>
        </div>";
        exit; 
    }

    if ($acao === 'edit_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE estoque SET nome=?, codigo=?, unidade_medida=?, estoque_minimo=?, eh_revenda=? WHERE id=?");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $_POST['estoque_minimo'], $eh_revenda, $_POST['estoque_id']]);
        
        $nome_esc = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8');
        $cod_esc = htmlspecialchars($_POST['codigo'], ENT_QUOTES, 'UTF-8');
        $un_esc = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');

        echo "<b>{$nome_esc}</b> (Cód: {$cod_esc} | Min: {$_POST['estoque_minimo']}{$un_esc})";
        exit;
    }

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

    if ($acao === 'add_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO lotes_estoque (estoque_id, fornecedor_id, numero_lote, data_validade, quantidade_estoque, preco_custo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['estoque_id'], $fornecedor_id, $_POST['numero_lote'], $_POST['data_validade'], $_POST['quantidade'], $_POST['preco_custo']]);
        $id_lote = $pdo->lastInsertId();
        
        $stmtMov = $pdo->prepare("INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao) VALUES (?, ?, 'entrada', ?, ?)");
        $stmtMov->execute([$id_lote, $funcionario_id, $_POST['quantidade'], $_POST['observacao'] ?? null]);

        $dataVal = $_POST['data_validade'];
        $hoje = date('Y-m-d');
        $avisoValidade = '';
        if ($dataVal < $hoje) {
            $avisoValidade = ' <span style="color:red; font-weight:bold;">(VENCIDO!)</span>';
        } elseif ($dataVal === $hoje) {
            $avisoValidade = ' <span style="color:orange; font-weight:bold;">(VENCE HOJE!)</span>';
        }

        $dataBR = date('d/m/Y', strtotime($dataVal));
        $un = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $num_lote_esc = htmlspecialchars($_POST['numero_lote'], ENT_QUOTES, 'UTF-8');
        $num_lote_js = addslashes($num_lote_esc);
        $un_js = addslashes($un);
        $fornecedor_js = addslashes((string)$fornecedor_id);
        
        echo "
        <div id='lote-{$id_lote}'>
            <div id='info-lote-{$id_lote}'>
                Lote: <b>{$num_lote_esc}</b> | Val: {$dataBR}{$avisoValidade} | Qtd: {$_POST['quantidade']}{$un}
            </div>
            <div>
                <button onclick='abrirModalEditLote({$id_lote}, \"{$num_lote_js}\", \"{$fornecedor_js}\", \"{$_POST['data_validade']}\", {$_POST['quantidade']}, {$_POST['preco_custo']}, \"{$un_js}\")'>EDITAR LOTE</button>
                <button onclick='deletarLote({$id_lote})'>EXCLUIR LOTE</button>
            </div>
        </div>";
        exit;
    }

    if ($acao === 'edit_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        $stmt = $pdo->prepare("UPDATE lotes_estoque SET numero_lote=?, fornecedor_id=?, data_validade=?, quantidade_estoque=?, preco_custo=? WHERE id=?");
        $stmt->execute([$_POST['numero_lote'], $fornecedor_id, $_POST['data_validade'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['lote_id']]);
        
        $stmtMov = $pdo->prepare("INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao) VALUES (?, ?, ?, ?, ?)");
        $stmtMov->execute([$_POST['lote_id'], $funcionario_id, $_POST['tipo_movimentacao'], $_POST['quantidade'], $_POST['observacao'] ?? null]);

        $dataVal = $_POST['data_validade'];
        $hoje = date('Y-m-d');
        $avisoValidade = '';
        if ($dataVal < $hoje) {
            $avisoValidade = ' <span style="color:red; font-weight:bold;">(VENCIDO!)</span>';
        } elseif ($dataVal === $hoje) {
            $avisoValidade = ' <span style="color:orange; font-weight:bold;">(VENCE HOJE!)</span>';
        }

        $dataBR = date('d/m/Y', strtotime($dataVal));
        $un = htmlspecialchars($_POST['unidade_medida'], ENT_QUOTES, 'UTF-8');
        $num_lote_esc = htmlspecialchars($_POST['numero_lote'], ENT_QUOTES, 'UTF-8');
        
        echo "Lote: <b>{$num_lote_esc}</b> | Val: {$dataBR}{$avisoValidade} | Qtd: {$_POST['quantidade']}{$un}";
        exit;
    }

    if ($acao === 'del_lote') {
        try {
            $pdo->beginTransaction();
            
            $stmtLote = $pdo->prepare("SELECT numero_lote FROM lotes_estoque WHERE id = ?");
            $stmtLote->execute([$_POST['lote_id']]);
            $numLote = $stmtLote->fetchColumn();

            $obsFormulario = $_POST['observacao'] ?? '';
            $novaObservacao = trim("Lote excluído: " . $numLote . ". " . $obsFormulario);

            // Tenta desvincular ou registrar movimentação desvinculada caso haja FK estrita
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

$lotes_agrupados = [];
foreach($lotes_raw as $l) {
    $lotes_agrupados[$l['estoque_id']][] = $l;
}
$hoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-BR">
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
    <title>Estoque e Lotes</title>
</head>
<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>

    <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    <main class="conteudo">
    <div id="container-estoque">
        <?php foreach($estoques as $item): ?>
            <?php $eh_rev = isset($item['eh_revenda']) ? $item['eh_revenda'] : 0; ?>
            <div id="estoque-<?= $item['id'] ?>">
                
                <div>
                    <span id="cabecalho-estoque-<?= $item['id'] ?>">
                        <b><?= htmlspecialchars($item['nome']) ?></b> (Cód: <?= htmlspecialchars($item['codigo']) ?> | Min: <?= $item['estoque_minimo'] ?><?= htmlspecialchars($item['unidade_medida']) ?>)
                    </span>
                    <button onclick="abrirModalEditEstoque(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['nome'], ENT_QUOTES)) ?>', '<?= addslashes(htmlspecialchars($item['codigo'], ENT_QUOTES)) ?>', '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>', <?= $item['estoque_minimo'] ?>, <?= $eh_rev ?>)">EDITAR INSUMO</button>
                    <button onclick="deletarEstoque(<?= $item['id'] ?>)">EXCLUIR INSUMO</button>
                </div>
                
                <div id="lista-lotes-<?= $item['id'] ?>">
                    
                    <?php if(isset($lotes_agrupados[$item['id']])): ?>
                        <?php foreach($lotes_agrupados[$item['id']] as $lote): ?>
                            
                            <?php 
                                $avisoValidade = '';
                                if ($lote['data_validade'] < $hoje) {
                                    $avisoValidade = ' <span style="color:red; font-weight:bold;">(VENCIDO!)</span>';
                                } elseif ($lote['data_validade'] === $hoje) {
                                    $avisoValidade = ' <span style="color:orange; font-weight:bold;">(VENCE HOJE!)</span>';
                                }
                            ?>
                            <div id="lote-<?= $lote['id'] ?>">
                                <div id="info-lote-<?= $lote['id'] ?>">
                                    Lote: <b><?= htmlspecialchars($lote['numero_lote']) ?></b> | 
                                    Val: <?= date('d/m/Y', strtotime($lote['data_validade'])) ?><?= $avisoValidade ?> | 
                                    Qtd: <?= $lote['quantidade_estoque'] ?><?= htmlspecialchars($item['unidade_medida']) ?>
                                </div>
                                <div>
                                    <button onclick="abrirModalEditLote(<?= $lote['id'] ?>, '<?= addslashes(htmlspecialchars($lote['numero_lote'], ENT_QUOTES)) ?>', '<?= $lote['fornecedor_id'] ?>', '<?= $lote['data_validade'] ?>', <?= $lote['quantidade_estoque'] ?>, <?= $lote['preco_custo'] ?>, '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>')">EDITAR LOTE</button>
                                    <button onclick="deletarLote(<?= $lote['id'] ?>)">EXCLUIR LOTE</button>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="sem-lotes-<?= $item['id'] ?>">Nenhum lote cadastrado.</p>
                    <?php endif; ?>

                    <div id="container-btn-<?= $item['id'] ?>">
                        <button onclick="abrirModalLote(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['unidade_medida'], ENT_QUOTES)) ?>')">ADICIONAR NOVO LOTE</button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('modalEstoque').showModal()">
        ADICIONAR NOVO INSUMO
    </button>
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
                    <input type="checkbox" name="eh_revenda" value="1">Revenda
                </label>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalEstoque').close()">Cancelar</button>
                <button type="submit">Salvar</button>
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
                    <input type="checkbox" name="eh_revenda" id="edit_revenda" value="1">Revenda
                </label>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalEditEstoque').close()">Cancelar</button>
                <button type="button" onclick="pedirConfirmacaoEditarEstoque()">Atualizar Insumo</button>
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
                <label>Nº do Lote:</label>
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
                <label>Preço de Custo:</label>
                <input type="number" step="0.01" name="preco_custo" required>
            </div>

            <hr>
            <h4>Informações da Movimentação</h4>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Recebido NF 1234">
            </div>

            <div>
                <button type="button" onclick="document.getElementById('modalLote').close()">Cancelar</button>
                <button type="submit">Adicionar Lote</button>
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
                <label>Nº do Lote:</label>
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
                <label>Motivo da Edição:</label>
                <select name="tipo_movimentacao" required>
                    <option value="ajuste">Ajuste (Correção manual)</option>
                    <option value="perda_validade">Perda por Validade</option>
                    <option value="descarte">Descarte</option>
                </select>
            </div>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Contagem de fim de mês">
            </div>

            <div>
                <button type="button" onclick="document.getElementById('modalEditLote').close()">Cancelar</button>
                <button type="button" onclick="pedirConfirmacaoEditarLote()">Atualizar Lote</button>
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
                    <option value="ajuste">Ajuste (Erro de Cadastro)</option>
                </select>
            </div>
            <div>
                <label>Observação (Opcional):</label>
                <input type="text" name="observacao" placeholder="Ex: Cadastro duplicado">
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
            <strong>Atenção:</strong> Excluir este insumo apagará <b>todos</b> os lotes cadastrados nele! Deseja continuar?
        </p>
        <form id="formDelEstoque" onsubmit="confirmarDeletarEstoque(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="del_estoque">
            <input type="hidden" name="estoque_id" id="del_estoque_id">
            
            <div>
                <button type="button" onclick="document.getElementById('modalDelEstoque').close()">Cancelar</button>
                <button type="submit">Sim, Excluir Tudo</button>
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

        async function salvarLote(e) {
            e.preventDefault();
            let form = e.target;
            let id_estoque = new FormData(form).get('estoque_id');
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            
            let txtVazio = document.getElementById('sem-lotes-' + id_estoque);
            if(txtVazio) txtVazio.remove();

            document.getElementById('container-btn-' + id_estoque).insertAdjacentHTML('beforebegin', await req.text());
            
            let csrfVal = form.querySelector('input[name="csrf_token"]').value;
            form.reset();
            form.querySelector('input[name="csrf_token"]').value = csrfVal;
            
            document.getElementById('modalLote').close();
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
                alert('Erro ao excluir. O item pode estar atrelado a outros registros além de lotes.');
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
            form.reset();
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