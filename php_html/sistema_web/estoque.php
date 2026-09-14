<?php
require_once '../../php/conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO estoque (nome, codigo, unidade_medida, estoque_minimo, eh_revenda) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $_POST['estoque_minimo'], $eh_revenda]);
        $id = $pdo->lastInsertId();
        
        echo "
        <div id='estoque-{$id}'>
            <div>
                <span id='cabecalho-estoque-{$id}'>
                    <b>{$_POST['nome']}</b> (Cód: {$_POST['codigo']} | Min: {$_POST['estoque_minimo']}{$_POST['unidade_medida']})
                </span>
                <button onclick='abrirModalEditEstoque({$id}, \"{$_POST['nome']}\", \"{$_POST['codigo']}\", \"{$_POST['unidade_medida']}\", {$_POST['estoque_minimo']}, {$eh_revenda})'>EDITAR INSUMO</button>
                <button onclick='deletarEstoque({$id})'>EXCLUIR INSUMO</button>
            </div>
            <div id='lista-lotes-{$id}'>
                <p id='sem-lotes-{$id}'>Nenhum lote cadastrado.</p>
                <div id='container-btn-{$id}'>
                    <button onclick='abrirModalLote({$id}, \"{$_POST['unidade_medida']}\")'>ADICIONAR NOVO LOTE</button>
                </div>
            </div>
        </div>";
        exit; 
    }

    if ($acao === 'edit_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE estoque SET nome=?, codigo=?, unidade_medida=?, estoque_minimo=?, eh_revenda=? WHERE id=?");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $_POST['estoque_minimo'], $eh_revenda, $_POST['estoque_id']]);
        
        echo "<b>{$_POST['nome']}</b> (Cód: {$_POST['codigo']} | Min: {$_POST['estoque_minimo']}{$_POST['unidade_medida']})";
        exit;
    }

    if ($acao === 'del_estoque') {
        $stmt = $pdo->prepare("DELETE FROM estoque WHERE id = ?");
        $stmt->execute([$_POST['estoque_id']]);
        echo "ok";
        exit;
    }

    // --- LOTES ---
    if ($acao === 'add_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO lotes_estoque (estoque_id, fornecedor_id, numero_lote, data_validade, quantidade_estoque, preco_custo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['estoque_id'], $fornecedor_id, $_POST['numero_lote'], $_POST['data_validade'], $_POST['quantidade'], $_POST['preco_custo']]);
        $id_lote = $pdo->lastInsertId();
        
        $dataBR = date('d/m/Y', strtotime($_POST['data_validade']));
        $un = $_POST['unidade_medida'];
        
        echo "
        <div id='lote-{$id_lote}'>
            <div id='info-lote-{$id_lote}'>
                Lote: <b>{$_POST['numero_lote']}</b> | Val: {$dataBR} | Qtd: {$_POST['quantidade']}{$un}
            </div>
            <div>
                <button onclick='abrirModalEditLote({$id_lote}, \"{$_POST['numero_lote']}\", \"{$fornecedor_id}\", \"{$_POST['data_validade']}\", {$_POST['quantidade']}, {$_POST['preco_custo']}, \"{$un}\")'>EDITAR LOTE</button>
                <button onclick='deletarLote({$id_lote})'>EXCLUIR LOTE</button>
            </div>
        </div>";
        exit;
    }

    if ($acao === 'edit_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        $stmt = $pdo->prepare("UPDATE lotes_estoque SET numero_lote=?, fornecedor_id=?, data_validade=?, quantidade_estoque=?, preco_custo=? WHERE id=?");
        $stmt->execute([$_POST['numero_lote'], $fornecedor_id, $_POST['data_validade'], $_POST['quantidade'], $_POST['preco_custo'], $_POST['lote_id']]);
        
        $dataBR = date('d/m/Y', strtotime($_POST['data_validade']));
        $un = $_POST['unidade_medida'];
        
        echo "Lote: <b>{$_POST['numero_lote']}</b> | Val: {$dataBR} | Qtd: {$_POST['quantidade']}{$un}";
        exit;
    }

    if ($acao === 'del_lote') {
        $stmt = $pdo->prepare("DELETE FROM lotes_estoque WHERE id = ?");
        $stmt->execute([$_POST['lote_id']]);
        echo "ok";
        exit;
    }
}

