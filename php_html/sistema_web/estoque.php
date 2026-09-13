<?php
require_once '../../php/conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add_estoque') {
        $eh_revenda = isset($_POST['eh_revenda']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO estoque (nome, codigo, unidade_medida, estoque_minimo, eh_revenda) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['codigo'], $_POST['unidade_medida'], $_POST['estoque_minimo'], $eh_revenda]);
        $id = $pdo->lastInsertId();
        
        $badge = $eh_revenda ? ' [Revenda]' : ' [Insumo]';
        
        echo "
        <div id='estoque-{$id}'>
            <div>
                {$badge} {$_POST['nome']} (Cód: {$_POST['codigo']} | Min: {$_POST['estoque_minimo']}{$_POST['unidade_medida']})
            </div>
            <div id='lista-lotes-{$id}'>
                <p class='sem-lotes'>Nenhum lote cadastrado.</p>
                <div class='container-add-lote'>
                    <button onclick='abrirModalLote({$id})'>ADD NOVO LOTE</button>
                </div>
            </div>
        </div>";
        exit; 
    }

    if ($acao === 'add_lote') {
        $fornecedor_id = !empty($_POST['fornecedor_id']) ? $_POST['fornecedor_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO lotes_estoque (estoque_id, fornecedor_id, numero_lote, data_validade, quantidade_estoque, preco_custo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['estoque_id'], 
            $fornecedor_id, 
            $_POST['numero_lote'], 
            $_POST['data_validade'], 
            $_POST['quantidade'], 
            $_POST['preco_custo']
        ]);
        $id_lote = $pdo->lastInsertId();
        
        $dataBR = date('d/m/Y', strtotime($_POST['data_validade']));
        
        echo "
        <div id='lote-{$id_lote}'>
            <div>Lote: <b>{$_POST['numero_lote']}</b> | Val: {$dataBR} | Qtd: {$_POST['quantidade']}</div>
            <div>
                <button onclick='alert(\"Função Edit em construção\")'>EDITAR</button>
                <button onclick='deletarLote({$id_lote})'>EXCLUIR</button>
            </div>
        </div>";
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
            <div id="estoque-<?= $item['id'] ?>">
                
                <div>
                    <?= htmlspecialchars($item['nome']) ?> (Cód: <?= $item['codigo'] ?> | Min: <?= $item['estoque_minimo'] ?><?= $item['unidade_medida'] ?>)
                </div>
                
                <div id="lista-lotes-<?= $item['id'] ?>">
                    
                    <?php if(isset($lotes_agrupados[$item['id']])): ?>
                        <?php foreach($lotes_agrupados[$item['id']] as $lote): ?>
                            
                            <div id="lote-<?= $lote['id'] ?>">
                                <div>
                                    Lote: <b><?= $lote['numero_lote'] ?></b> | 
                                    Val: <?= date('d/m/Y', strtotime($lote['data_validade'])) ?> | 
                                    Qtd: <?= $lote['quantidade_estoque'] ?>
                                </div>
                                <div>
                                    <button onclick="alert('Função Edit em construção')">EDITAR</button>
                                    <button onclick="deletarLote(<?= $lote['id'] ?>)">EXCLUIR</button>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="sem-lotes">Nenhum lote cadastrado.</p>
                    <?php endif; ?>

                    <div class="container-add-lote">
                        <button onclick="abrirModalLote(<?= $item['id'] ?>)">ADICIONAR NOVO LOTE</button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('modalEstoque').showModal()">
        ADICIONAR NOVO INSUMO
    </button>

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
                    <option value="ml">Mililitros (ml)</option>
                    <option value="l">Litros (l)</option>
                </select>
            </div>
            <div>
                <label>Estoque Mínimo:</label>
                <input type="number" step="0.01" name="estoque_minimo" required>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="eh_revenda" value="1"> 
                    Este item é para Revenda?
                </label>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalEstoque').close()">Cancelar</button>
                <button type="submit">Salvar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalLote">
        <h3>Adicionar Novo Lote</h3>
        <form id="formLote" onsubmit="salvarLote(event)">
            <input type="hidden" name="acao" value="add_lote">
            <input type="hidden" name="estoque_id" id="input_estoque_id">
            
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
                <label>Quantidade de Entrada:</label>
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

    <script>
        function abrirModalLote(id_estoque) {
            document.getElementById('input_estoque_id').value = id_estoque;
            document.getElementById('modalLote').showModal();
        }

        async function salvarLote(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);
            let id_estoque = formData.get('estoque_id');

            let req = await fetch('', { method: 'POST', body: formData });
            let htmlNovaLinha = await req.text();

            let areaLotes = document.getElementById('lista-lotes-' + id_estoque);
            let txtVazio = areaLotes.querySelector('.sem-lotes');
            if(txtVazio) txtVazio.remove();

            let btnContainer = areaLotes.querySelector('.container-add-lote');
            btnContainer.insertAdjacentHTML('beforebegin', htmlNovaLinha);

            form.reset();
            document.getElementById('modalLote').close();
        }

        async function deletarLote(id_lote) {
            if(!confirm("Tem certeza que deseja apagar este lote?")) return;

            let formData = new FormData();
            formData.append('acao', 'del_lote');
            formData.append('lote_id', id_lote);

            let req = await fetch('', { method: 'POST', body: formData });
            let res = await req.text();

            if(res.trim() === 'ok') {
                document.getElementById('lote-' + id_lote).remove();
            }
        }

        async function salvarEstoque(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);

            let req = await fetch('', { method: 'POST', body: formData });
            let htmlNovaCaixa = await req.text();

            document.getElementById('container-estoque').insertAdjacentHTML('beforeend', htmlNovaCaixa);

            form.reset();
            document.getElementById('modalEstoque').close();
        }
    </script>
</body>
</html>