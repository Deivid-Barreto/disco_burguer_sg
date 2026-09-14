<?php
if (isset($_GET['obter_produto'])) {
    require_once '../../php/conexao.php'; 
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $id_ed = (int)$_GET['obter_produto'];
    
    $st = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $st->execute([$id_ed]);
    $prod_data = $st->fetch(PDO::FETCH_ASSOC);

    $st_rec = $pdo->prepare("SELECT * FROM receitas WHERE produto_id = ?");
    $st_rec->execute([$id_ed]);
    $prod_rec = $st_rec->fetchAll(PDO::FETCH_ASSOC);

    $st_comb = $pdo->prepare("SELECT * FROM itens_combo WHERE combo_id = ?");
    $st_comb->execute([$id_ed]);
    $prod_comb = $st_comb->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'produto' => $prod_data,
        'receitas' => $prod_rec,
        'combos' => $prod_comb
    ]);
    exit;
}

require_once '../../php/conexao.php'; 
$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'deletar') {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmtDel->execute([$_POST['id_produto']]);
        } catch (PDOException $e) {}
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($acao === 'editar' || $acao === 'cadastrar') {
        $id_produto  = $_POST['id_produto'] ?? null;
        $nome        = trim($_POST['nome'] ?? '');
        $categoria   = $_POST['categorias'] ?? 'hamburguer';
        $descricao   = trim($_POST['descricao'] ?? '');
        $preco_venda = filter_var($_POST['preco_venda'] ?? 0, FILTER_VALIDATE_FLOAT);
        $visivel     = isset($_POST['visivel']) ? 1 : 0;
        
        if (in_array($categoria, ['hamburguer', 'porcoes', 'combos', 'sobremesa_gelada', 'extras'])) {
            $make_or_buy = 'produto_acabado';
        } else {
            $make_or_buy = $_POST['make_or_buy'] ?? 'revenda';
        }

        $tem_combo = false;
        if (isset($_POST['combo_itens']) && is_array($_POST['combo_itens'])) {
            foreach ($_POST['combo_itens'] as $filho_id => $marcado) {
                $qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 0);
                if ($qtd > 0) {
                    $tem_combo = true;
                    break;
                }
            }
        }

        $tem_receita = false;
        if (isset($_POST['ingredientes']) && is_array($_POST['ingredientes'])) {
            foreach ($_POST['ingredientes'] as $ingred_id => $marcado) {
                $qtd = (float)($_POST['ingrediente_qtd'][$ingred_id] ?? 0);
                if ($qtd > 0) {
                    $tem_receita = true;
                    break;
                }
            }
        }

        $ativo = 1;
        if ($categoria === 'combos' && !$tem_combo) {
            $ativo = 0;
        } elseif ($make_or_buy === 'produto_acabado' && $categoria !== 'combos' && !$tem_receita) {
            $ativo = 0;
        } elseif ($make_or_buy === 'revenda' && empty($_POST['estoque_revenda_id'])) {
            $ativo = 0;
        }

        if (!empty($nome) && $preco_venda !== false && $preco_venda >= 0) {
            try {
                $pdo->beginTransaction();

                if ($acao === 'editar' && !empty($id_produto)) {
                    $sql_produto = "UPDATE produtos SET categorias = :cat, nome = :nome, ativo = :ativo, descricao = :desc, preco_venda = :preco, visivel = :visivel, make_or_buy = :mob WHERE id = :id";
                    $stmt = $pdo->prepare($sql_produto);
                    $stmt->execute([
                        ':cat'     => $categoria,
                        ':nome'    => $nome,
                        ':ativo'   => $ativo,
                        ':desc'    => $descricao,
                        ':preco'   => $preco_venda,
                        ':visivel' => $visivel,
                        ':mob'     => $make_or_buy,
                        ':id'      => $id_produto
                    ]);
                    $alvo_id = $id_produto;
                } else {
                    $sql_produto = "INSERT INTO produtos (categorias, nome, ativo, descricao, preco_venda, visivel, make_or_buy) VALUES (:cat, :nome, :ativo, :desc, :preco, :visivel, :mob)";
                    $stmt = $pdo->prepare($sql_produto);
                    $stmt->execute([
                        ':cat'     => $categoria,
                        ':nome'    => $nome,
                        ':ativo'   => $ativo,
                        ':desc'    => $descricao,
                        ':preco'   => $preco_venda,
                        ':visivel' => $visivel,
                        ':mob'     => $make_or_buy
                    ]);
                    $alvo_id = $pdo->lastInsertId();
                }

                if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] === UPLOAD_ERR_OK) {
                    $pasta_destino = '../../img/uploads/';
                    if (!is_dir($pasta_destino)) mkdir($pasta_destino, 0755, true);
                    
                    $extensao = strtolower(pathinfo($_FILES['imagem_arquivo']['name'], PATHINFO_EXTENSION));
                    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($extensao, $extensoes_permitidas)) {
                        $caminho_completo = $pasta_destino . 'produto_' . $alvo_id . '.' . $extensao;
                        if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], $caminho_completo)) {
                            $stmt_img = $pdo->prepare("UPDATE produtos SET imagem_caminho = :caminho WHERE id = :id");
                            $stmt_img->execute([':caminho' => $caminho_completo, ':id' => $alvo_id]);
                        }
                    }
                }
                
                $pdo->prepare("DELETE FROM itens_combo WHERE combo_id = ?")->execute([$alvo_id]);
                $pdo->prepare("DELETE FROM receitas WHERE produto_id = ?")->execute([$alvo_id]);

                if ($categoria === 'combos' && isset($_POST['combo_itens'])) {
                    $stmtCombo = $pdo->prepare("INSERT INTO itens_combo (combo_id, produto_filho_id, quantidade) VALUES (:combo, :filho, :qtd)");
                    foreach ($_POST['combo_itens'] as $filho_id => $marcado) {
                        $qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 1);
                        if ($qtd > 0) $stmtCombo->execute([':combo' => $alvo_id, ':filho' => $filho_id, ':qtd' => $qtd]);
                    }
                } 
                elseif ($make_or_buy === 'revenda' && !empty($_POST['estoque_revenda_id'])) {
                    $stmtReceita = $pdo->prepare("INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, 1)");
                    $stmtReceita->execute([':prod' => $alvo_id, ':ing'  => $_POST['estoque_revenda_id']]);
                }
                elseif ($make_or_buy === 'produto_acabado' && $categoria !== 'combos' && isset($_POST['ingredientes'])) {
                    $stmtReceita = $pdo->prepare("INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, :qtd)");
                    foreach ($_POST['ingredientes'] as $ingred_id => $marcado) {
                        $qtd = (float)($_POST['ingrediente_qtd'][$ingred_id] ?? 0);
                        if ($qtd > 0) $stmtReceita->execute([':prod' => $alvo_id, ':ing' => $ingred_id, ':qtd' => $qtd]);
                    }
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$lista_estoque_ingredientes = [];
$lista_estoque_revenda = [];
$lista_produtos = [];
$produtosAtivos = [];
$produtosPendentes = [];

try {
    $stmtEstIng = $pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 0 ORDER BY nome ASC");
    $lista_estoque_ingredientes = $stmtEstIng->fetchAll(PDO::FETCH_ASSOC);

    $stmtEstRev = $pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 1 ORDER BY nome ASC");
    $lista_estoque_revenda = $stmtEstRev->fetchAll(PDO::FETCH_ASSOC);

    $stmtProd = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE categorias != 'combos' AND ativo = 1 ORDER BY nome ASC");
    $lista_produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    $produtosAtivos = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $produtosPendentes = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE ativo = 0 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Produtos</title>
</head>
<body>

<div>
    <button onclick="abrirModalCadastro()">ADD NOVO PRODUTO</button>

    <div>
        <h2>PRODUTOS CADASTRADOS</h2>
        <div>
            <?php foreach($produtosAtivos as $p): ?>
                <button onclick="abrirAcoes(<?= $p['id'] ?>)">
                    <?= htmlspecialchars($p['nome']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <h2>PENDÊNCIAS (PRODUTOS INATIVOS)</h2>
        <div>
            <?php foreach($produtosPendentes as $p): ?>
                <button onclick="abrirAcoes(<?= $p['id'] ?>)">
                    <?= htmlspecialchars($p['nome']) ?>
                </button>
            <?php endforeach; ?>
            <?php if(empty($produtosPendentes)): ?>
                <p>Nenhuma pendência no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<dialog id="modalCadastro">
    <h1 id="modalTituloCadastro">Cadastrar Novo Produto</h1>

    <form method="POST" action="" enctype="multipart/form-data" onsubmit="habilitarCamposAntesDeEnviar()">
        <input type="hidden" id="form_acao" name="acao" value="cadastrar">
        <input type="hidden" id="form_id_produto" name="id_produto" value="">

        <div>
            <label for="nome">Nome do Produto</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div>
            <label for="categorias">Categoria</label>
            <select id="categorias" name="categorias" onchange="atualizarFormulario()" required>
                <option value="" hidden></option>
                <option value="hamburguer">Hambúrguer</option>
                <option value="porcoes">Porções</option>
                <option value="bebidas">Bebidas</option>
                <option value="combos">Combos</option>
                <option value="sobremesa_gelada">Sobremesa Gelada</option>
                <option value="extras">Extras</option>
            </select>
        </div>

        <div>
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" required></textarea>
        </div>

        <div>
            <label for="preco_venda">Preço de Venda (R$)</label>
            <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" required>
        </div>

        <div>
            <label for="make_or_buy">Tipo de Produção</label>
            <select id="make_or_buy" name="make_or_buy" onchange="atualizarFormulario()" required>
                <option value="" hidden></option>
                <option value="produto_acabado">Produto Acabado</option>
                <option value="revenda">Revenda</option>
            </select>
            <input type="hidden" id="make_or_buy_hidden" name="make_or_buy" value="produto_acabado">
        </div>

        <div id="secao-revenda" style="display:none;">
            <h3>Vínculo de Estoque</h3>
            <label for="estoque_revenda_id">Item para baixa automática:</label>
            <select id="estoque_revenda_id" name="estoque_revenda_id">
                <option value="">-- Selecione --</option>
                <?php foreach($lista_estoque_revenda as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['nome']) ?> (<?= htmlspecialchars($item['unidade_medida']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="secao-receita" style="display:none;">
            <h3>Composição da Receita</h3>
            <?php if(!empty($lista_estoque_ingredientes)): ?>
            <table>
                <thead>
                    <tr><th>Incluir</th><th>Ingrediente</th><th>Quantidade</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_estoque_ingredientes as $item): ?>
                        <tr>
                            <td><input type="checkbox" id="chk_ing_<?= $item['id'] ?>" name="ingredientes[<?= $item['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_ing_<?= $item['id'] ?>')"></td>
                            <td><?= htmlspecialchars($item['nome']) ?> (<?= htmlspecialchars($item['unidade_medida']) ?>)</td>
                            <td><input type="number" id="qtd_ing_<?= $item['id'] ?>" name="ingrediente_qtd[<?= $item['id'] ?>]" step="0.01" min="0.01" placeholder="Qtd" disabled style="display: none;"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div id="secao-combo" style="display:none;">
            <h3>Itens do Combo</h3>
            <?php if(!empty($lista_produtos)): ?>
            <table>
                <thead>
                    <tr><th>Incluir</th><th>Produto</th><th>Quantidade</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_produtos as $prod): ?>
                        <tr>
                            <td><input type="checkbox" id="chk_combo_<?= $prod['id'] ?>" name="combo_itens[<?= $prod['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_combo_<?= $prod['id'] ?>')"></td>
                            <td><?= htmlspecialchars($prod['nome']) ?> <small>(<?= htmlspecialchars($prod['categorias']) ?>)</small></td>
                            <td><input type="number" id="qtd_combo_<?= $prod['id'] ?>" name="combo_qtd[<?= $prod['id'] ?>]" min="1" value="1" placeholder="Qtd" disabled style="display: none;"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div>
            <label for="imagem_arquivo">Imagem do Produto</label>
            <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/*">
            <div id="status_imagem_cadastrada" style="margin-top: 5px; font-size: 0.9em;"></div>
        </div>

        <div>
            <input type="checkbox" id="visivel" name="visivel" value="1" checked>
            <label for="visivel">Visível no Cardápio</label>
        </div>

        <button type="submit" id="btnSubmitModal">Cadastrar</button>
        <button type="button" onclick="document.getElementById('modalCadastro').close()">Fechar</button>
    </form>
</dialog>

<dialog id="modalAcoes">
    <h3 id="tituloAcoes">Ações do Produto</h3>
    
    <div id="detalhes_produto_info" style="margin-bottom: 15px; background: #f4f4f4; padding: 10px; border-radius: 4px;"></div>

    <form method="POST" action="">
        <input type="hidden" name="acao" value="deletar">
        <input type="hidden" name="id_produto" id="acao_id_produto" value="">
        
        <div>
            <button type="button" id="btnEditarAcoes" onclick="carregarDadosEdicao()" disabled>Editar</button>
            <button type="submit" style="color: red;">Excluir</button>
            <button type="button" onclick="document.getElementById('modalAcoes').close()">Cancelar</button>
        </div>
    </form>
</dialog>

<script>
let produtoAtualData = null;

function resetarFormulario() {
    document.getElementById('modalCadastro').querySelector('form').reset();
    document.getElementById('status_imagem_cadastrada').innerHTML = '';
    document.querySelectorAll('input[type=checkbox]').forEach(el => {
        if(el.id.startsWith('chk_')) {
            el.checked = false;
            let inp = document.getElementById(el.id.replace('chk_', 'qtd_'));
            if(inp) { inp.style.display = 'none'; inp.disabled = true; inp.value = ''; }
        }
    });
}

function abrirModalCadastro() {
    resetarFormulario();
    document.getElementById('form_acao').value = 'cadastrar';
    document.getElementById('form_id_produto').value = '';
    document.getElementById('modalTituloCadastro').innerText = 'Cadastrar Novo Produto';
    document.getElementById('btnSubmitModal').innerText = 'Cadastrar Produto';
    atualizarFormulario();
    document.getElementById('modalCadastro').showModal();
}

async function abrirAcoes(id) {
    document.getElementById('tituloAcoes').innerText = "Carregando...";
    document.getElementById('detalhes_produto_info').innerHTML = "<p>Buscando dados...</p>";
    document.getElementById('btnEditarAcoes').disabled = true;
    document.getElementById('modalAcoes').showModal();

    try {
        let response = await fetch(window.location.pathname + '?obter_produto=' + id);
        let textoBruto = await response.text(); 
        
        try {
            produtoAtualData = JSON.parse(textoBruto);
        } catch (e) {
            document.getElementById('detalhes_produto_info').innerHTML = "<p style='color:red;'>Erro na leitura dos dados.</p>";
            return;
        }

        let p = produtoAtualData.produto;
        document.getElementById('acao_id_produto').value = p.id;
        document.getElementById('tituloAcoes').innerText = "Produto: " + p.nome;

        let precoFmt = parseFloat(p.preco_venda).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        let statusText = p.ativo == 1 ? "<span style='color: green;'>Ativo</span>" : "<span style='color: red;'>Inativo (Pendente)</span>";
        let statusImagem = (p.imagem_caminho && p.imagem_caminho.trim() !== '') ? "<span style='color: green;'>✔ Com imagem</span>" : "<span style='color: orange;'>❌ Sem imagem</span>";

        document.getElementById('detalhes_produto_info').innerHTML = `
            <p><strong>Categoria:</strong> ${p.categorias.toUpperCase()}</p>
            <p><strong>Preço:</strong> R$ ${precoFmt}</p>
            <p><strong>Descrição:</strong> ${p.descricao}</p>
            <p><strong>Status:</strong> ${statusText}</p>
            <p><strong>Imagem:</strong> ${statusImagem}</p>
        `;
        document.getElementById('btnEditarAcoes').disabled = false;

    } catch (err) {
        document.getElementById('detalhes_produto_info').innerHTML = "<p style='color:red;'>Falha de conexão.</p>";
    }
}

function carregarDadosEdicao() {
    if (!produtoAtualData) return;
    document.getElementById('modalAcoes').close();
    resetarFormulario();

    let data = produtoAtualData;
    let p = data.produto;

    document.getElementById('form_acao').value = 'editar';
    document.getElementById('form_id_produto').value = p.id;
    document.getElementById('modalTituloCadastro').innerText = 'Editar Produto: ' + p.nome;
    document.getElementById('btnSubmitModal').innerText = 'Salvar Alterações';
    
    document.getElementById('nome').value = p.nome;
    document.getElementById('categorias').value = p.categorias;
    document.getElementById('descricao').value = p.descricao;
    document.getElementById('preco_venda').value = p.preco_venda;
    document.getElementById('make_or_buy').value = p.make_or_buy;
    document.getElementById('visivel').checked = (p.visivel == 1);

    if (p.imagem_caminho) document.getElementById('status_imagem_cadastrada').innerHTML = "<span style='color: green;'>✔ Imagem salva</span>";

    atualizarFormulario();

    if (p.categorias === 'combos') {
        data.combos.forEach(c => {
            let chk = document.getElementById('chk_combo_' + c.produto_filho_id);
            if (chk) {
                chk.checked = true;
                let inp = document.getElementById('qtd_combo_' + c.produto_filho_id);
                inp.value = c.quantidade; inp.style.display = 'block'; inp.disabled = false;
            }
        });
    } else if (p.make_or_buy === 'revenda') {
        if (data.receitas.length > 0) document.getElementById('estoque_revenda_id').value = data.receitas[0].ingrediente_id;
    } else {
        data.receitas.forEach(r => {
            let chk = document.getElementById('chk_ing_' + r.ingrediente_id);
            if (chk) {
                chk.checked = true;
                let inp = document.getElementById('qtd_ing_' + r.ingrediente_id);
                inp.value = r.quantidade_usada; inp.style.display = 'block'; inp.disabled = false;
            }
        });
    }

    document.getElementById('modalCadastro').showModal();
}

function toggleQtd(checkbox, inputId) {
    const inputQtd = document.getElementById(inputId);
    if (inputQtd) {
        inputQtd.style.display = checkbox.checked ? 'block' : 'none';
        inputQtd.disabled = !checkbox.checked;
        if(checkbox.checked) inputQtd.focus();
    }
}

function atualizarFormulario() {
    const cat = document.getElementById('categorias').value;
    const mbSel = document.getElementById('make_or_buy');
    const mbHid = document.getElementById('make_or_buy_hidden');
    
    document.getElementById('secao-combo').style.display = 'none';
    document.getElementById('secao-receita').style.display = 'none';
    document.getElementById('secao-revenda').style.display = 'none';

    if (!cat) return;

    if (['hamburguer', 'porcoes', 'combos', 'sobremesa_gelada', 'extras'].includes(cat)) {
        mbSel.value = 'produto_acabado'; mbSel.disabled = true;
        mbHid.disabled = false; mbHid.value = 'produto_acabado';
    } else {
        mbSel.disabled = false; mbHid.disabled = true;
        if (cat === 'bebidas' && !mbSel.value) mbSel.value = 'revenda';
    }

    const tipoAtual = mbSel.value || mbHid.value;

    if (cat === 'combos') document.getElementById('secao-combo').style.display = 'block';
    else if (tipoAtual === 'revenda') document.getElementById('secao-revenda').style.display = 'block';
    else if (tipoAtual === 'produto_acabado') document.getElementById('secao-receita').style.display = 'block';
}

function habilitarCamposAntesDeEnviar() {
    const mbSel = document.getElementById('make_or_buy');
    const mbHid = document.getElementById('make_or_buy_hidden');
    if (mbSel.disabled) { mbHid.disabled = false; mbHid.value = mbSel.value; }
}
</script>

</body>
</html>