<?php

require_once '../../php/conexao.php'; 

$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'deletar') {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmtDel->execute([$_POST['id_produto']]);
            $mensagem_sucesso = "Produto excluído com sucesso!";
        } catch (PDOException $e) {}
    } else {
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

                $sql_produto = "INSERT INTO produtos (categorias, nome, ativo, descricao, preco_venda, visivel, make_or_buy) 
                                VALUES (:cat, :nome, :ativo, :desc, :preco, :visivel, :mob)";
                
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
                
                $produto_id = $pdo->lastInsertId();

                if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] === UPLOAD_ERR_OK) {
                    $pasta_destino = '../../img/uploads/';
                    
                    if (!is_dir($pasta_destino)) {
                        mkdir($pasta_destino, 0755, true);
                    }

                    $extensao = strtolower(pathinfo($_FILES['imagem_arquivo']['name'], PATHINFO_EXTENSION));
                    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($extensao, $extensoes_permitidas)) {
                        $novo_nome_arquivo = 'produto_' . $produto_id . '.' . $extensao;
                        $caminho_completo = $pasta_destino . $novo_nome_arquivo;

                        if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], $caminho_completo)) {
                            $sql_update_img = "UPDATE produtos SET imagem_caminho = :caminho WHERE id = :id";
                            $stmt_img = $pdo->prepare($sql_update_img);
                            $stmt_img->execute([
                                ':caminho' => $caminho_completo,
                                ':id'      => $produto_id
                            ]);
                        }
                    }
                }
                
                if ($categoria === 'combos' && isset($_POST['combo_itens'])) {
                    $sqlCombo = "INSERT INTO itens_combo (combo_id, produto_filho_id, quantidade) VALUES (:combo, :filho, :qtd)";
                    $stmtCombo = $pdo->prepare($sqlCombo);
                    
                    foreach ($_POST['combo_itens'] as $filho_id => $marcado) {
                        $qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 1);
                        if ($qtd > 0) {
                            $stmtCombo->execute([':combo' => $produto_id, ':filho' => $filho_id, ':qtd' => $qtd]);
                        }
                    }
                } 
                elseif ($make_or_buy === 'revenda' && !empty($_POST['estoque_revenda_id'])) {
                    $sqlReceita = "INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, 1)";
                    $stmtReceita = $pdo->prepare($sqlReceita);
                    $stmtReceita->execute([
                        ':prod' => $produto_id, 
                        ':ing'  => $_POST['estoque_revenda_id']
                    ]);
                }
                elseif ($make_or_buy === 'produto_acabado' && $categoria !== 'combos' && isset($_POST['ingredientes'])) {
                    $sqlReceita = "INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, :qtd)";
                    $stmtReceita = $pdo->prepare($sqlReceita);

                    foreach ($_POST['ingredientes'] as $ingred_id => $marcado) {
                        $qtd = (float)($_POST['ingrediente_qtd'][$ingred_id] ?? 0);
                        if ($qtd > 0) {
                            $stmtReceita->execute([':prod' => $produto_id, ':ing' => $ingred_id, ':qtd' => $qtd]);
                        }
                    }
                }

                $pdo->commit();
                $mensagem_sucesso = "Produto '$nome' cadastrado com sucesso! (ID: $produto_id)";

            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
    }
}

$lista_estoque_ingredientes = [];
$lista_estoque_revenda = [];
$lista_produtos = [];
$produtosAtivos = [];
$prodSemReceita = [];
$combosVazios = [];

