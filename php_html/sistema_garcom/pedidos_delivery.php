<?php
session_start();

require_once __DIR__ . '/../../php/conexao.php';
require_once __DIR__ . '/../sistema_web/verificar_login.php';

$funcionario_id = $_SESSION['funcionario_id'] ?? null;

if (isset($_GET['obter_pedido'])) {
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $id_ed = $_GET['obter_pedido'];
    
    $st = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $st->execute([$id_ed]);
    $pedido_data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$pedido_data) {
        echo json_encode(['erro' => 'Pedido não encontrado.']);
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
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'aguardando_pagamento' WHERE id = ?");
            $stmt->execute([$id_pedido]);
            echo json_encode(['sucesso' => true]);
        } catch (PDOException $e) {
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    } elseif ($acao === 'cancelar_pedido') {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        $quantidadesRetorno = $_POST['qtd_retorno'] ?? [];

        if (!$id_pedido || empty($motivo)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o motivo do cancelamento.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmtItens = $pdo->prepare("SELECT id, produto_id, quantidade FROM itens_pedidos WHERE pedido_id = ?");
            $stmtItens->execute([$id_pedido]);
            $itensPedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            $stmtPedido = $pdo->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id = ?");
            $stmtPedido->execute([$id_pedido]);

            $stmtItensCancel = $pdo->prepare("UPDATE itens_pedidos SET status_item = 'cancelado' WHERE pedido_id = ?");
            $stmtItensCancel->execute([$id_pedido]);

            $stmtOcorrencia = $pdo->prepare("
                INSERT INTO ocorrencias_pedido (pedido_id, item_pedido_id, funcionario_id, tipo, motivo, retornou_estoque, quantidade_retornada)
                VALUES (?, ?, ?, 'cancelamento', ?, ?, ?)
            ");

            foreach ($itensPedido as $item) {
                $itemId = $item['id'];
                $qtdMaxima = (int)$item['quantidade'];
                
                $qtdRetorno = isset($quantidadesRetorno[$itemId]) ? (int)$quantidadesRetorno[$itemId] : 0;
                if ($qtdRetorno < 0) $qtdRetorno = 0;
                if ($qtdRetorno > $qtdMaxima) $qtdRetorno = $qtdMaxima;

                $retornouEstoqueFlag = ($qtdRetorno > 0) ? 1 : 0;

                $stmtOcorrencia->execute([$id_pedido, $itemId, $funcionario_id, $motivo, $retornouEstoqueFlag, $qtdRetorno]);

                if ($qtdRetorno > 0) {
                    $stmtProd = $pdo->prepare("SELECT estoque_id FROM produtos WHERE id = ?");
                    $stmtProd->execute([$item['produto_id']]);
                    $prodData = $stmtProd->fetch(PDO::FETCH_ASSOC);

                    if ($prodData && $prodData['estoque_id']) {
                        $stmtLote = $pdo->prepare("SELECT id FROM lotes_estoque WHERE estoque_id = ? ORDER BY data_validade ASC LIMIT 1");
                        $stmtLote->execute([$prodData['estoque_id']]);
                        $loteId = $stmtLote->fetchColumn();

                        if ($loteId) {
                            $stmtUpLote = $pdo->prepare("UPDATE lotes_estoque SET quantidade_estoque = quantidade_estoque + ? WHERE id = ?");
                            $stmtUpLote->execute([$qtdRetorno, $loteId]);

                            $stmtMov = $pdo->prepare("
                                INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao)
                                VALUES (?, ?, 'entrada', ?, ?)
                            ");
                            $stmtMov->execute([$loteId, $funcionario_id, $qtdRetorno, "Retorno parcial/total ao estoque por cancelamento (Item ID #$itemId, Pedido #$id_pedido)"]);
                        }
                    } else {
                        $stmtReceita = $pdo->prepare("SELECT ingrediente_id, quantidade_usada FROM receitas WHERE produto_id = ?");
                        $stmtReceita->execute([$item['produto_id']]);
                        $receitas = $stmtReceita->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($receitas as $rec) {
                            $qtdRetornoIngrediente = $rec['quantidade_usada'] * $qtdRetorno;
                            $stmtLoteIng = $pdo->prepare("SELECT id FROM lotes_estoque WHERE estoque_id = ? ORDER BY data_validade ASC LIMIT 1");
                            $stmtLoteIng->execute([$rec['ingrediente_id']]);
                            $loteIngId = $stmtLoteIng->fetchColumn();

                            if ($loteIngId) {
                                $stmtUpLoteIng = $pdo->prepare("UPDATE lotes_estoque SET quantidade_estoque = quantidade_estoque + ? WHERE id = ?");
                                $stmtUpLoteIng->execute([$qtdRetornoIngrediente, $loteIngId]);

                                $stmtMovIng = $pdo->prepare("
                                    INSERT INTO movimentacoes_estoque (lote_estoque_id, funcionario_id, tipo_movimentacao, quantidade, observacao)
                                    VALUES (?, ?, 'entrada', ?, ?)
                                ");
                                $stmtMovIng->execute([$loteIngId, $funcionario_id, $qtdRetornoIngrediente, "Retorno de ingrediente por cancelamento (Pedido #$id_pedido)"]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['sucesso' => true]);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    } elseif ($acao === 'editar') {
        $id_pedido = (int)($_POST['id_pedido'] ?? 0);
        $nome_cliente = trim($_POST['nome_cliente'] ?? '');
        $tipo = 'delivery';
        $mesa_id = null;
        $valor_total = (float)($_POST['valor_total'] ?? 0);
        $itens_pedido = json_decode($_POST['itens_pedido'] ?? '[]', true);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET nome_cliente = ?, tipo = ?, mesa_id = ?, valor_total = ?
                WHERE id = ? AND status = 'aberto'
            ");
            $stmt->execute([$nome_cliente, $tipo, $mesa_id, $valor_total, $id_pedido]);

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
        $tipo = 'delivery';
        $mesa_id = null;
        $valor_total = (float)($_POST['valor_total'] ?? 0);
        
        $chave_validacao = strtoupper(bin2hex(random_bytes(4))); 
        $itens_pedido = json_decode($_POST['itens_pedido'] ?? '[]', true);

        try {
            $pdo->beginTransaction();

            $stmtPedido = $pdo->prepare("
                INSERT INTO pedidos (mesa_id, atendente_id, nome_cliente, tipo, status, valor_total, chave_validacao)
                VALUES (?, ?, ?, ?, 'aberto', ?, ?)
            ");
            $stmtPedido->execute([$mesa_id, $funcionario_id, $nome_cliente, $tipo, $valor_total, $chave_validacao]);
            $pedido_id = $pdo->lastInsertId();

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

$deliveryMeus = [];
$deliveryTodos = [];
$produtosAtivos = [];

try {
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

    $stmtDeliveryMeus = $pdo->prepare("SELECT * FROM pedidos WHERE tipo = 'delivery' AND atendente_id = ? AND status = 'aberto' ORDER BY id DESC");
    $stmtDeliveryMeus->execute([$funcionario_id]);
    $deliveryMeus = $stmtDeliveryMeus->fetchAll(PDO::FETCH_ASSOC);

    $stmtDeliveryTodos = $pdo->query("SELECT * FROM pedidos WHERE tipo = 'delivery' AND status = 'aberto' ORDER BY id DESC");
    $deliveryTodos = $stmtDeliveryTodos->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Delivery - Atendente</title>
    <style>
        @media print {
            body * { visibility: hidden; }
            #areaRecibo, #areaRecibo * { visibility: visible; }
            #areaRecibo { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; }
        }
        .aba-ativa {
            background-color: #007bff !important;
            color: white !important;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div>
    <div>
        <h2>Delivery</h2>
        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
            <div style="display: flex; gap: 4px;">
                <button type="button" id="btnAbaDeliveryMeus" class="aba-ativa" onclick="alternarFiltroDelivery('meus')" style="padding: 6px 12px; cursor: pointer; border: 1px solid #ccc; background: #f8f9fa;">Meus</button>
                <button type="button" id="btnAbaDeliveryTodos" onclick="alternarFiltroDelivery('todos')" style="padding: 6px 12px; cursor: pointer; border: 1px solid #ccc; background: #f8f9fa;">Todos</button>
            </div>
        </div>

        <div id="listaDeliveryMeus">
            <div style="margin-bottom: 10px;">
                <button type="button" onclick="abrirModalCadastro('delivery')">+ Novo Pedido Delivery</button>
            </div>
            <?php if (empty($deliveryMeus)): ?>
                <p>Nenhum pedido de delivery seu aberto.</p>
            <?php else: ?>
                <?php foreach ($deliveryMeus as $pedido): ?>
                    <div style="display: flex; gap: 5px; margin-top: 5px; align-items: center;">
                        <button type="button" onclick="abrirPedidoParaEditar(<?= $pedido['id'] ?>)" style="flex: 1; text-align: left; padding: 8px; cursor: pointer;">
                            <strong>Delivery</strong> — Pedido #<?= $pedido['id'] ?> (<?= htmlspecialchars($pedido['nome_cliente']) ?>)
                        </button>
                        <button type="button" onclick="abrirModalFecharContaDireto(<?= $pedido['id'] ?>)" style="padding: 8px; background: #e6f2ff; cursor: pointer; font-weight: bold;" title="Fechar Conta / Imprimir Pré-conta">🖨️ Fechar Conta</button>
                        <button type="button" onclick="abrirModalCancelarPedido(<?= $pedido['id'] ?>)" style="padding: 8px; background: #ffe6e6; cursor: pointer; font-weight: bold;" title="Cancelar / Relatar Ocorrência">❌ Cancelar</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="listaDeliveryTodos" style="display: none;">
            <?php if (empty($deliveryTodos)): ?>
                <p>Nenhum pedido de delivery aberto no sistema.</p>
            <?php else: ?>
                <?php foreach ($deliveryTodos as $pedido): ?>
                    <div style="display: flex; gap: 5px; margin-top: 5px; align-items: center;">
                        <button type="button" onclick="abrirPedidoParaEditar(<?= $pedido['id'] ?>)" style="flex: 1; text-align: left; padding: 8px; cursor: pointer;">
                            <strong>Delivery</strong> — Pedido #<?= $pedido['id'] ?> (<?= htmlspecialchars($pedido['nome_cliente']) ?>)
                        </button>
                        <button type="button" onclick="abrirModalFecharContaDireto(<?= $pedido['id'] ?>)" style="padding: 8px; background: #e6f2ff; cursor: pointer; font-weight: bold;" title="Fechar Conta / Imprimir Pré-conta">🖨️ Fechar Conta</button>
                        <button type="button" onclick="abrirModalCancelarPedido(<?= $pedido['id'] ?>)" style="padding: 8px; background: #ffe6e6; cursor: pointer; font-weight: bold;" title="Cancelar / Relatar Ocorrência">❌ Cancelar</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
    
<dialog id="modalCadastro">
    <h1 id="modalTituloCadastro">Fazer Novo Pedido Delivery</h1>

    <form id="formCadastroPedido" method="POST" action="" onsubmit="interceptarEnvioFormulario(event)">
        <input type="hidden" id="form_acao" name="acao" value="cadastrar">
        <input type="hidden" id="form_id_pedido" name="id_pedido" value="">
        <input type="hidden" id="tipo" name="tipo" value="delivery">

        <div>
            <label for="nome_cliente">Nome do Cliente</label>
            <input type="text" id="nome_cliente" name="nome_cliente" required>
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

    <div style="margin: 15px 0; padding: 12px; background: #eef2f7; border-radius: 6px; text-align: center;">
        <label style="font-weight: bold; display: block; margin-bottom: 8px;">Quantidade do Item:</label>
        <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
            <button type="button" onclick="mudarQtdProdutoCustomizacao(-1)" style="padding: 6px 16px; font-weight: bold; font-size: 1.1em; cursor: pointer; background: #ddd; border: none; border-radius: 4px;">−</button>
            <span id="qtdProdutoCustomizacao" style="min-width: 35px; text-align: center; font-weight: bold; font-size: 1.2em;">1</span>
            <button type="button" onclick="mudarQtdProdutoCustomizacao(1)" style="padding: 6px 16px; font-weight: bold; font-size: 1.1em; cursor: pointer; background: #ddd; border: none; border-radius: 4px;">+</button>
        </div>
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

<dialog id="modalConfirmacaoSegura">
    <h3 id="tituloConfirmacaoSegura">Confirmação</h3>
    <p id="textoConfirmacaoSegura" style="margin: 15px 0;">Tem certeza que deseja realizar esta ação?</p>
    <div>
        <button type="button" id="btnSimConfirmacao" style="color: green; font-weight: bold;">Sim, Continuar</button>
        <button type="button" onclick="document.getElementById('modalConfirmacaoSegura').close()">Cancelar</button>
    </div>
</dialog>

<dialog id="modalCancelarPedido">
    <h3>Cancelar Pedido / Relatar Ocorrência</h3>
    <form id="formCancelarPedido" onsubmit="enviarCancelamentoPedido(event)">
        <input type="hidden" id="cancelar_id_pedido" name="id_pedido" value="">
        
        <div style="margin: 10px 0;">
            <label style="display: block; font-weight: bold; margin-bottom: 5px;">Itens do Pedido (Quantidade Retornada ao Estoque):</label>
            <div id="listaItensCancelamento" style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 150px; overflow-y: auto; margin-bottom: 10px;">
                <!-- Itens carregados dinamicamente -->
            </div>
        </div>

        <div style="margin: 10px 0;">
            <label for="motivoCancelamento" style="display: block; font-weight: bold; margin-bottom: 5px;">Motivo da Ocorrência:</label>
            <textarea id="motivoCancelamento" name="motivo" rows="3" style="width: 100%; padding: 8px;" required placeholder="Descreva o motivo do cancelamento..."></textarea>
        </div>

        <div style="margin-top: 15px;">
            <button type="submit" style="color: red; font-weight: bold; padding: 6px 12px; cursor: pointer;">Confirmar Cancelamento</button>
            <button type="button" onclick="document.getElementById('modalCancelarPedido').close()" style="padding: 6px 12px; cursor: pointer;">Fechar</button>
        </div>
    </form>
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

function alternarFiltroDelivery(tipo) {
    const divMeus = document.getElementById('listaDeliveryMeus');
    const divTodos = document.getElementById('listaDeliveryTodos');
    const btnMeus = document.getElementById('btnAbaDeliveryMeus');
    const btnTodos = document.getElementById('btnAbaDeliveryTodos');

    if (tipo === 'meus') {
        divMeus.style.display = 'block';
        divTodos.style.display = 'none';
        btnMeus.classList.add('aba-ativa');
        btnTodos.classList.remove('aba-ativa');
    } else {
        divMeus.style.display = 'none';
        divTodos.style.display = 'block';
        btnTodos.classList.add('aba-ativa');
        btnMeus.classList.remove('aba-ativa');
    }
}

function gerarSnapshotForm() {
    return JSON.stringify({
        nome: document.getElementById('nome_cliente').value.trim(),
        tipo: 'delivery',
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
                document.getElementById('nome_cliente').value = pedido.nome_cliente;
                document.getElementById('modalTituloCadastro').innerText = 'Editar Pedido Delivery - #' + pedido.id;

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

function abrirModalCancelarPedido(pedidoId) {
    document.getElementById('cancelar_id_pedido').value = pedidoId;
    document.getElementById('motivoCancelamento').value = '';
    
    const containerItens = document.getElementById('listaItensCancelamento');
    containerItens.innerHTML = 'Carregando itens...';

    fetch(`?obter_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.itens && data.itens.length > 0) {
                let html = '';
                data.itens.forEach((item) => {
                    const qtdMax = Number(item.quantidade);
                    html += `
                        <div style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px dashed #ddd; padding-bottom: 6px;">
                            <div>
                                <strong>${qtdMax}x</strong> ${escapeHtml(item.nome)}
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <label style="font-size: 0.85em; color: #555;">Qtd p/ Estoque:</label>
                                <input type="number" name="qtd_retorno[${item.id}]" value="${qtdMax}" min="0" max="${qtdMax}" style="width: 55px; padding: 4px; text-align: center;" title="Máximo: ${qtdMax}">
                                <span style="font-size: 0.85em; color: #777;">/ ${qtdMax}</span>
                            </div>
                        </div>
                    `;
                });
                containerItens.innerHTML = html;
            } else {
                containerItens.innerHTML = 'Nenhum item encontrado para este pedido.';
            }
        })
        .catch(err => {
            console.error(err);
            containerItens.innerHTML = 'Erro ao carregar itens.';
        });

    document.getElementById('modalCancelarPedido').showModal();
}

function enviarCancelamentoPedido(event) {
    event.preventDefault();
    const pedidoId = document.getElementById('cancelar_id_pedido').value;
    const motivo = document.getElementById('motivoCancelamento').value.trim();

    if (!motivo) {
        alert('Informe o motivo.');
        return;
    }

    const form = document.getElementById('formCancelarPedido');
    const formData = new FormData(form);
    formData.append('acao', 'cancelar_pedido');
    formData.append('id_pedido', pedidoId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(resData => {
        document.getElementById('modalCancelarPedido').close();
        if (resData.sucesso) {
            alert('Pedido cancelado e ocorrência registrada com sucesso.');
            location.reload();
        } else {
            alert('Erro ao cancelar pedido: ' + (resData.erro || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('modalCancelarPedido').close();
        alert('Erro ao processar solicitação.');
    });
}

function executarFechamentoConta(confirmado) {
    const modal = document.getElementById('ModalFecharConta');
    
    if (!confirmado) {
        modal.close();
        pedidoFechamentoTemp = null;
        return;
    }

    const data = pedidoFechamentoTemp;
    if (!data || !data.pedido) {
        modal.close();
        return;
    }

    const pedido = data.pedido;
    const itens = data.itens;

    document.getElementById('reciboPedidoId').innerText = pedido.id;
    document.getElementById('reciboCliente').innerText = pedido.nome_cliente;
    document.getElementById('reciboTipo').innerText = 'Delivery';
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
    document.getElementById('modalCustomizacao').close();
}

function abrirModalCadastro() {
    resetarFormulario();
    document.getElementById('form_acao').value = 'cadastrar';
    document.getElementById('modalTituloCadastro').innerText = 'Novo Pedido - Delivery';
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
    document.getElementById('qtdProdutoCustomizacao').innerText = '1';
    
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

function mudarQtdProdutoCustomizacao(delta) {
    if (!produtoCustomizando) return;
    produtoCustomizando.quantidade += delta;
    if (produtoCustomizando.quantidade < 1) {
        produtoCustomizando.quantidade = 1;
    }
    document.getElementById('qtdProdutoCustomizacao').innerText = produtoCustomizando.quantidade;
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