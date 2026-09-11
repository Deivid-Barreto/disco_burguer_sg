<?php
require_once '../../php/conexao.php'; 

$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome        = trim($_POST['nome'] ?? '');
    $categoria   = $_POST['categorias'] ?? 'hamburguer';
    $descricao   = trim($_POST['descricao'] ?? '');
    $preco_venda = filter_var($_POST['preco_venda'] ?? 0, FILTER_VALIDATE_FLOAT);
    $ativo       = isset($_POST['ativo']) ? 1 : 0;
    $visivel     = isset($_POST['visivel']) ? 1 : 0;
    
    if ($categoria === 'combos') {
        $make_or_buy = 'produto_acabado';
    } else {
        $make_or_buy = $_POST['make_or_buy'] ?? 'produto_acabado';
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
                        // 4. Executa o UPDATE atualizando a coluna imagem_caminho do ID específico
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
            
            elseif ($make_or_buy === 'produto_acabado' && isset($_POST['ingredientes'])) {
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

$lista_estoque = [];
$lista_produtos = [];

try {
    $stmtEst = $pdo->query("SELECT id, nome, unidade_medida FROM estoque ORDER BY nome ASC");
    $lista_estoque = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

    $stmtProd = $pdo->query("SELECT id, nome, categorias FROM produtos WHERE categorias != 'combos' AND ativo = 1 ORDER BY nome ASC");
    $lista_produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos</title>
</head>
<body>

<div class="container">
    <h1>Cadastrar Novo Produto</h1>

    <?php if ($mensagem_sucesso): ?>
        <div class="alerta-sucesso"><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>

    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nome">Nome do Produto</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div class="form-group">
            <label for="categorias">Categoria</label>
            <select id="categorias" name="categorias" onchange="atualizarFormulario()" required>
                <option hidden></option>
                <option value="hamburguer">Hambúrguer</option>
                <option value="porcoes">Porções</option>
                <option value="bebidas">Bebidas</option>
                <option value="combos">Combos</option>
                <option value="sobremesa_gelada">Sobremesa Gelada</option>
                <option value="extras">Extras</option>
            </select>
        </div>

        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="preco_venda">Preço de Venda (R$)</label>
            <input type="number" id="preco_venda" name="preco_venda" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label for="make_or_buy">Tipo de Produção</label>
            <select id="make_or_buy" name="make_or_buy" onchange="atualizarFormulario()" required>
                <option hidden></option>    
                <option value="produto_acabado">Produto Acabado</option>
                <option value="revenda">Revenda</option>
            </select>
            <input type="hidden" id="make_or_buy_hidden" name="make_or_buy" value="produto_acabado" disabled>
        </div>

    
        <div class="form-group">
            <label for="imagem_arquivo">Imagem do Produto</label>
            <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/*">
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="ativo" name="ativo" value="1" checked>
            <label for="ativo">Ativo no Sistema</label>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="visivel" name="visivel" value="1" checked>
            <label for="visivel">Visível no Cardápio</label>
        </div>

        
        <div id="secao-receita" style="display:none;">
            <h3>Composição da Receita</h3>
            <?php if(!empty($lista_estoque)): ?>
            <table>
                <thead>
                    <tr>
                        <th class="col-check">Incluir</th>
                        <th>Ingrediente</th>
                        <th class="col-qtd">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_estoque as $item): ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" name="ingredientes[<?= $item['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_ing_<?= $item['id'] ?>')">
                            </td>
                            <td><?= htmlspecialchars($item['nome']) ?> (<?= htmlspecialchars($item['unidade_medida']) ?>)</td>
                            <td class="col-qtd">
                                <input type="number" id="qtd_ing_<?= $item['id'] ?>" name="ingrediente_qtd[<?= $item['id'] ?>]" step="0.01" min="0.01" placeholder="Qtd" disabled style="display: none;">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Nenhum item no estoque.</p>
            <?php endif; ?>
        </div>

        
        <div id="secao-combo" style="display:none;">
            <h3>Itens Integrantes do Combo</h3>
            <?php if(!empty($lista_produtos)): ?>
            <table>
                <thead>
                    <tr>
                        <th class="col-check">Incluir</th>
                        <th>Produto</th>
                        <th class="col-qtd">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_produtos as $prod): ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" name="combo_itens[<?= $prod['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_combo_<?= $prod['id'] ?>')">
                            </td>
                            <td><?= htmlspecialchars($prod['nome']) ?> <small>(<?= htmlspecialchars($prod['categorias']) ?>)</small></td>
                            <td class="col-qtd">
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

        <button type="submit">Cadastrar Produto</button>
    </form>
</div>

<script>
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

    if (categoria === 'combos') {
        makeOrBuySelect.value = 'produto_acabado';
        makeOrBuySelect.disabled = true;
        makeOrBuyHidden.disabled = false;
        secaoCombo.style.display = 'block';
        secaoReceita.style.display = 'none';
    } else {
        makeOrBuySelect.disabled = false;
        makeOrBuyHidden.disabled = true;
        if (makeOrBuySelect.value === 'produto_acabado') {
            secaoReceita.style.display = 'block';
            secaoCombo.style.display = 'none';
        } else {
            secaoReceita.style.display = 'none';
            secaoCombo.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', atualizarFormulario);
</script>

</body>
</html>