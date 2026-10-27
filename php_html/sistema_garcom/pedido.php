<?php
session_start();

require_once __DIR__ . '/../../php/conexao.php';
require_once __DIR__ . '/../sistema_web/verificar_login.php';

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if (isset($_GET['obter_pedido'])) {
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $id_ed = $_GET['obter_pedido'];
    
    $st = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND atendente_id = ?");
    $st->execute([$id_ed, $funcionario_id]);
    $pedido_data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$pedido_data) {
        echo json_encode(['erro' => 'Pedido não encontrado ou não autorizado.']);
        exit;
    }

    $stmtItens = $pdo->prepare("
        SELECT
            ip.id,
            ip.quantidade,
            ip.status_item,
            p.id AS produto_id,
            p.nome,
            p.preco_venda
        FROM itens_pedidos ip
        INNER JOIN produtos p ON p.id = ip.produto_id
        WHERE ip.pedido_id = ?
    ");
    $stmtItens->execute([$id_ed]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    $stmtCustomizacoes = $pdo->prepare("
        SELECT
            cip.item_pedido_id,
            cip.produto_id,
            cip.tipo_acao,
            cip.quantidade,
            p.nome,
            p.preco_venda
        FROM customizacoes_item_pedido cip
        INNER JOIN produtos p ON p.id = cip.produto_id
        WHERE cip.item_pedido_id = ?
    ");

    foreach ($itens as &$item) {
        $stmtCustomizacoes->execute([$item['id']]);
        $item['customizacoes'] = $stmtCustomizacoes->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($item);

    echo json_encode([
        'pedido' => $pedido_data,
        'itens' => $itens
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'solicitar_fechamento') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'aguardando_pagamento' WHERE id = ? AND atendente_id = ?");
            $stmt->execute([$id_pedido, $funcionario_id]);
            echo json_encode(['sucesso' => true]);
        } catch (PDOException $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    } elseif ($acao === 'editar') {
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $nome_cliente = trim($_POST['nome_cliente'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $mesa_id = !empty($_POST['mesa_id']) ? (int)$_POST['mesa_id'] : null;
        $valor_total = (float)($_POST['valor_total'] ?? 0);
        $itens_pedido = json_decode($_POST['itens_pedido'] ?? '[]', true);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET nome_cliente = ?, tipo = ?, mesa_id = ?, valor_total = ?
                WHERE id = ? AND atendente_id = ? AND status = 'aberto'
            ");
            $stmt->execute([$nome_cliente, $tipo, $mesa_id, $valor_total, $id_pedido, $funcionario_id]);

            $stmt = $pdo->prepare("
               DELETE cip FROM customizacoes_item_pedido cip
               INNER JOIN itens_pedidos ip ON ip.id = cip.item_pedido_id
               WHERE ip.pedido_id = ?
            ");
            $stmt->execute([$id_pedido]);

            $stmt = $pdo->prepare("DELETE FROM itens_pedidos WHERE pedido_id = ?");
            $stmt->execute([$id_pedido]);

            $stmtItem = $pdo->prepare("
                INSERT INTO itens_pedidos (pedido_id, produto_id, quantidade, status_item)
                VALUES (?, ?, ?, 'pendente')
            ");
            $stmtCustomizacao = $pdo->prepare("
                INSERT INTO customizacoes_item_pedido (item_pedido_id, produto_id, tipo_acao, quantidade)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($itens_pedido as $item) {
                $stmtItem->execute([$id_pedido, (int)$item['id'], (int)$item['quantidade']]);
                $item_pedido_id = $pdo->lastInsertId();

                foreach (($item['customizacoes'] ?? []) as $extra) {
                    $stmtCustomizacao->execute([$item_pedido_id, (int)$extra['produto_id'], $extra['tipo_acao'] ?? 'adicionar', (float)$extra['quantidade']]);
                }
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("Erro ao editar pedido: " . $e->getMessage());
        }    
    } elseif ($acao === 'cadastrar') {
        $nome_cliente = trim($_POST['nome_cliente'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $mesa_id = !empty($_POST['mesa_id']) ? (int)$_POST['mesa_id'] : null;
        $valor_total = (float)($_POST['valor_total'] ?? 0);
        
        $chave_validacao = strtoupper(bin2hex(random_bytes(4))); 
        $itens_pedido = json_decode($_POST['itens_pedido'] ?? '[]', true);

        try {
            $pdo->beginTransaction();
            $pedido_id = null;

            if ($tipo === 'local' && $mesa_id) {
                $stmtBusca = $pdo->prepare("
                    SELECT id FROM pedidos
                    WHERE mesa_id = ? AND atendente_id = ? AND status = 'aberto'
                    LIMIT 1
                ");
                $stmtBusca->execute([$mesa_id, $funcionario_id]);
                $pedidoExistente = $stmtBusca->fetch(PDO::FETCH_ASSOC);

                if ($pedidoExistente) {
                    $pedido_id = $pedidoExistente['id'];
                    $stmtUpdate = $pdo->prepare("UPDATE pedidos SET valor_total = valor_total + ?, nome_cliente = ? WHERE id = ?");
                    $stmtUpdate->execute([$valor_total, $nome_cliente, $pedido_id]);
                }
            }

            if (!$pedido_id) {
                $stmtPedido = $pdo->prepare("
                    INSERT INTO pedidos (mesa_id, atendente_id, nome_cliente, tipo, status, valor_total, chave_validacao)
                    VALUES (?, ?, ?, ?, 'aberto', ?, ?)
                ");
                $stmtPedido->execute([$mesa_id, $funcionario_id, $nome_cliente, $tipo, $valor_total, $chave_validacao]);
                $pedido_id = $pdo->lastInsertId();
            }

            $stmtItem = $pdo->prepare("
                INSERT INTO itens_pedidos (pedido_id, produto_id, quantidade, status_item)
                VALUES (?, ?, ?, 'pendente')
            ");
            $stmtCustomizacao = $pdo->prepare("
                INSERT INTO customizacoes_item_pedido (item_pedido_id, produto_id, tipo_acao, quantidade)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($itens_pedido as $item) {
                $stmtItem->execute([$pedido_id, (int)$item['id'], (int)$item['quantidade']]);
                $item_pedido_id = $pdo->lastInsertId();

                foreach (($item['customizacoes'] ?? []) as $extra) {
                    $stmtCustomizacao->execute([$item_pedido_id, (int)$extra['produto_id'], $extra['tipo_acao'] ?? 'adicionar', (float)$extra['quantidade']]);
                }
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("Erro ao cadastrar pedido: " . $e->getMessage());
        }
    }
}

$mesas = [];
$locais = [];
$delivery = [];
$produtosAtivos = [];

try {
    $stmtMesas = $pdo->query("SELECT id, numero FROM mesas ORDER BY id ASC");
    $mesas = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);

    $stmtProdutos = $pdo->query("
        SELECT DISTINCT p.id, p.categorias, p.nome, p.descricao, p.preco_venda, p.imagem_caminho 
        FROM produtos p
        WHERE p.ativo = 1 
          AND LOWER(p.categorias) NOT IN ('extras', 'extra')
          AND (
              EXISTS (
                  SELECT 1 FROM estoque e 
                  INNER JOIN lotes_estoque l ON l.estoque_id = e.id 
                  WHERE e.id = p.estoque_id AND l.quantidade_estoque > 0
              )
              OR
              EXISTS (
                  SELECT 1 FROM receitas r
                  INNER JOIN estoque e ON e.id = r.ingrediente_id
                  INNER JOIN lotes_estoque l ON l.estoque_id = e.id
                  WHERE r.produto_id = p.id AND l.quantidade_estoque > 0
              )
          )
        ORDER BY p.nome ASC
    ");
    $produtosAtivos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

    $stmtLocais = $pdo->prepare("SELECT p.*, m.numero as numero_mesa FROM pedidos p LEFT JOIN mesas m ON m.id = p.mesa_id WHERE p.tipo = 'local' AND p.atendente_id = ? AND p.status = 'aberto' ORDER BY p.id DESC");
    $stmtLocais->execute([$funcionario_id]);
    $locais = $stmtLocais->fetchAll(PDO::FETCH_ASSOC);

    $stmtDelivery = $pdo->prepare("SELECT * FROM pedidos WHERE tipo = 'delivery' AND atendente_id = ? AND status = 'aberto' ORDER BY id DESC");
    $stmtDelivery->execute([$funcionario_id]);
    $delivery = $stmtDelivery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

$extras = [];
try {
    $stmtExtras = $pdo->query("
        SELECT DISTINCT p.id, p.nome, p.descricao, p.preco_venda, p.imagem_caminho 
        FROM produtos p
        WHERE p.ativo = 1 
          AND LOWER(p.categorias) IN ('extras', 'extra')
          AND (
              EXISTS (
                  SELECT 1 FROM estoque e 
                  INNER JOIN lotes_estoque l ON l.estoque_id = e.id 
                  WHERE e.id = p.estoque_id AND l.quantidade_estoque > 0
              )
              OR
              EXISTS (
                  SELECT 1 FROM receitas r
                  INNER JOIN estoque e ON e.id = r.ingrediente_id
                  INNER JOIN lotes_estoque l ON l.estoque_id = e.id
                  WHERE r.produto_id = p.id AND l.quantidade_estoque > 0
              )
          )
        ORDER BY p.nome ASC
    ");
    $extras = $stmtExtras->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao carregar extras: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - Atendente</title>
    <style>
        @media print {
            body * { visibility: hidden; }
            #areaRecibo, #areaRecibo * { visibility: visible; }
            #areaRecibo { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

<div>
    <div>
        <h2>Pedidos Locais</h2>
        <button type="button" onclick="abrirModalCadastro('local')">+ Novo Pedido Local</button>
        <?php if (empty($locais)): ?>
            <p>Nenhum pedido local aberto.</p>
        <?php else: ?>
            <?php foreach ($locais as $pedido): ?>
                <div style="display: flex; gap: 5px; margin-top: 5px; align-items: center;">
                    <button type="button" onclick="abrirPedidoParaEditar(<?= $pedido['id'] ?>)" style="flex: 1; text-align: left; padding: 8px; cursor: pointer;">
                        Pedido <?= $pedido['id'] ?> — Mesa <?= $pedido['numero_mesa'] ?? 'N/A' ?> (<?= htmlspecialchars($pedido['nome_cliente']) ?>)
                    </button>
                    <button type="button" onclick="abrirModalFecharContaDireto(<?= $pedido['id'] ?>)" style="padding: 8px; background: #e6f2ff; cursor: pointer; font-weight: bold;" title="Fechar Conta / Imprimir Pré-conta">🖨️ Fechar Conta</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <br>
    <div>
        <h2>Meus Delivery</h2>
        <button type="button" onclick="abrirModalCadastro('delivery')">+ Novo Pedido Delivery</button>
        <?php if (empty($delivery)): ?>
            <p>Nenhum pedido de delivery aberto.</p>
        <?php else: ?>
            <?php foreach ($delivery as $pedido): ?>
                <div style="display: flex; gap: 5px; margin-top: 5px; align-items: center;">
                    <button type="button" onclick="abrirPedidoParaEditar(<?= $pedido['id'] ?>)" style="flex: 1; text-align: left; padding: 8px; cursor: pointer;">
                        Pedido <?= $pedido['id'] ?> — <?= htmlspecialchars($pedido['nome_cliente']) ?>
                    </button>
                    <button type="button" onclick="abrirModalFecharContaDireto(<?= $pedido['id'] ?>)" style="padding: 8px; background: #e6f2ff; cursor: pointer; font-weight: bold;" title="Fechar Conta / Imprimir Pré-conta">🖨️ Fechar Conta</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
    
<dialog id="modalCadastro">
    <h1 id="modalTituloCadastro">Fazer Novo Pedido</h1>

    <form id="formCadastroPedido" method="POST" action="" onsubmit="interceptarEnvioFormulario(event)">
        <input type="hidden" id="form_acao" name="acao" value="cadastrar">
        <input type="hidden" id="form_id_pedido" name="id_pedido" value="">
        
        <input type="hidden" id="tipo" name="tipo" value="local">

        <div>
            <label for="nome_cliente">Nome do Cliente</label>
            <input type="text" id="nome_cliente" name="nome_cliente" required>
        </div>

        <div id="secao-mesas" style="display: none;">
            <label for="mesa_id">Mesa</label>
            <select id="mesa_id" name="mesa_id">
                <option value="" hidden>Selecione uma mesa</option>
                <?php foreach ($mesas as $mesa): ?>
                <option value="<?= $mesa['id'] ?>">Mesa <?= htmlspecialchars($mesa['numero']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <section class="area-cardapio">
            <h2>CARDÁPIO</h2>
            <div class="categorias-cardapio menu-cardapio d-flex gap-3 mt-4">
                <button type="button" class="btn-filtro-cat active" data-categoria="todos" onclick="filtrarProdutos('todos', this)">Todos</button>
                <button type="button" class="btn-filtro-cat" data-categoria="hamburguer" onclick="filtrarProdutos('hamburguer', this)">Hamburguers</button>
                <button type="button" class="btn-filtro-cat" data-categoria="combos" onclick="filtrarProdutos('combos', this)">Combos</button>
                <button type="button" class="btn-filtro-cat" data-categoria="bebidas" onclick="filtrarProdutos('bebidas', this)">Bebidas</button>
                <button type="button" class="btn-filtro-cat" data-categoria="porcoes" onclick="filtrarProdutos('porcoes', this)">Porções</button>
                <button type="button" class="btn-filtro-cat" data-categoria="sobremesa_gelada" onclick="filtrarProdutos('sobremesa_gelada', this)">Sobremesas</button>
            </div>

            <br>

            <div class="grade-produtos">
                <?php if (empty($produtosAtivos)): ?>
                    <p>Nenhum produto com estoque disponível no momento.</p>
                <?php else: ?>
                    <?php foreach ($produtosAtivos as $produto): ?>
                        <button type="button" class="card-produto" 
                            data-categoria="<?= htmlspecialchars($produto['categorias'] ?? '') ?>"
                            onclick="adicionarProduto(<?= $produto['id'] ?>, '<?= htmlspecialchars($produto['nome'], ENT_QUOTES) ?>', <?= (float)$produto['preco_venda'] ?>, '<?= htmlspecialchars($produto['descricao'], ENT_QUOTES) ?>', '<?= htmlspecialchars($produto['imagem_caminho'] ?? '', ENT_QUOTES) ?>')"
                            style="display: block; width: 100%; margin-bottom: 10px; text-align: left; padding: 10px;">
                            
                            <?php if (!empty($produto['imagem_caminho'])): ?>
                                <div class="produto-imagem">
                                    <img src="<?= htmlspecialchars($produto['imagem_caminho']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" style="max-width: 60px; height: auto;"><br>
                                </div>
                            <?php endif; ?>

                            <div class="produto-info">
                                <strong><?= htmlspecialchars($produto['nome']) ?></strong><br>
                                <span class="preco">R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></span><br>
                                <small class="descricao"><?= htmlspecialchars($produto['descricao']) ?></small>
                            </div>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="resumo-pedido">
            <h2>Itens do Pedido</h2>
            <div id="listaProdutosPedido">
                <div class="pedido-vazio">Nenhum produto adicionado.</div>
            </div>
            <div class="total-pedido">
                <span>Total</span>
                <strong id="valorTotalTexto">R$ 0,00</strong>
            </div>
            <input type="hidden" name="valor_total" id="valor_total" value="0.00">
            <input type="hidden" name="itens_pedido" id="itens_pedido">
        </section>

        <div style="margin-top: 15px;">
            <button type="submit" class="btn-enviar-pedido" id="btnSubmitModal">Enviar Para a Cozinha</button>
            <button type="button" onclick="tentarFecharModalCadastro()">Fechar</button>
        </div>
    </form>
</dialog>

<dialog id="modalCustomizacao">
    <h2 id="nomeProdutoCustomizacao">Personalizar produto</h2>
    
    <div style="text-align: center; margin: 10px 0;">
        <img id="imagemProdutoCustomizacao" src="" alt="Foto do Produto" style="max-width: 120px; height: auto; border-radius: 6px; display: none;">
    </div>

    <div style="margin: 10px 0; padding: 10px; background: #f4f4f4; border-radius: 4px;">
        <p><strong>Especificações / Ingredientes:</strong></p>
        <p id="descricaoProdutoCustomizacao" style="margin: 5px 0 0 0; color: #555;"></p>
    </div>
    
    <hr>
    <h3>Adicionais (Extras)</h3>
    <div id="adicionaisProduto">
        <?php if (empty($extras)): ?>
            <p>Nenhum extra cadastrado.</p>
        <?php else: ?>
            <?php foreach ($extras as $extra): ?>
                <div class="extra-item" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; background: #f9f9f9; padding: 8px; border-radius: 4px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <?php if (!empty($extra['imagem_caminho'])): ?>
                            <img src="<?= htmlspecialchars($extra['imagem_caminho']) ?>" alt="<?= htmlspecialchars($extra['nome']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($extra['nome']) ?></strong><br>
                            <small>R$ <?= number_format($extra['preco_venda'], 2, ',', '.') ?></small>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="mudarQtdExtra(<?= (int)$extra['id'] ?>, '<?= htmlspecialchars($extra['nome'], ENT_QUOTES) ?>', <?= (float)$extra['preco_venda'] ?>, -1)" style="padding: 2px 8px; font-weight: bold;">−</button>
                        <span id="qtd_extra_<?= (int)$extra['id'] ?>" style="min-width: 25px; text-align: center; font-weight: bold;">0</span>
                        <button type="button" onclick="mudarQtdExtra(<?= (int)$extra['id'] ?>, '<?= htmlspecialchars($extra['nome'], ENT_QUOTES) ?>', <?= (float)$extra['preco_venda'] ?>, 1)" style="padding: 2px 8px; font-weight: bold;">+</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <hr>
    <br>
    <button type="button" onclick="confirmarCustomizacao()">Adicionar ao pedido</button>
    <button type="button" onclick="fecharCustomizacao()">Cancelar</button>
</dialog>

<!-- DIALOG DE CONFIRMAÇÃO GERAL SEGURA -->
<dialog id="modalConfirmacaoSegura">
    <h3 id="tituloConfirmacaoSegura">Confirmação</h3>
    <p id="textoConfirmacaoSegura" style="margin: 15px 0;">Tem certeza que deseja realizar esta ação?</p>
    <div>
        <button type="button" id="btnSimConfirmacao" style="color: green; font-weight: bold;">Sim, Continuar</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoSegura').close()">Cancelar</button>
    </div>
</dialog>

<div id="areaRecibo" style="display: none;">
    <h2>PRÉ-CONTA / RECIBO</h2>
    <p><strong>Pedido ID:</strong> <span id="reciboPedidoId"></span></p>
    <p><strong>Cliente:</strong> <span id="reciboCliente"></span></p>
    <p><strong>Tipo:</strong> <span id="reciboTipo"></span></p>
    <hr>
    <div id="reciboItens"></div>
    <hr>
    <h3><span id="reciboTotal"></span></h3>
    <hr>
    <p style="text-align: center;">
        <strong>Avalie sua experiência!</strong><br>
        Código de Avaliação: <strong id="reciboChaveValidacao" style="font-size: 1.2em;"></strong>
    </p>
</div>

<!-- DIALOG COM A MENSAGEM EXATA SOLICITADA -->
<dialog id="ModalFecharConta">
    <h3>Fechar Conta</h3>
    <p><strong>Você deseja realmente fechar essa conta?</strong></p>
    <input type="hidden" id="fechar_id_pedido" value="">
    <div>
        <button type="button" style="color: green; font-weight: bold; padding: 6px 12px; cursor: pointer;" onclick="executarFechamentoConta(true)">Sim</button>
        <button type="button" style="color: red; font-weight: bold; padding: 6px 12px; cursor: pointer;" onclick="executarFechamentoConta(false)">Não</button>
    </div>
</dialog>

<script>
let pedidoAtualData = null;
let produtosPedido = [];
let produtoCustomizando = null;
let snapshotInicialForm = '';
let pedidoFechamentoTemp = null;

function gerarSnapshotForm() {
    return JSON.stringify({
        nome: document.getElementById('nome_cliente').value.trim(),
        mesa: document.getElementById('mesa_id').value,
        tipo: document.getElementById('tipo').value,
        itens: produtosPedido
    });
}

function abrirPedidoParaEditar(pedidoId) {
    fetch(`?obter_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.pedido) {
                pedidoAtualData = data;
                const pedido = data.pedido;
                const itens = data.itens;

                resetarFormulario();
                document.getElementById('form_acao').value = 'editar';
                document.getElementById('form_id_pedido').value = pedido.id;
                document.getElementById('tipo').value = pedido.tipo;
                document.getElementById('nome_cliente').value = pedido.nome_cliente;

                if (pedido.tipo === 'local') {
                    document.getElementById('modalTituloCadastro').innerText = 'Editar Pedido Local - #' + pedido.id;
                    document.getElementById('secao-mesas').style.display = 'block';
                    document.getElementById('mesa_id').value = pedido.mesa_id || '';
                    document.getElementById('mesa_id').required = true;
                } else {
                    document.getElementById('modalTituloCadastro').innerText = 'Editar Pedido Delivery - #' + pedido.id;
                    document.getElementById('secao-mesas').style.display = 'none';
                    document.getElementById('mesa_id').required = false;
                }

                document.getElementById('btnSubmitModal').innerText = 'Salvar Alterações';

                produtosPedido = itens.map(item => {
                    return {
                        id: item.produto_id,
                        nome: item.nome,
                        preco: Number(item.preco_venda),
                        quantidade: Number(item.quantidade),
                        customizacoes: (item.customizacoes || []).map(ext => ({
                            produto_id: ext.produto_id,
                            nome: ext.nome,
                            preco: Number(ext.preco_venda),
                            tipo_acao: ext.tipo_acao,
                            quantidade: Number(ext.quantidade)
                        }))
                    };
                });

                atualizarListaProdutos();
                snapshotInicialForm = gerarSnapshotForm();
                document.getElementById('modalCadastro').showModal();
            }
        })
        .catch(error => console.error('Erro:', error));
}

// Abre o modal de fechar conta diretamente a partir da lista de pedidos
function abrirModalFecharContaDireto(pedidoId) {
    fetch(`?obter_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.pedido) {
                pedidoFechamentoTemp = data;
                document.getElementById('fechar_id_pedido').value = data.pedido.id;
                document.getElementById('ModalFecharConta').showModal();
            } else {
                alert('Erro ao carregar dados do pedido.');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar pedido.');
        });
}

// Executa a escolha do usuário no diálogo "Você deseja realmente fechar essa conta?"
function executarFechamentoConta(confirmado) {
    const modal = document.getElementById('ModalFecharConta');
    
    // Se apertar "Não", não faz nada e apenas fecha o modal
    if (!confirmado) {
        modal.close();
        pedidoFechamentoTemp = null;
        return;
    }

    // Se apertar "Sim", imprime e envia ao caixa
    const data = pedidoFechamentoTemp;
    if (!data || !data.pedido) {
        modal.close();
        return;
    }

    const pedido = data.pedido;
    const itens = data.itens;

    // Preenche e imprime o recibo
    document.getElementById('reciboPedidoId').innerText = pedido.id;
    document.getElementById('reciboCliente').innerText = pedido.nome_cliente;
    document.getElementById('reciboTipo').innerText = pedido.tipo === 'local' ? 'Mesa ' + (pedido.mesa_id || 'N/A') : 'Delivery';
    document.getElementById('reciboChaveValidacao').innerText = pedido.chave_validacao || '---';

    let htmlItens = '<ul>';
    let totalCalculado = 0;

    itens.forEach(function(item) {
        const quantidade = Number(item.quantidade) || 0;
        const preco = Number(item.preco_venda) || 0;
        const customizacoes = item.customizacoes || [];
        const totalExtrasUnitario = customizacoes.reduce(function(total, extra) {
            return total + (Number(extra.preco_venda) * Number(extra.quantidade));
        }, 0);

        const subtotal = (preco + totalExtrasUnitario) * quantidade;
        totalCalculado += subtotal;

        let textoExtras = '';
        if (customizacoes.length > 0) {
            textoExtras = ' <small>(' + customizacoes.map(e => `${e.quantidade}x ${e.nome}`).join(', ') + ')</small>';
        }

        htmlItens += `<li>${quantidade}x ${escapeHtml(item.nome)}${textoExtras} - R$ ${subtotal.toFixed(2).replace('.', ',')}</li>`;
    });
    htmlItens += '</ul>';

    document.getElementById('reciboItens').innerHTML = htmlItens;
    document.getElementById('reciboTotal').innerText = 'Total: R$ ' + totalCalculado.toFixed(2).replace('.', ',');

    document.getElementById('areaRecibo').style.display = 'block';
    window.print();
    document.getElementById('areaRecibo').style.display = 'none';

    // Atualiza status no banco via requisição AJAX
    const formData = new FormData();
    formData.append('acao', 'solicitar_fechamento');
    formData.append('id_pedido', pedido.id);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(resData => {
        modal.close();
        if (resData.sucesso) {
            location.reload();
        } else {
            alert('Erro ao fechar conta: ' + (resData.erro || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        modal.close();
        location.reload();
    });
}

function interceptarEnvioFormulario(event) {
    event.preventDefault();

    const acao = document.getElementById('form_acao').value;
    const snapshotAtual = gerarSnapshotForm();

    if (acao === 'editar' && snapshotAtual === snapshotInicialForm) {
        document.getElementById('modalCadastro').close();
        document.getElementById('formCadastroPedido').submit();
        return;
    }

    if (acao === 'cadastrar' && produtosPedido.length === 0) {
        alert('Adicione pelo menos um produto ao pedido.');
        return;
    }

    const isEdicao = acao === 'editar';
    document.getElementById('tituloConfirmacaoSegura').innerText = isEdicao ? 'Salvar Alterações?' : 'Enviar Pedido?';
    document.getElementById('textoConfirmacaoSegura').innerText = isEdicao 
        ? 'Deseja realmente salvar as alterações feitas neste pedido?' 
        : 'Deseja realmente enviar este novo pedido para a cozinha?';

    const btnSim = document.getElementById('btnSimConfirmacao');
    btnSim.onclick = function() {
        document.getElementById('modalConfirmacaoSegura').close();
        document.getElementById('modalCadastro').close();
        document.getElementById('formCadastroPedido').submit();
    };

    document.getElementById('modalConfirmacaoSegura').showModal();
}

function tentarFecharModalCadastro() {
    const snapshotAtual = gerarSnapshotForm();
    
    if (snapshotAtual !== snapshotInicialForm) {
        document.getElementById('tituloConfirmacaoSegura').innerText = 'Descartar Alterações?';
        document.getElementById('textoConfirmacaoSegura').innerText = 'Existem dados modificados ou adicionados. Se fechar agora, as alterações não salvas serão perdidas.';
        
        const btnSim = document.getElementById('btnSimConfirmacao');
        btnSim.onclick = function() {
            document.getElementById('modalConfirmacaoSegura').close();
            document.getElementById('modalCadastro').close();
        };
        
        document.getElementById('modalConfirmacaoSegura').showModal();
    } else {
        document.getElementById('modalCadastro').close();
    }
}

function resetarFormulario() {
    const form = document.getElementById('modalCadastro').querySelector('form');
    form.reset();
    produtosPedido = [];
    produtoCustomizando = null;
    pedidoAtualData = null;
    snapshotInicialForm = '';
    document.getElementById('listaProdutosPedido').innerHTML = `<div class="pedido-vazio">Nenhum produto adicionado.</div>`;
    document.getElementById('valorTotalTexto').innerText = 'R$ 0,00';
    document.getElementById('valor_total').value = '0.00';
    document.getElementById('itens_pedido').value = '';
    document.getElementById('secao-mesas').style.display = 'none';
    document.getElementById('modalCustomizacao').close();
}

function abrirModalCadastro(tipo = 'local') {
    resetarFormulario();
    document.getElementById('form_acao').value = 'cadastrar';
    document.getElementById('tipo').value = tipo;

    if (tipo === 'delivery') {
        document.getElementById('modalTituloCadastro').innerText = 'Novo Pedido - Delivery';
        document.getElementById('secao-mesas').style.display = 'none';
        document.getElementById('mesa_id').required = false;
    } else {
        document.getElementById('modalTituloCadastro').innerText = 'Novo Pedido - Local';
        document.getElementById('secao-mesas').style.display = 'block';
        document.getElementById('mesa_id').required = true;
    }

    document.getElementById('btnSubmitModal').innerText = 'Enviar Para a Cozinha';
    snapshotInicialForm = gerarSnapshotForm();
    document.getElementById('modalCadastro').showModal();
}

function adicionarProduto(id, nome, preco, descricao, imagem) {
    produtoCustomizando = {
        id: id,
        nome: nome,
        preco: preco,
        quantidade: 1,
        customizacoes: []
    };

    document.getElementById('nomeProdutoCustomizacao').innerText = nome;
    document.getElementById('descricaoProdutoCustomizacao').innerText = descricao || 'Nenhuma especificação cadastrada.';
    
    const imgElement = document.getElementById('imagemProdutoCustomizacao');
    if (imagem && imagem.trim() !== '') {
        imgElement.src = imagem;
        imgElement.style.display = 'inline-block';
    } else {
        imgElement.src = '';
        imgElement.style.display = 'none';
    }

    document.querySelectorAll('[id^="qtd_extra_"]').forEach(el => el.innerText = '0');

    document.getElementById('modalCustomizacao').showModal();
}

function mudarQtdExtra(id, nome, preco, delta) {
    if (!produtoCustomizando) return;

    let existente = produtoCustomizando.customizacoes.find(item => item.produto_id === id);

    if (existente) {
        existente.quantidade += delta;
        if (existente.quantidade <= 0) {
            produtoCustomizando.customizacoes = produtoCustomizando.customizacoes.filter(item => item.produto_id !== id);
            existente = null;
        }
    } else if (delta > 0) {
        existente = {
            produto_id: id,
            nome: nome,
            preco: preco,
            tipo_acao: 'adicionar',
            quantidade: delta
        };
        produtoCustomizando.customizacoes.push(existente);
    }

    const spanQtd = document.getElementById('qtd_extra_' + id);
    if (spanQtd) {
        spanQtd.innerText = existente ? existente.quantidade : 0;
    }
}

function confirmarCustomizacao() {
    if (!produtoCustomizando) return;
    produtosPedido.push(produtoCustomizando);
    atualizarListaProdutos();
    document.getElementById('modalCustomizacao').close();
    produtoCustomizando = null;
}

function fecharCustomizacao() {
    document.getElementById('modalCustomizacao').close();
    produtoCustomizando = null;
}

function atualizarListaProdutos() {
    const lista = document.getElementById('listaProdutosPedido');
    lista.innerHTML = '';
    let total = 0;

    if (produtosPedido.length === 0) {
        lista.innerHTML = `<div class="pedido-vazio">Nenhum produto adicionado.</div>`;
        document.getElementById('valorTotalTexto').innerText = 'R$ 0,00';
        document.getElementById('valor_total').value = '0.00';
        document.getElementById('itens_pedido').value = '';
        return;
    }

    produtosPedido.forEach(function(produto, index) {
        const totalExtras = (produto.customizacoes || []).reduce((total, extra) => {
            return total + (extra.preco * extra.quantidade);
        }, 0);
        const subtotal = (produto.preco + totalExtras) * produto.quantidade;
        total += subtotal;

        const item = document.createElement('div');
        item.className = 'item-pedido';
        
        let htmlCustomizacoes = '';
        if (produto.customizacoes && produto.customizacoes.length > 0) {
            htmlCustomizacoes = '<br><small style="color: #666;">Extras: ' + produto.customizacoes.map(e => `${e.quantidade}x ${e.nome}`).join(', ') + '</small>';
        }

        item.innerHTML = `
            <div><strong>${escapeHtml(produto.nome)}</strong> <span>${produto.quantidade}x</span> ${htmlCustomizacoes}</div>
            <div>R$ ${subtotal.toFixed(2).replace('.', ',')}</div>
            <button type="button" onclick="removerProduto(${index})">Remover</button>
        `;
        lista.appendChild(item);
    });

    document.getElementById('valorTotalTexto').innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
    document.getElementById('valor_total').value = total.toFixed(2);
    document.getElementById('itens_pedido').value = JSON.stringify(produtosPedido);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function removerProduto(index) {
    produtosPedido.splice(index, 1);
    atualizarListaProdutos();
}

function filtrarProdutos(categoria, botao) {
    const produtos = document.querySelectorAll('.card-produto');
    produtos.forEach(function(produto) {
        const categoriaProduto = produto.dataset.categoria.trim();
        if (categoria === 'todos' || categoriaProduto.toLowerCase() === categoria.toLowerCase()) {
            produto.style.display = '';
        } else {
            produto.style.display = 'none';
        }
    });

    document.querySelectorAll('.btn-filtro-cat').forEach(function(btn) {
        btn.classList.remove('active', 'ativo');
    });
    botao.classList.add('active', 'ativo');
}
</script>
</body>
</html>