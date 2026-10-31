<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../php/conexao.php'; 


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['obter_produto'])) {
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $id_ed = (int)$_GET['obter_produto'];
    
    $st = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $st->execute([$id_ed]);
    $prod_data = $st->fetch(PDO::FETCH_ASSOC);

    $st_rec = $pdo->prepare("SELECT r.*, e.nome as ingrediente_nome, e.unidade_medida FROM receitas r JOIN estoque e ON r.ingrediente_id = e.id WHERE r.produto_id = ?");
    $st_rec->execute([$id_ed]);
    $prod_rec = $st_rec->fetchAll(PDO::FETCH_ASSOC);

    $st_comb = $pdo->prepare("SELECT c.*, p.nome as produto_nome FROM itens_combo c JOIN produtos p ON c.produto_filho_id = p.id WHERE c.combo_id = ?");
    $st_comb->execute([$id_ed]);
    $prod_comb = $st_comb->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'produto' => $prod_data,
        'receitas' => $prod_rec,
        'combos' => $prod_comb
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ação não autorizada.');
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'deletar') {
        try {
            $stmtImg = $pdo->prepare("SELECT imagem_caminho FROM produtos WHERE id = ?");
            $stmtImg->execute([$_POST['id_produto']]);
            $imgAntiga = $stmtImg->fetchColumn();
            
            if ($imgAntiga && file_exists($imgAntiga)) {
                unlink($imgAntiga);
            }

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

        $estoque_revenda_id = ($make_or_buy === 'revenda' && !empty($_POST['estoque_revenda_id'])) ? (int)$_POST['estoque_revenda_id'] : null;

        $tem_combo = false;
        if (isset($_POST['combo_itens']) && is_array($_POST['combo_itens'])) {
            foreach ($_POST['combo_itens'] as $filho_id => $marcado) {
                $qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 1);
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
        } elseif ($make_or_buy === 'revenda' && empty($estoque_revenda_id)) {
            $ativo = 0;
        }

        if (!empty($nome) && $preco_venda !== false && $preco_venda >= 0) {
            try {
                $pdo->beginTransaction();

                if ($acao === 'editar' && !empty($id_produto)) {
                    $sql_produto = "UPDATE produtos SET estoque_id = :estoque_id, categorias = :cat, nome = :nome, ativo = :ativo, descricao = :desc, preco_venda = :preco, visivel = :visivel, make_or_buy = :mob WHERE id = :id";
                    $stmt = $pdo->prepare($sql_produto);
                    $stmt->execute([
                        ':estoque_id' => $estoque_revenda_id,
                        ':cat'        => $categoria,
                        ':nome'       => $nome,
                        ':ativo'      => $ativo,
                        ':desc'       => $descricao,
                        ':preco'      => $preco_venda,
                        ':visivel'    => $visivel,
                        ':mob'        => $make_or_buy,
                        ':id'         => $id_produto
                    ]);
                    $alvo_id = $id_produto;
                } else {
                    $sql_produto = "INSERT INTO produtos (estoque_id, categorias, nome, ativo, descricao, preco_venda, visivel, make_or_buy) VALUES (:estoque_id, :cat, :nome, :ativo, :desc, :preco, :visivel, :mob)";
                    $stmt = $pdo->prepare($sql_produto);
                    $stmt->execute([
                        ':estoque_id' => $estoque_revenda_id,
                        ':cat'        => $categoria,
                        ':nome'       => $nome,
                        ':ativo'      => $ativo,
                        ':desc'       => $descricao,
                        ':preco'      => $preco_venda,
                        ':visivel'    => $visivel,
                        ':mob'        => $make_or_buy
                    ]);
                    $alvo_id = $pdo->lastInsertId();
                }

                if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] === UPLOAD_ERR_OK) {
                    $pasta_destino = '../../img/uploads/';
                    if (!is_dir($pasta_destino)) mkdir($pasta_destino, 0755, true);
                    $extensao = strtolower(pathinfo($_FILES['imagem_arquivo']['name'], PATHINFO_EXTENSION));

                    $stmt_old =$pdo->prepare("SELECT imagem_caminho FROM produtos WHERE id = ?");
                    $stmt_old->execute([$alvo_id]);
                    $caminho_antigo =$stmt_old->fetchColumn();

                    if ($caminho_antigo && file_exists($caminho_antigo)) {
                        unlink($caminho_antigo);
                    }

                    $versao_imagem = uniqid();
                    $caminho_completo = $pasta_destino . 'produto_' .$alvo_id . '_' . $versao_imagem . '.' .$extensao;
                    
                    if (move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'],$caminho_completo)) {
                        $stmt_img =$pdo->prepare("UPDATE produtos SET imagem_caminho = :caminho WHERE id = :id");
                        $stmt_img->execute([':caminho' => $caminho_completo, ':id' =>$alvo_id]);
                    }
                }
                
                $pdo->prepare("DELETE FROM itens_combo WHERE combo_id = ?")->execute([$alvo_id]);
                $pdo->prepare("DELETE FROM receitas WHERE produto_id = ?")->execute([$alvo_id]);

                if ($categoria === 'combos' && isset($_POST['combo_itens'])) {
                    $stmtCombo =$pdo->prepare("INSERT INTO itens_combo (combo_id, produto_filho_id, quantidade) VALUES (:combo, :filho, :qtd)");
                    foreach ($_POST['combo_itens'] as$filho_id => $marcado) {$qtd = (int)($_POST['combo_qtd'][$filho_id] ?? 1);
                        if ($qtd > 0) $stmtCombo->execute([':combo' =>$alvo_id, ':filho' => $filho_id, ':qtd' =>$qtd]);
                    }
                } 
                elseif ($make_or_buy === 'produto_acabado' && $categoria !== 'combos' && isset($_POST['ingredientes'])) {
                    $stmtReceita =$pdo->prepare("INSERT INTO receitas (produto_id, ingrediente_id, quantidade_usada) VALUES (:prod, :ing, :qtd)");
                    foreach ($_POST['ingredientes'] as$ingred_id => $marcado) {$qtd = (float)($_POST['ingrediente_qtd'][$ingred_id] ?? 0);
                        if ($qtd > 0) $stmtReceita->execute([':prod' =>$alvo_id, ':ing' => $ingred_id, ':qtd' =>$qtd]);
                    }
                }

                $pdo->commit();
            } catch (PDOException $e) {$pdo->rollBack();
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

$lista_estoque_ingredientes = [];$lista_estoque_revenda = [];
$lista_produtos = [];$produtos_por_categoria = [];
$produtosCompletos = [];$produtosInativos = [];

try {
    $pdo->exec("UPDATE produtos p SET ativo = 0 WHERE ativo = 1 AND (
        (categorias = 'combos' AND NOT EXISTS (SELECT 1 FROM itens_combo c WHERE c.combo_id = p.id)) OR
        (categorias != 'combos' AND make_or_buy = 'produto_acabado' AND NOT EXISTS (SELECT 1 FROM receitas r WHERE r.produto_id = p.id)) OR
        (categorias IN ('hamburguer', 'porcoes', 'sobremesa_gelada', 'extras') AND NOT EXISTS (SELECT 1 FROM receitas r WHERE r.produto_id = p.id)) OR
        (make_or_buy = 'revenda' AND (estoque_id IS NULL OR estoque_id = 0))
    )");

    $pdo->exec("UPDATE produtos p SET ativo = 1 WHERE ativo = 0 AND (
        (categorias = 'combos' AND EXISTS (SELECT 1 FROM itens_combo c WHERE c.combo_id = p.id)) OR
        (categorias != 'combos' AND make_or_buy = 'produto_acabado' AND EXISTS (SELECT 1 FROM receitas r WHERE r.produto_id = p.id)) OR
        (categorias IN ('hamburguer', 'porcoes', 'sobremesa_gelada', 'extras') AND EXISTS (SELECT 1 FROM receitas r WHERE r.produto_id = p.id)) OR
        (make_or_buy = 'revenda' AND estoque_id IS NOT NULL AND estoque_id > 0)
    )");

    $stmtEstIng =$pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 0 ORDER BY nome ASC");
    $lista_estoque_ingredientes =$stmtEstIng->fetchAll(PDO::FETCH_ASSOC);

    $stmtEstRev =$pdo->query("SELECT id, nome, unidade_medida FROM estoque WHERE eh_revenda = 1 ORDER BY nome ASC");
    $lista_estoque_revenda =$stmtEstRev->fetchAll(PDO::FETCH_ASSOC);

    $stmtProd =$pdo->query("SELECT id, nome, categorias FROM produtos WHERE categorias != 'combos' AND ativo = 1 ORDER BY nome ASC");
    $lista_produtos =$stmtProd->fetchAll(PDO::FETCH_ASSOC);

    foreach($lista_produtos as $p) {$produtos_por_categoria[$p['categorias']][] =$p;
    }

  $produtosCompletos = $pdo->query("SELECT id, nome, categorias, descricao, preco_venda, imagem_caminho FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
  $produtosInativos = $pdo->query("SELECT id, nome, categorias, descricao, preco_venda, imagem_caminho FROM produtos WHERE ativo = 0 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
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
     <link rel="stylesheet" href="../../css/modal.css">
    <link rel="stylesheet" href="../../css/sistema_web/produto.css">

 
    <title>Gestão de Produtos</title>
 
</head>
<body>
 
<?php include __DIR__ . "/../../includes/painel.php"; ?>
 
<?php include __DIR__ . "/../../includes/header_sistema.php"; ?>
 
 

    <div class="conteudo container-fluid ">
 
        <div class="nav-botoes menu-cardapio d-flex gap-3 mt-4">
            <button type="button" class="btn-filtro-cat ativo" data-categoria="todos">Todos</button>
            <button type="button" class="btn-filtro-cat" data-categoria="hamburguer">Hamburguers</button>
            <button type="button" class="btn-filtro-cat" data-categoria="combos">Combos</button>
            <button type="button" class="btn-filtro-cat" data-categoria="bebidas">Bebidas</button>
            <button type="button" class="btn-filtro-cat" data-categoria="porcoes">Porções</button>
            <button type="button" class="btn-filtro-cat" data-categoria="sobremesa_gelada">Sobremesas</button>

            <button class="ms-auto btn-cardapio" type="button" onclick="abrirModalCadastro()"> + PRODUTO</button>
        </div>

       
        <?php foreach($produtosCompletos as $p): ?>

               <button type="button" class="card-produto" data-categoria="<?= $p['categorias'] ?>" onclick="abrirAcoes(<?= $p['id'] ?>)">

                   <span class="produto-nome"><?= htmlspecialchars($p['nome']) ?></span>
        <?php if (!empty($p['imagem_caminho'])): ?>
            <img src="<?= htmlspecialchars($p['imagem_caminho']) ?>" alt="Foto do produto" class="img-produto">
        <?php endif; ?>
    
        <span class="produto-descricao"><?= htmlspecialchars($p['descricao']) ?></span>
        <span class="produto-preco">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></span>
        </button>


        <?php endforeach; ?>
        <?php if(empty($produtosCompletos)): ?>
            <p>Nenhum Produto Completo</p>
        <?php endif; ?>

        
        <div class="row  flex-wrap g-2 mt-2 pb-5">
            <h2>PRODUTOS SEM RECEITA</h2>
        <?php foreach($produtosInativos as $p): ?> 
            <div class="col-3 mb-2">
            <button   type="button" class="card-produto mb-2" data-categoria="<?= $p['categorias'] ?>" onclick="abrirAcoes(<?= $p['id'] ?>)">

                <span class="produto-nome"><?= htmlspecialchars($p['nome']) ?></span>
        <?php if (!empty($p['imagem_caminho'])): ?>
            <img src="<?= htmlspecialchars($p['imagem_caminho']) ?>" alt="Foto do produto" class="img-produto">
        <?php endif; ?>
       
        <span class="produto-descricao"><?= htmlspecialchars($p['descricao']) ?></span>
        <div class="d-flex align-items-center mt-2 gap-5">
        <span class="produto-categoria"><?= htmlspecialchars($p['categorias']) ?></span>
        <span class="produto-preco">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></span>
        </div>
    </button>
          </div>
        <?php endforeach; ?>
        
        
        <?php if(empty($produtosInativos)): ?>
            <p>Nenhum produto inativo no momento.</p>
        <?php endif; ?>
 
    </div>
    
  
 
 
<dialog id="modalCadastro" class="ModalProdutoCadastrar d-flex flex-column align-items-center justify-content-center">
    <h1 id="modalTituloCadastro">Cadastrar Novo Produto</h1>
 
    <form id="formProduto" method="POST" action="" enctype="multipart/form-data" onsubmit="confirmarSalvarProduto(event)">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
 
        <div id="secao-revenda" hidden>
            <h3>Vínculo de Estoque</h3>
            <label for="estoque_revenda_id">Item para baixa automática:</label>
            <select id="estoque_revenda_id" name="estoque_revenda_id">
                <option value="">-- Selecione --</option>
                <?php foreach($lista_estoque_revenda as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['nome']) ?> (<?= htmlspecialchars($item['unidade_medida']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
 
        <div id="secao-receita" hidden>
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
                            <td><input type="number" id="qtd_ing_<?= $item['id'] ?>" name="ingrediente_qtd[<?= $item['id'] ?>]" step="0.01" min="0.01" placeholder="Qtd" disabled hidden></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
 
        <div id="secao-combo" hidden>
            <h3>Itens do Combo</h3>
            <?php if(!empty($produtos_por_categoria)): ?>
 
                <div>
                    <?php
                    $primeira_cat = array_key_first($produtos_por_categoria);
                    foreach(array_keys($produtos_por_categoria) as $cat): ?>
                        <button type="button" onclick="mostrarCategoriaCombo('<?= $cat ?>')">
                            <?= ucfirst(str_replace('_', ' ', $cat)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
 
                <?php foreach ($produtos_por_categoria as $cat => $produtos): ?>
                    <div id="combo-cat-<?= $cat ?>" class="tabela-combo-categoria" <?= $cat === $primeira_cat ? '' : 'hidden' ?>>
                        <table>
                            <thead>
                                <tr><th>Incluir</th><th>Produto</th><th>Quantidade</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produtos as $prod): ?>
                                    <tr>
                                        <td><input type="checkbox" id="chk_combo_<?= $prod['id'] ?>" name="combo_itens[<?= $prod['id'] ?>]" value="1" onchange="toggleQtd(this, 'qtd_combo_<?= $prod['id'] ?>')"></td>
                                        <td><?= htmlspecialchars($prod['nome']) ?></td>
                                        <td><input type="number" id="qtd_combo_<?= $prod['id'] ?>" name="combo_qtd[<?= $prod['id'] ?>]" min="1" value="1" placeholder="Qtd" disabled hidden></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
 
            <?php else: ?>
                <p>Nenhum produto cadastrado para montar combo.</p>
            <?php endif; ?>
        </div>
 
        <div>
            <label for="imagem_arquivo">Imagem do Produto</label>
            <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/*">
            <div id="status_imagem_cadastrada"></div>
        </div>
 
        <div>
            <input type="checkbox" id="visivel" name="visivel" value="1" checked>
            <label for="visivel">Visível no Cardápio</label>
        </div>
        
        <div class="btn-dialog-cadastrar">
        <button type="submit" id="btnSubmitModal">Cadastrar</button>
        <button type="button" onclick="document.getElementById('modalCadastro').close()">Fechar</button>
        </div>
    </form>
</dialog>
 
<dialog id="modalAcoes" class="ModalProdutoCadastrar ModalAcoes">
    <h1 id="tituloAcoes">Ações do Produto</h1>
 
    <div id="detalhes_produto_info"></div>
 
    <input type="hidden" id="acao_id_produto" value="">
 
    <div class="btn-dialog-cadastrar">
        <button type="button" id="btnEditarAcoes" onclick="carregarDadosEdicao()" disabled>Editar</button>
        <button type="button" class="btn-dialog-excluir" onclick="confirmarExclusao()">Excluir</button>
        <button type="button" onclick="document.getElementById('modalAcoes').close()">Cancelar</button>
    </div>
</dialog>
 
<form id="formDeletar" method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="acao" value="deletar">
    <input type="hidden" name="id_produto" id="deletar_id_produto" value="">
</form>
 
<dialog id="modalConfirmacaoCustom" class="ModalProdutoCadastrar ModalConfirmacao">
    <h1 id="tituloConfirmacao">Confirmação</h1>
    <p id="mensagemConfirmacao">Tem certeza que deseja prosseguir?</p>
    <div class="btn-dialog-cadastrar">
        <button type="button" id="btnSimConfirmacao">Sim</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoCustom').close()">Cancelar</button>
    </div>
</dialog>
</div>
<script>
let produtoAtualData = null;
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

function confirmarSalvarProduto(e) {
    e.preventDefault();
    const form = e.target;
    const acao = document.getElementById('form_acao').value;
    const msg = (acao === 'editar') ? "Tem certeza que deseja salvar as alterações deste produto?" : "Tem certeza que deseja cadastrar este produto?";

    dispararConfirmacao(msg, function() {
        habilitarCamposAntesDeEnviar();
        HTMLFormElement.prototype.submit.call(form);
    });
}

function confirmarExclusao() {
    const id = document.getElementById('acao_id_produto').value;
    dispararConfirmacao("Tem certeza que deseja excluir este produto? Esta ação não poderá ser desfeita.", function() {
        document.getElementById('modalAcoes').close();
        document.getElementById('deletar_id_produto').value = id;
        document.getElementById('formDeletar').submit();
    });
}

function resetarFormulario() {
    document.getElementById('formProduto').reset();
    document.getElementById('status_imagem_cadastrada').innerText = '';
    document.querySelectorAll('input[type=checkbox]').forEach(el => {
        if(el.id.startsWith('chk_')) {
            el.checked = false;
            let inp = document.getElementById(el.id.replace('chk_', 'qtd_'));
            if(inp) { 
                inp.hidden = true; 
                inp.disabled = true; 
                inp.value = ''; 
            }
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
            document.getElementById('detalhes_produto_info').innerHTML = "<p>Erro na leitura dos dados.</p>";
            return;
        }

        let p = produtoAtualData.produto;
        document.getElementById('acao_id_produto').value = p.id;
        document.getElementById('tituloAcoes').innerText = "Produto: " + p.nome;

        let precoFmt = parseFloat(p.preco_venda).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        let statusText = p.ativo == 1 ? '<span class="status-tag status-ok">Ativo</span>' : '<span class="status-tag status-pendente">Inativo (Pendente)</span>';
        let statusImagem = (p.imagem_caminho && p.imagem_caminho.trim() !== '') ? '<span class="status-tag status-ok">✔ Com imagem</span>' : '<span class="status-tag status-sem">❌ Sem imagem</span>';

        let detalhesReceita = '<br>';
        if (p.categorias === 'combos' && produtoAtualData.combos.length > 0) {
            detalhesReceita += '<p><strong>Itens do Combo:</strong></p><ul>';
            produtoAtualData.combos.forEach(c => {
                detalhesReceita += `<li>${c.quantidade}x ${c.produto_nome}</li>`;
            });
            detalhesReceita += '</ul>';
        } else if (p.make_or_buy === 'produto_acabado' && p.categorias !== 'combos' && produtoAtualData.receitas.length > 0) {
            detalhesReceita += '<p><strong>Receita:</strong></p><ul>';
            produtoAtualData.receitas.forEach(r => {
                detalhesReceita += `<li>${r.quantidade_usada}${r.unidade_medida} de ${r.ingrediente_nome}</li>`;
            });
            detalhesReceita += '</ul>';
        } else if (p.make_or_buy === 'revenda') {
            detalhesReceita += '<p><strong>Tipo:</strong> Produto de Revenda</p>';
        } else {
            detalhesReceita += '<p><strong>Receita:</strong> ❌ Nenhuma receita cadastrada (Produto Inativo)</p>';
        }

        document.getElementById('detalhes_produto_info').innerHTML = `
            <p><strong>Categoria:</strong> ${p.categorias.toUpperCase()}</p>
            <p><strong>Preço:</strong> R$ ${precoFmt}</p>
            <p><strong>Descrição:</strong> ${p.descricao}</p>
            <p><strong>Status:</strong> ${statusText}</p>
            <p><strong>Imagem:</strong> ${statusImagem}</p>
            ${detalhesReceita}
        `;
        document.getElementById('btnEditarAcoes').disabled = false;

    } catch (err) {
        document.getElementById('detalhes_produto_info').innerHTML = "<p>Falha de conexão.</p>";
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

    if (p.imagem_caminho) document.getElementById('status_imagem_cadastrada').innerText = "✔ Imagem salva";

    atualizarFormulario();

    if (p.categorias === 'combos') {
        data.combos.forEach(c => {
            let chk = document.getElementById('chk_combo_' + c.produto_filho_id);
            if (chk) {
                chk.checked = true;
                let inp = document.getElementById('qtd_combo_' + c.produto_filho_id);
                inp.value = c.quantidade; 
                inp.hidden = false; 
                inp.disabled = false;
            }
        });
    } else if (p.make_or_buy === 'revenda') {
        if (p.estoque_id) {
            document.getElementById('estoque_revenda_id').value = p.estoque_id;
        }
    } else {
        data.receitas.forEach(r => {
            let chk = document.getElementById('chk_ing_' + r.ingrediente_id);
            if (chk) {
                chk.checked = true;
                let inp = document.getElementById('qtd_ing_' + r.ingrediente_id);
                inp.value = r.quantidade_usada; 
                inp.hidden = false; 
                inp.disabled = false;
            }
        });
    }

    document.getElementById('modalCadastro').showModal();
}

function toggleQtd(checkbox, inputId) {
    const inputQtd = document.getElementById(inputId);
    if (inputQtd) {
        inputQtd.hidden = !checkbox.checked;
        inputQtd.disabled = !checkbox.checked;
        if(checkbox.checked) inputQtd.focus();
    }
}

function mostrarCategoriaCombo(categoria) {
    document.querySelectorAll('.tabela-combo-categoria').forEach(el => {
        el.hidden = true;
    });
    let abaSelecionada = document.getElementById('combo-cat-' + categoria);
    if(abaSelecionada) {
        abaSelecionada.hidden = false;
    }
}

function atualizarFormulario() {
    const cat = document.getElementById('categorias').value;
    const mbSel = document.getElementById('make_or_buy');
    const mbHid = document.getElementById('make_or_buy_hidden');
    
    document.getElementById('secao-combo').hidden = true;
    document.getElementById('secao-receita').hidden = true;
    document.getElementById('secao-revenda').hidden = true;

    if (!cat) return;

    if (['hamburguer', 'porcoes', 'combos', 'sobremesa_gelada', 'extras'].includes(cat)) {
        mbSel.value = 'produto_acabado'; 
        mbSel.disabled = true;
        mbHid.disabled = false; 
        mbHid.value = 'produto_acabado';
    } else {
        mbSel.disabled = false; 
        mbHid.disabled = true;
        if (cat === 'bebidas' && !mbSel.value) mbSel.value = 'revenda';
    }

    const tipoAtual = mbSel.value || mbHid.value;

    if (cat === 'combos') {
        document.getElementById('secao-combo').hidden = false;
    } else if (tipoAtual === 'revenda') {
        document.getElementById('secao-revenda').hidden = false;
    } else if (tipoAtual === 'produto_acabado') {
        document.getElementById('secao-receita').hidden = false;
    }
}

function habilitarCamposAntesDeEnviar() {
    const mbSel = document.getElementById('make_or_buy');
    const mbHid = document.getElementById('make_or_buy_hidden');
    
    mbSel.disabled = false;
    
    if (mbSel.value !== "") {
        mbHid.disabled = true;
    } else {
        mbHid.disabled = false;
    }
    
    document.querySelectorAll('#formProduto input, #formProduto select').forEach(el => {
        if (el.type === 'number' && !el.hidden) {
            el.disabled = false;
        }
    });
}

document.querySelectorAll('.btn-filtro-cat').forEach(botao => {
    botao.addEventListener('click', function() {
        document.querySelectorAll('.btn-filtro-cat').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const categoriaSelecionada = this.getAttribute('data-categoria');
        const cards = document.querySelectorAll('.card-produto');

        cards.forEach(card => {
            const catCard = card.getAttribute('data-categoria');
            
            if (categoriaSelecionada === 'todos' || catCard === categoriaSelecionada) {
                card.style.display = ''; 
            } else {
                card.style.display = 'none'; 
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

        <script src="../../js/painel.js"></script>
</body>
</html>