$estoques = $pdo->query("SELECT * FROM estoque ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$lotes_raw = $pdo->query("SELECT * FROM lotes_estoque ORDER BY data_validade ASC")->fetchAll(PDO::FETCH_ASSOC);
$fornecedores = $pdo->query("SELECT id, nome_razao_social FROM fornecedores WHERE ativo = 1 ORDER BY nome_razao_social ASC")->fetchAll(PDO::FETCH_ASSOC);

$lotes_agrupados = [];
foreach($lotes_raw as $l) {
    $lotes_agrupados[$l['estoque_id']][] = $l;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Estoque e Lotes</title>
</head>
<body>

    <div id="container-estoque">
        <?php foreach($estoques as $item): ?>
            <?php $eh_rev = isset($item['eh_revenda']) ? $item['eh_revenda'] : 0; ?>
            <div id="estoque-<?= $item['id'] ?>">
                
                <div>
                    <span id="cabecalho-estoque-<?= $item['id'] ?>">
                        <b><?= htmlspecialchars($item['nome']) ?></b> (Cód: <?= htmlspecialchars($item['codigo']) ?> | Min: <?= $item['estoque_minimo'] ?><?= htmlspecialchars($item['unidade_medida']) ?>)
                    </span>
                    <button onclick="abrirModalEditEstoque(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nome'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['codigo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['unidade_medida'], ENT_QUOTES) ?>', <?= $item['estoque_minimo'] ?>, <?= $eh_rev ?>)">EDITAR INSUMO</button>
                    <button onclick="deletarEstoque(<?= $item['id'] ?>)">EXCLUIR INSUMO</button>
                </div>
                
                <div id="lista-lotes-<?= $item['id'] ?>">
                    
                    <?php if(isset($lotes_agrupados[$item['id']])): ?>
                        <?php foreach($lotes_agrupados[$item['id']] as $lote): ?>
                            
                            <div id="lote-<?= $lote['id'] ?>">
                                <div id="info-lote-<?= $lote['id'] ?>">
                                    Lote: <b><?= htmlspecialchars($lote['numero_lote']) ?></b> | 
                                    Val: <?= date('d/m/Y', strtotime($lote['data_validade'])) ?> | 
                                    Qtd: <?= $lote['quantidade_estoque'] ?><?= htmlspecialchars($item['unidade_medida']) ?>
                                </div>
                                <div>
                                    <button onclick="abrirModalEditLote(<?= $lote['id'] ?>, '<?= htmlspecialchars($lote['numero_lote'], ENT_QUOTES) ?>', '<?= $lote['fornecedor_id'] ?>', '<?= $lote['data_validade'] ?>', <?= $lote['quantidade_estoque'] ?>, <?= $lote['preco_custo'] ?>, '<?= htmlspecialchars($item['unidade_medida'], ENT_QUOTES) ?>')">EDITAR LOTE</button>
                                    <button onclick="deletarLote(<?= $lote['id'] ?>)">EXCLUIR LOTE</button>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="sem-lotes-<?= $item['id'] ?>">Nenhum lote cadastrado.</p>
                    <?php endif; ?>

                    <div id="container-btn-<?= $item['id'] ?>">
                        <button onclick="abrirModalLote(<?= $item['id'] ?>, '<?= htmlspecialchars($item['unidade_medida'], ENT_QUOTES) ?>')">ADICIONAR NOVO LOTE</button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('modalEstoque').showModal()">
        ADICIONAR NOVO INSUMO
    </button>

    <!-- Modal ADD Estoque -->
    <dialog id="modalEstoque">
        <h3>Cadastrar Insumo ou Item de Revenda</h3>
        <form id="formEstoque" onsubmit="salvarEstoque(event)">
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

    <!-- Modal EDIT Estoque -->
    <dialog id="modalEditEstoque">
        <h3>Editar Insumo / Revenda</h3>
        <form onsubmit="editarEstoque(event)">
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
                <button type="submit">Atualizar Insumo</button>
            </div>
        </form>
    </dialog>

    <!-- Modal ADD Lote -->
    <dialog id="modalLote">
        <h3>Adicionar Novo Lote</h3>
        <form id="formLote" onsubmit="salvarLote(event)">
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
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_razao_social']) ?></option>
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
            <div>
                <button type="button" onclick="document.getElementById('modalLote').close()">Cancelar</button>
                <button type="submit">Adicionar Lote</button>
            </div>
        </form>
    </dialog>

    <!-- Modal EDIT Lote -->
    <dialog id="modalEditLote">
        <h3>Editar Lote</h3>
        <form onsubmit="editarLote(event)">
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
                        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome_razao_social']) ?></option>
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
            <div>
                <button type="button" onclick="document.getElementById('modalEditLote').close()">Cancelar</button>
                <button type="submit">Atualizar Lote</button>
            </div>
        </form>
    </dialog>

    <script>
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

        async function salvarEstoque(e) {
            e.preventDefault();
            let form = e.target;
            let req = await fetch('', { method: 'POST', body: new FormData(form) });
            document.getElementById('container-estoque').insertAdjacentHTML('beforeend', await req.text());
            form.reset();
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
            form.reset();
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

        async function deletarEstoque(id_estoque) {
            if(!confirm("Atenção: Excluir este insumo apagará todos os lotes cadastrados nele! Deseja continuar?")) return;
            let fd = new FormData();
            fd.append('acao', 'del_estoque');
            fd.append('estoque_id', id_estoque);
            let req = await fetch('', { method: 'POST', body: fd });
            if((await req.text()).trim() === 'ok') {
                document.getElementById('estoque-' + id_estoque).remove();
            }
        }

        async function deletarLote(id_lote) {
            if(!confirm("Tem certeza que deseja apagar este lote?")) return;
            let fd = new FormData();
            fd.append('acao', 'del_lote');
            fd.append('lote_id', id_lote);
            let req = await fetch('', { method: 'POST', body: fd });
            if((await req.text()).trim() === 'ok') {
                document.getElementById('lote-' + id_lote).remove();
            }
        }
    </script>
</body>
</html>