try {
    // Busca apenas itens que NÃO são de revenda (Ingredientes / Matéria-prima)
    $stmtEstIng = $pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 0 ORDER BY nome ASC");
    $lista_estoque_ingredientes = $stmtEstIng->fetchAll(PDO::FETCH_ASSOC);

    // Busca apenas itens que SÃO de revenda (Bebidas prontas, etc)
    $stmtEstRev = $pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 1 ORDER BY nome ASC");
    $lista_estoque_revenda = $stmtEstRev->fetchAll(PDO::FETCH_ASSOC);

    $stmtProd = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE categorias != 'combos' AND ativo = 1 ORDER BY nome ASC");
    $lista_produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    $produtosAtivos = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $prodSemReceita = $pdo->query("SELECT p.id, p.nome FROM produtos p 
                                   LEFT JOIN receitas r ON p.id = r.produto_id 
                                   WHERE p.make_or_buy = 'produto_acabado' AND p.categorias != 'combos' AND (r.id IS NULL OR p.ativo = 0)
                                   GROUP BY p.id ORDER BY p.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    $combosVazios = $pdo->query("SELECT p.id, p.nome FROM produtos p 
                                 LEFT JOIN itens_combo c ON p.id = c.combo_id 
                                 WHERE p.categorias = 'combos' AND (c.id IS NULL OR p.ativo = 0)
                                 GROUP BY p.id ORDER BY p.nome ASC")->fetchAll(PDO::FETCH_ASSOC);
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
    <button onclick="document.getElementById('modalCadastro').showModal()">ADD NOVO PRODUTO</button>

    <div>
        <h2>SEC PRODUTOS CADASTRADOS</h2>
        <div>
            <?php foreach($produtosAtivos as $p): ?>
                <button onclick="abrirAcoes(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')">
                    <?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['categorias']) ?>)
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <h2>SEC PROD SEM RECEITA</h2>
        <div>
            <?php foreach($prodSemReceita as $p): ?>
                <button onclick="abrirAcoes(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')">
                    <?= htmlspecialchars($p['nome']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <h2>SEC COMBO SEM ITENS CADASTRADOS</h2>
        <div>
            <?php foreach($combosVazios as $p): ?>
                <button onclick="abrirAcoes(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')">
                    <?= htmlspecialchars($p['nome']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<dialog id="modalCadastro">
    <h1>Cadastrar Novo Produto</h1>

    <?php if ($mensagem_sucesso): ?>
        <div><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" onsubmit="habilitarCamposAntesDeEnviar()">
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
            <h3>Vínculo de Estoque (Item de Revenda)</h3>
            <label for="estoque_revenda_id">Item para baixa automática no estoque (1 unidade):</label>
            <select id="estoque_revenda_id" name="estoque_revenda_id">
                <option value="">-- Selecione o item de revenda --</option>
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
                    <tr>
                        <th>Incluir</th>
                        <th>Ingrediente</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_estoque_ingredientes as $item): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="ingredientes[<?= $item['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_ing_<?= $item['id'] ?>')">
                            </td>
                            <td><?= htmlspecialchars($item['nome']) ?> (<?= htmlspecialchars($item['unidade_medida']) ?>)</td>
                            <td>
                                <input type="number" id="qtd_ing_<?= $item['id'] ?>" name="ingrediente_qtd[<?= $item['id'] ?>]" step="0.01" min="0.01" placeholder="Qtd" disabled style="display: none;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Nenhum ingrediente no estoque.</p>
            <?php endif; ?>
        </div>

        <div id="secao-combo" style="display:none;">
            <h3>Itens Integrantes do Combo</h3>
            <?php if(!empty($lista_produtos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Incluir</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_produtos as $prod): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="combo_itens[<?= $prod['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_combo_<?= $prod['id'] ?>')">
                            </td>
                            <td><?= htmlspecialchars($prod['nome']) ?> <small>(<?= htmlspecialchars($prod['categorias']) ?>)</small></td>
                            <td>
                                <input type="number" id="qtd_combo_<?= $prod['id'] ?>" name="combo_qtd[<?= $prod['id'] ?>]" min="1" value="1" placeholder="Qtd" disabled style="display: none;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Nenhum produto cadastrado.</p>
            <?php endif; ?>
        </div>

        <div>
            <label for="imagem_arquivo">Imagem do Produto</label>
            <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/*">
        </div>

        <div>
            <input type="checkbox" id="visivel" name="visivel" value="1" checked>
            <label for="visivel">Visível no Cardápio</label>
        </div>

        <button type="submit">Cadastrar Produto</button>
        <button type="reset">Limpar</button>
        <button type="button" onclick="document.getElementById('modalCadastro').close()">Fechar</button>
    </form>
</dialog>

<dialog id="modalAcoes">
    <h3 id="tituloAcoes">Ações do Produto</h3>
    <form method="POST" action="">
        <input type="hidden" name="acao" value="deletar">
        <input type="hidden" name="id_produto" id="acao_id_produto" value="">
        
        <p>Opções para este produto:</p>
        <div>
            <button type="button" onclick="alert('Tela de edição')">Editar / Adicionar Receita</button>
            <button type="submit">Excluir</button>
            <button type="button" onclick="document.getElementById('modalAcoes').close()">Cancelar</button>
        </div>
    </form>
</dialog>

<script>
function abrirAcoes(id, nome) {
    document.getElementById('acao_id_produto').value = id;
    document.getElementById('tituloAcoes').innerText = "Produto: " + nome;
    document.getElementById('modalAcoes').showModal();
}

function toggleQtd(checkbox, inputId) {
    const inputQtd = document.getElementById(inputId);
    if (inputQtd) {
        if (checkbox.checked) {
            inputQtd.style.display = 'block';
            inputQtd.disabled = false;
            inputQtd.focus();
        } else {
            inputQtd.style.display = 'none';
            inputQtd.disabled = true;
        }
    }
}

function atualizarFormulario() {
    const categoria = document.getElementById('categorias').value;
    const makeOrBuySelect = document.getElementById('make_or_buy');
    const makeOrBuyHidden = document.getElementById('make_or_buy_hidden');
    
    const secaoReceita = document.getElementById('secao-receita');
    const secaoCombo = document.getElementById('secao-combo');
    const secaoRevenda = document.getElementById('secao-revenda');

    secaoCombo.style.display = 'none';
    secaoReceita.style.display = 'none';
    secaoRevenda.style.display = 'none';

    if (!categoria) return;

    if (categoria === 'hamburguer' || categoria === 'porcoes' || categoria === 'combos' || categoria === 'sobremesa_gelada' || categoria === 'extras') {
        makeOrBuySelect.value = 'produto_acabado';
        makeOrBuySelect.disabled = true;
        makeOrBuyHidden.disabled = false;
        makeOrBuyHidden.value = 'produto_acabado';
    } else {
        makeOrBuySelect.disabled = false;
        makeOrBuyHidden.disabled = true;
        
        if (categoria === 'bebidas' && !makeOrBuySelect.value) {
            makeOrBuySelect.value = 'revenda';
        }
    }

    const tipoAtual = makeOrBuySelect.value || makeOrBuyHidden.value;

    if (categoria === 'combos') {
        secaoCombo.style.display = 'block';
    } else if (tipoAtual === 'revenda') {
        secaoRevenda.style.display = 'block';
    } else if (tipoAtual === 'produto_acabado') {
        secaoReceita.style.display = 'block';
    }
}

function habilitarCamposAntesDeEnviar() {
    const makeOrBuySelect = document.getElementById('make_or_buy');
    const makeOrBuyHidden = document.getElementById('make_or_buy_hidden');
    if (makeOrBuySelect.disabled) {
        makeOrBuyHidden.disabled = false;
        makeOrBuyHidden.value = makeOrBuySelect.value;
    }
}

document.addEventListener('DOMContentLoaded', atualizarFormulario);
</script>

</body>
</html>