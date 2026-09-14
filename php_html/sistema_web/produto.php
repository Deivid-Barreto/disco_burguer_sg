<?php

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
    } elseif ($acao === 'editar') {
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

        if (!empty($id_produto) && !empty($nome) && $preco_venda !== false && $preco_venda >= 0) {
            try {
                $pdo->beginTransaction();

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

                if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] === UPLOAD_ERR_OK) {
                    $pasta_destino = '../../img/uploads/';
                    
                    if (!is_dir($pasta_destino)) {
                        mkdir($pasta_destino, 0755, true);
                    }

                    $extensao = strtolower(pathinfo($_FILES['imagem_arquivo']['name'], PATHINFO_EXTENSION));
                    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($extensao, $extensoes_permitidas)) {
                        $novo_nome_arquivo = 'produto_' . $id_produto . '.' . $extensao;
                        $caminho_completo = $pasta_destino . $novo_nome_arquivo;

                        if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], $caminho_completo)) {
                            $sql_update_img = "UPDATE produtos SET imagem_caminho = :caminho WHERE id = :id";
                            $stmt_img = $pdo->prepare($sql_update_img);
                            $stmt_img->execute([
                                ':caminho' => $caminho_completo,
                                ':id'      => $id_produto
                            ]);
                        }
                    }
                }
                
                $pdo->prepare("DELETE FROM itens_combo WHERE combo_id = ?")->execute([$id_produto]);
                $pdo->prepare("DELETE FROM receitas WHERE produto_id = ?")->execute([$id_produto]);

                if ($categoria === 'combos' && isset($_POST['combo_itens'])) {
                    $sqlCombo = "INSERT INTO itens_combo (combo_id, produto_filho_id, quantidade) VALUES (:combo, :filho, :qtd)";
                    $stmtCombo = $pdo->prepare($sqlCombo);
                    
                    foreach ($_POST['combo_itens'] as $filho_id => $marcado) {
                        $qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 1);
                        if ($qtd > 0) {
                            $stmtCombo->execute([':combo' => $id_produto, ':filho' => $filho_id, ':qtd' => $qtd]);
                        }
                    }
                } 
                elseif ($make_or_buy === 'revenda' && !empty($_POST['estoque_revenda_id'])) {
                    $sqlReceita = "INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, 1)";
                    $stmtReceita = $pdo->prepare($sqlReceita);
                    $stmtReceita->execute([
                        ':prod' => $id_produto, 
                        ':ing'  => $_POST['estoque_revenda_id']
                    ]);
                }
                elseif ($make_or_buy === 'produto_acabado' && $categoria !== 'combos' && isset($_POST['ingredientes'])) {
                    $sqlReceita = "INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, :qtd)";
                    $stmtReceita = $pdo->prepare($sqlReceita);

                    foreach ($_POST['ingredientes'] as $ingred_id => $marcado) {
                        $qtd = (float)($_POST['ingrediente_qtd'][$ingred_id] ?? 0);
                        if ($qtd > 0) {
                            $stmtReceita->execute([':prod' => $id_produto, ':ing' => $ingred_id, ':qtd' => $qtd]);
                        }
                    }
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
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
            } catch (PDOException $e) {
                $pdo->rollBack();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Endpoint GET para carregar os dados na edição
if (isset($_GET['obter_produto'])) {
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

    $img_status_html = "";
    if (!empty($prod_data['imagem_caminho'])) {
        $img_status_html = "<span style='color: green;'>✔ Imagem cadastrada no sistema</span>";
    } else {
        $img_status_html = "<span style='color: #666;'>Nenhuma imagem cadastrada</span>";
    }

    echo "<script>
        document.getElementById('form_acao').value = 'editar';
        document.getElementById('form_id_produto').value = " . $prod_data['id'] . ";
        document.getElementById('modalTituloCadastro').innerText = 'Editar Produto: " . addslashes($prod_data['nome']) . "';
        document.getElementById('btnSubmitModal').innerText = 'Salvar Alterações';
        
        document.getElementById('nome').value = " . json_encode($prod_data['nome']) . ";
        document.getElementById('categorias').value = " . json_encode($prod_data['categorias']) . ";
        document.getElementById('descricao').value = " . json_encode($prod_data['descricao']) . ";
        document.getElementById('preco_venda').value = " . json_encode($prod_data['preco_venda']) . ";
        document.getElementById('make_or_buy').value = " . json_encode($prod_data['make_or_buy']) . ";
        document.getElementById('visivel').checked = " . ($prod_data['visivel'] ? 'true' : 'false') . ";
        document.getElementById('status_imagem_cadastrada').innerHTML = " . json_encode($img_status_html) . ";

        atualizarFormulario();";

        if ($prod_data['categorias'] === 'combos') {
            foreach($prod_comb as $c) {
                echo "
                if(document.getElementById('chk_combo_" . $c['produto_filho_id'] . "')) {
                    document.getElementById('chk_combo_" . $c['produto_filho_id'] . "').checked = true;
                    let inp = document.getElementById('qtd_combo_" . $c['produto_filho_id'] . "');
                    inp.value = " . $c['quantidade'] . ";
                    inp.style.display = 'block';
                    inp.disabled = false;
                }";
            }
        } elseif ($prod_data['make_or_buy'] === 'revenda') {
            if(count($prod_rec) > 0) {
                echo "document.getElementById('estoque_revenda_id').value = '" . $prod_rec[0]['ingrediente_id'] . "';";
            }
        } else {
            foreach($prod_rec as $r) {
                echo "
                if(document.getElementById('chk_ing_" . $r['ingrediente_id'] . "')) {
                    document.getElementById('chk_ing_" . $r['ingrediente_id'] . "').checked = true;
                    let inp = document.getElementById('qtd_ing_" . $r['ingrediente_id'] . "');
                    inp.value = " . $r['quantidade_usada'] . ";
                    inp.style.display = 'block';
                    inp.disabled = false;
                }";
            }
        }

    echo "document.getElementById('modalCadastro').showModal();</script>";
    exit;
}

$lista_estoque_ingredientes = [];
$lista_estoque_revenda = [];
$lista_produtos = [];
$produtosAtivos = [];
$prodSemReceita = [];
$combosVazios = [];

try {
    $stmtEstIng = $pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 0 ORDER BY nome ASC");
    $lista_estoque_ingredientes = $stmtEstIng->fetchAll(PDO::FETCH_ASSOC);

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
    <button onclick="abrirModalCadastro()">ADD NOVO PRODUTO</button>

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
    <h1 id="modalTituloCadastro">Cadastrar Novo Produto</h1>

    <?php if ($mensagem_sucesso): ?>
        <div><?= htmlspecialchars($mensagem_sucesso) ?></div>
    <?php endif; ?>
    
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
                                <input type="checkbox" id="chk_ing_<?= $item['id'] ?>" name="ingredientes[<?= $item['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_ing_<?= $item['id'] ?>')">
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
                                <input type="checkbox" id="chk_combo_<?= $prod['id'] ?>" name="combo_itens[<?= $prod['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_combo_<?= $prod['id'] ?>')">
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
            <div id="status_imagem_cadastrada" style="margin-top: 5px; font-size: 0.9em;"></div>
        </div>

        <div>
            <input type="checkbox" id="visivel" name="visivel" value="1" checked>
            <label for="visivel">Visível no Cardápio</label>
        </div>

        <button type="submit" id="btnSubmitModal">Cadastrar Produto</button>
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
            <button type="button" onclick="carregarDadosEdicao()">Editar / Adicionar Receita</button>
            <button type="submit">Excluir</button>
            <button type="button" onclick="document.getElementById('modalAcoes').close()">Cancelar</button>
        </div>
    </form>
</dialog>

<script>
function abrirModalCadastro() {
    document.getElementById('form_acao').value = 'cadastrar';
    document.getElementById('form_id_produto').value = '';
    document.getElementById('modalTituloCadastro').innerText = 'Cadastrar Novo Produto';
    document.getElementById('btnSubmitModal').innerText = 'Cadastrar Produto';
    document.getElementById('modalCadastro').querySelector('form').reset();
    document.getElementById('status_imagem_cadastrada').innerHTML = '';
    
    document.querySelectorAll('input[type=checkbox]').forEach(el => {
        if(el.id.startsWith('chk_')) {
            el.checked = false;
            let targetId = el.id.replace('chk_', 'qtd_');
            let inp = document.getElementById(targetId);
            if(inp) {
                inp.style.display = 'none';
                inp.disabled = true;
                inp.value = '';
            }
        }
    });

    atualizarFormulario();
    document.getElementById('modalCadastro').showModal();
}

function abrirAcoes(id, nome) {
    document.getElementById('acao_id_produto').value = id;
    document.getElementById('tituloAcoes').innerText = "Produto: " + nome;
    document.getElementById('modalAcoes').showModal();
}

async function carregarDadosEdicao() {
    const id = document.getElementById('acao_id_produto').value;
    document.getElementById('modalAcoes').close();

    document.getElementById('modalCadastro').querySelector('form').reset();
    document.getElementById('status_imagem_cadastrada').innerHTML = '';
    document.querySelectorAll('input[type=checkbox]').forEach(el => {
        if(el.id.startsWith('chk_')) {
            el.checked = false;
            let targetId = el.id.replace('chk_', 'qtd_');
            let inp = document.getElementById(targetId);
            if(inp) {
                inp.style.display = 'none';
                inp.disabled = true;
                inp.value = '';
            }
        }
    });

    let req = await fetch(window.location.pathname + '?obter_produto=' + id);
    let html = await req.text();
    
    let containerTemp = document.createElement('div');
    containerTemp.innerHTML = html;
    let scriptTag = containerTemp.querySelector('script');
    if (scriptTag) {
        eval(scriptTag.innerText);
    }
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