<?php
require_once '../../php/conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add_produto') {
        $stmt = $pdo->prepare("INSERT INTO produtos (categorias, nome, descricao, preco_venda, make_or_buy, ativo, visivel) VALUES (?, ?, ?, ?, ?, 1, 1)");
        $stmt->execute([$_POST['categorias'], $_POST['nome'], $_POST['descricao'], $_POST['preco_venda'], $_POST['make_or_buy']]);
        $id = $pdo->lastInsertId();
        
        echo "
        <div id='produto-{$id}'>
            <div>
                <b>{$_POST['nome']}</b> (Cat: {$_POST['categorias']} | R$ {$_POST['preco_venda']} | Tipo: {$_POST['make_or_buy']})
            </div>
            <div id='lista-sub-{$id}'>";
            
        if($_POST['categorias'] === 'combos') {
            echo "
                <p id='pendente-{$id}'>Pendente: Nenhum item de combo cadastrado.</p>
                <div id='container-btn-{$id}'>
                    <button onclick='abrirModalCombo({$id}, \"{$_POST['nome']}\")'>ADICIONAR ITEM AO COMBO</button>
                </div>";
        } elseif($_POST['make_or_buy'] === 'produto_acabado') {
            echo "
                <p id='pendente-{$id}'>Pendente: Nenhuma receita cadastrada.</p>
                <div id='container-btn-{$id}'>
                    <button onclick='abrirModalReceita({$id}, \"{$_POST['nome']}\")'>ADICIONAR RECEITA</button>
                </div>";
        } else {
            echo "<p>Item de Revenda (Pronto para venda).</p>";
        }
        
        echo "
            </div>
        </div>";
        exit; 
    }

    if ($acao === 'add_receita') {
        $stmt = $pdo->prepare("INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['produto_id'], $_POST['ingrediente_id'], $_POST['quantidade_usada']]);
        $id_receita = $pdo->lastInsertId();
        
        $ing = $pdo->prepare("SELECT nome, unidade_medida FROM estoque WHERE id = ?");
        $ing->execute([$_POST['ingrediente_id']]);
        $dadosIng = $ing->fetch(PDO::FETCH_ASSOC);
        
        echo "
        <div id='receita-{$id_receita}'>
            <div>Ingrediente: <b>{$dadosIng['nome']}</b> | Qtd: {$_POST['quantidade_usada']} {$dadosIng['unidade_medida']}</div>
            <div>
                <button onclick='deletarReceita({$id_receita})'>EXCLUIR</button>
            </div>
        </div>";
        exit;
    }

    if ($acao === 'add_item_combo') {
        $stmt = $pdo->prepare("INSERT INTO itens_combo (combo_id, produto_filho_id, quantidade) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['combo_id'], $_POST['produto_filho_id'], $_POST['quantidade']]);
        $id_combo_item = $pdo->lastInsertId();
        
        $prod = $pdo->prepare("SELECT nome FROM produtos WHERE id = ?");
        $prod->execute([$_POST['produto_filho_id']]);
        $dadosProd = $prod->fetch(PDO::FETCH_ASSOC);
        
        echo "
        <div id='itemcombo-{$id_combo_item}'>
            <div>Produto: <b>{$dadosProd['nome']}</b> | Qtd: {$_POST['quantidade']}</div>
            <div>
                <button onclick='deletarItemCombo({$id_combo_item})'>EXCLUIR</button>
            </div>
        </div>";
        exit;
    }

    if ($acao === 'del_receita') {
        $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = ?");
        $stmt->execute([$_POST['receita_id']]);
        echo "ok";
        exit;
    }

    if ($acao === 'del_item_combo') {
        $stmt = $pdo->prepare("DELETE FROM itens_combo WHERE id = ?");
        $stmt->execute([$_POST['item_id']]);
        echo "ok";
        exit;
    }
}

$produtos = $pdo->query("SELECT * FROM produtos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$estoque = $pdo->query("SELECT * FROM estoque ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$receitas_raw = $pdo->query("SELECT r.*, e.nome as ingrediente_nome, e.unidade_medida FROM receitas r JOIN estoque e ON r.ingrediente_id = e.id")->fetchAll(PDO::FETCH_ASSOC);
$combos_raw = $pdo->query("SELECT ic.*, p.nome as filho_nome FROM itens_combo ic JOIN produtos p ON ic.produto_filho_id = p.id")->fetchAll(PDO::FETCH_ASSOC);

$receitas_agrupadas = [];
foreach($receitas_raw as $r) {
    $receitas_agrupadas[$r['produto_id']][] = $r;
}

$combos_agrupados = [];
foreach($combos_raw as $c) {
    $combos_agrupados[$c['combo_id']][] = $c;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produtos, Receitas e Combos</title>
</head>
<body>

    <div id="container-produtos">
        <?php foreach($produtos as $prod): ?>
            <div id="produto-<?= $prod['id'] ?>">
                <div>
                    <b><?= htmlspecialchars($prod['nome']) ?></b> 
                    (Cat: <?= $prod['categorias'] ?> | R$ <?= $prod['preco_venda'] ?> | Tipo: <?= $prod['make_or_buy'] ?>)
                </div>
                
                <div id="lista-sub-<?= $prod['id'] ?>">
                    <?php if($prod['categorias'] === 'combos'): ?>
                        <?php if(isset($combos_agrupados[$prod['id']])): ?>
                            <?php foreach($combos_agrupados[$prod['id']] as $item): ?>
                                <div id="itemcombo-<?= $item['id'] ?>">
                                    <div>Produto: <b><?= htmlspecialchars($item['filho_nome']) ?></b> | Qtd: <?= $item['quantidade'] ?></div>
                                    <div>
                                        <button onclick="deletarItemCombo(<?= $item['id'] ?>)">EXCLUIR</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p id="pendente-<?= $prod['id'] ?>">Pendente: Nenhum item de combo cadastrado.</p>
                        <?php endif; ?>
                        <div id="container-btn-<?= $prod['id'] ?>">
                            <button onclick="abrirModalCombo(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nome'], ENT_QUOTES) ?>')">ADICIONAR ITEM AO COMBO</button>
                        </div>

                    <?php elseif($prod['make_or_buy'] === 'produto_acabado'): ?>
                        <?php if(isset($receitas_agrupadas[$prod['id']])): ?>
                            <?php foreach($receitas_agrupadas[$prod['id']] as $rec): ?>
                                <div id="receita-<?= $rec['id'] ?>">
                                    <div>Ingrediente: <b><?= htmlspecialchars($rec['ingrediente_nome']) ?></b> | Qtd: <?= $rec['quantidade_usada'] ?> <?= $rec['unidade_medida'] ?></div>
                                    <div>
                                        <button onclick="deletarReceita(<?= $rec['id'] ?>)">EXCLUIR</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p id="pendente-<?= $prod['id'] ?>">Pendente: Nenhuma receita cadastrada.</p>
                        <?php endif; ?>
                        <div id="container-btn-<?= $prod['id'] ?>">
                            <button onclick="abrirModalReceita(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nome'], ENT_QUOTES) ?>')">ADICIONAR RECEITA</button>
                        </div>

                    <?php else: ?>
                        <p>Item de Revenda (Pronto para venda).</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button onclick="document.getElementById('modalProduto').showModal()">
        ADICIONAR NOVO PRODUTO
    </button>

    <dialog id="modalProduto">
        <h3 id="titulo_modal_prod">Cadastrar Novo Produto</h3>
        <form id="formProduto" onsubmit="salvarProduto(event)">
            <input type="hidden" name="acao" value="add_produto">
            <div>
                <label>Nome:</label>
                <input type="text" name="nome" required>
            </div>
            <div>
                <label>Categoria:</label>
                <select name="categorias" required>
                    <option hidden></option>
                    <option value="hamburguer">Hamburguer</option>
                    <option value="porcoes">Porções</option>
                    <option value="bebidas">Bebidas</option>
                    <option value="combos">Combos</option>
                    <option value="sobremesa_gelada">Sobremesa Gelada</option>
                    <option value="extras">Extras</option>
                </select>
            </div>
            <div>
                <label>Descrição:</label>
                <textarea name="descricao" required></textarea>
            </div>
            <div>
                <label>Preço de Venda:</label>
                <input type="number" step="0.01" name="preco_venda" required>
            </div>
            <div>
                <label>Make or Buy:</label>
                <select name="make_or_buy" required>
                    <option hidden></option>
                    <option value="produto_acabado">Produto Acabado</option>
                    <option value="revenda">Revenda</option>
                </select>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalProduto').close()">Cancelar</button>
                <button type="submit">Salvar</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalReceita">
        <h3 id="titulo_receita">Adicionar Receita</h3>
        <form id="formReceita" onsubmit="salvarReceita(event)">
            <input type="hidden" name="acao" value="add_receita">
            <input type="hidden" name="produto_id" id="input_receita_produto_id">
            <div>
                <label>Ingrediente (Estoque):</label>
                <select name="ingrediente_id" required>
                    <option value="">Selecione o Insumo</option>
                    <?php foreach($estoque as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?> (<?= $e['unidade_medida'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Quantidade Usada:</label>
                <input type="number" step="0.01" name="quantidade_usada" required>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalReceita').close()">Cancelar</button>
                <button type="submit">Salvar Receita</button>
            </div>
        </form>
    </dialog>

    <dialog id="modalCombo">
        <h3 id="titulo_combo">Adicionar Item ao Combo</h3>
        <form id="formCombo" onsubmit="salvarItemCombo(event)">
            <input type="hidden" name="acao" value="add_item_combo">
            <input type="hidden" name="combo_id" id="input_combo_id">
            <div>
                <label>Produto Filho:</label>
                <select name="produto_filho_id" required>
                    <option value="">Selecione o Produto</option>
                    <?php foreach($produtos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Quantidade:</label>
                <input type="number" name="quantidade" value="1" min="1" required>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('modalCombo').close()">Cancelar</button>
                <button type="submit">Salvar Item</button>
            </div>
        </form>
    </dialog>

    <script>
        function abrirModalReceita(idProd, nomeProd) {
            document.getElementById('input_receita_produto_id').value = idProd;
            document.getElementById('titulo_receita').innerText = "Adicionar Receita para: " + nomeProd;
            document.getElementById('modalReceita').showModal();
        }

        function abrirModalCombo(idCombo, nomeCombo) {
            document.getElementById('input_combo_id').value = idCombo;
            document.getElementById('titulo_combo').innerText = "Adicionar Item ao Combo: " + nomeCombo;
            document.getElementById('modalCombo').showModal();
        }

        async function salvarProduto(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);

            let req = await fetch('', { method: 'POST', body: formData });
            let htmlNovaCaixa = await req.text();

            document.getElementById('container-produtos').insertAdjacentHTML('beforeend', htmlNovaCaixa);
            form.reset();
            document.getElementById('modalProduto').close();
        }

        async function salvarReceita(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);
            let prodId = formData.get('produto_id');

            let req = await fetch('', { method: 'POST', body: formData });
            let htmlLinha = await req.text();

            let pendente = document.getElementById('pendente-' + prodId);
            if(pendente) pendente.remove();

            let btnContainer = document.getElementById('container-btn-' + prodId);
            btnContainer.insertAdjacentHTML('beforebegin', htmlLinha);

            form.reset();
            document.getElementById('modalReceita').close();
        }

        async function salvarItemCombo(e) {
            e.preventDefault();
            let form = e.target;
            let formData = new FormData(form);
            let comboId = formData.get('combo_id');

            let req = await fetch('', { method: 'POST', body: formData });
            let htmlLinha = await req.text();

            let pendente = document.getElementById('pendente-' + comboId);
            if(pendente) pendente.remove();

            let btnContainer = document.getElementById('container-btn-' + comboId);
            btnContainer.insertAdjacentHTML('beforebegin', htmlLinha);

            form.reset();
            document.getElementById('modalCombo').close();
        }

        async function deletarReceita(idRec) {
            if(!confirm("Excluir receita?")) return;
            let fd = new FormData();
            fd.append('acao', 'del_receita');
            fd.append('receita_id', idRec);
            let req = await fetch('', { method: 'POST', body: fd });
            if((await req.text()).trim() === 'ok') {
                document.getElementById('receita-' + idRec).remove();
            }
        }

        async function deletarItemCombo(idItem) {
            if(!confirm("Excluir item do combo?")) return;
            let fd = new FormData();
            fd.append('acao', 'del_item_combo');
            fd.append('item_id', idItem);
            let req = await fetch('', { method: 'POST', body: fd });
            if((await req.text()).trim() === 'ok') {
                document.getElementById('itemcombo-' + idItem).remove();
            }
        }
    </script>
</body>
</html>