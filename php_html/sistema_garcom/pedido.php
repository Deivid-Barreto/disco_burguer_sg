<?php

session_start();

require_once __DIR__ . '/../../php/conexao.php';
require_once __DIR__ . '../../sistema_web/verificar_login.php';

if (isset($_GET['obter_pedido'])) {
    require_once '../../php/conexao.php'; 
    while (ob_get_level()) ob_end_clean(); 
    header('Content-Type: application/json; charset=utf-8');
    
    $id_ed = $_GET['obter_pedido'];
    
    $st = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $st->execute([$id_ed]);
    $pedido_data = $st->fetch(PDO::FETCH_ASSOC);

    $stmtItens = $pdo->prepare("
        SELECT
            ip.id,
            ip.quantidade,
            ip.status_item,
            p.id AS produto_id,
            p.nome,
            p.preco_venda
        FROM itens_pedidos ip
        INNER JOIN produtos p
            ON p.id = ip.produto_id
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

if (isset($_GET['obter_pedido_mesa'])) {

    $mesaId = $_GET['obter_pedido_mesa'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM pedidos
        WHERE mesa_id = ?
        AND status NOT IN ('finalizado', 'cancelado')
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->execute([$mesaId]);

    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        $stmtItens = $pdo->prepare("
            SELECT
                ip.id,
                ip.quantidade,
                ip.status_item,
                p.id AS produto_id,
                p.nome,
                p.preco_venda
            FROM itens_pedidos ip
            INNER JOIN produtos p
                ON p.id = ip.produto_id
            WHERE ip.pedido_id = ?
        ");

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

        $stmtItens->execute([$pedido['id']]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'encontrado' => true,
            'pedido' => $pedido,
            'itens' => $itens
        ]);
    } else {
        echo json_encode([
            'encontrado' => false
        ]);
    }

    exit;
}

$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'deletar') {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
            $stmtDel->execute([$_POST['id_pedido']]);
        } catch (PDOException $e) {}
        header("Location: " . $_SERVER['PHP_SELF']);
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
            SET nome_cliente = ?,
                tipo = ?,
                mesa_id = ?,
                valor_total = ?
            WHERE id = ?
            ");

            $stmt->execute([
            $nome_cliente,
            $tipo,
            $mesa_id,
            $valor_total,
            $id_pedido
            ]);

            
            $stmt = $pdo->prepare("
               DELETE cip
               FROM customizacoes_item_pedido cip
               INNER JOIN itens_pedidos ip
               ON ip.id = cip.item_pedido_id
               WHERE ip.pedido_id = ?
            ");

            $stmt->execute([$id_pedido]);

            $stmt = $pdo->prepare("
                DELETE FROM itens_pedidos WHERE pedido_id = ? 
            ");

            $stmt->execute([$id_pedido]);

            $stmt->execute([$id_pedido]);

            $stmtItem = $pdo->prepare("
            INSERT INTO itens_pedidos
            (
                pedido_id,
                produto_id,
                quantidade,
                status_item
            )
            VALUES (?, ?, ?, 'pendente')
            ");

            
            $stmtCustomizacao = $pdo->prepare("
                INSERT INTO customizacoes_item_pedido
                (
                item_pedido_id,
                produto_id,
                tipo_acao,
                quantidade
                )
                VALUES (?, ?, ?, ?)
            ");

            foreach ($itens_pedido as $item) {

                $stmtItem->execute([
                $id_pedido,
                (int)$item['id'],
                (int)$item['quantidade']
                ]);

                $item_pedido_id = $pdo->lastInsertId();

                foreach (($item['customizacoes'] ?? []) as $extra) {

                    $stmtCustomizacao->execute([
                    $item_pedido_id,
                    (int)$extra['produto_id'],
                    $extra['tipo_acao'] ?? 'adicionar',
                    (int)$extra['quantidade']
                    ]);
                }
            }

            $pdo->commit();

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
            $pdo->rollBack();
            }

            die("Erro ao editar pedido: " . $e->getMessage());
        }    
        
    } elseif ($acao === 'cadastrar') {

        $atendente_id = 1;

        $nome_cliente = trim($_POST['nome_cliente'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $mesa_id = !empty($_POST['mesa_id']) ? (int)$_POST['mesa_id'] : null;
        $valor_total = (float)($_POST['valor_total'] ?? 0);
        $chave_validacao = $_POST['chave_validacao'] ?? '';
        $itens_pedido = json_decode($_POST['itens_pedido'] ?? '[]', true);

        try {

            $pdo->beginTransaction();

            $pedido_id = null;

            if ($tipo === 'local' && $mesa_id) {

                $stmtBusca = $pdo->prepare("
                SELECT id
                FROM pedidos
                WHERE mesa_id = ?
                AND status NOT IN ('finalizado', 'cancelado')
                LIMIT 1
                ");

                $stmtBusca->execute([$mesa_id]);

                $pedidoExistente = $stmtBusca->fetch(PDO::FETCH_ASSOC);

                if ($pedidoExistente) {
                    $pedido_id = $pedidoExistente['id'];

                    $stmtUpdate = $pdo->prepare("
                    UPDATE pedidos
                    SET valor_total = valor_total + ?, nome_cliente = ?
                    WHERE id = ?
                    ");

                    $stmtUpdate->execute([
                    $valor_total,
                    $nome_cliente,
                    $pedido_id
                    ]);
                }
            }

            if (!$pedido_id) {

                $stmtPedido = $pdo->prepare("
                INSERT INTO pedidos
                (
                 mesa_id,
                 atendente_id,
                 nome_cliente,
                 tipo,
                 status,
                 valor_total,
                 chave_validacao
                )
                VALUES
                (
                 ?,
                 ?,
                 ?,
                 ?,
                 'aberto',
                 ?,
                 ?
                )
                ");

                $stmtPedido->execute([
                $mesa_id,
                $atendente_id,
                $nome_cliente,
                $tipo,
                $valor_total,
                $chave_validacao
                ]);

                $pedido_id = $pdo->lastInsertId();
            }

            $stmtItem = $pdo->prepare("
            INSERT INTO itens_pedidos
            (
                pedido_id,
                produto_id,
                quantidade,
                status_item
            )
            VALUES (?, ?, ?, 'pendente')
            ");

            $stmtCustomizacao = $pdo->prepare("
            INSERT INTO customizacoes_item_pedido
            (
                item_pedido_id,
                produto_id,
                tipo_acao,
                quantidade
            )
            VALUES (?, ?, ?, ?)
            ");

            foreach ($itens_pedido as $item) {
                $stmtItem->execute([
                $pedido_id,
                (int)$item['id'],
                (int)$item['quantidade']
                ]);

                $item_pedido_id = $pdo->lastInsertId();

                foreach (($item['customizacoes'] ?? []) as $extra) {

                    $stmtCustomizacao->execute([
                    $item_pedido_id,
                    (int)$extra['produto_id'],
                    $extra['tipo_acao'] ?? 'adicionar',
                    (int)$extra['quantidade']
                    ]);
                }
            }

            $pdo->commit();

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            die("Erro ao cadastrar pedido: " . $e->getMessage());
        }
    }

}
   

$mesas = [];
$delivery = [];
$pedidos = [];
$produtosAtivos = [];

try {
    $stmtMesas = $pdo->query("
    SELECT
        m.id,
        m.numero,
        p.nome_cliente
    FROM mesas m
    LEFT JOIN pedidos p
        ON p.mesa_id = m.id
        AND p.status NOT IN ('finalizado', 'cancelado')
    ORDER BY m.id ASC
    ");

$mesas = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);

    $stmtProdutos = $pdo->query("SELECT id, categorias, nome, descricao, preco_venda, imagem_caminho FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
    $produtosAtivos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

    $stmtDelivery = $pdo->query("SELECT * FROM pedidos WHERE tipo = 'delivery' AND status NOT IN ('finalizado', 'cancelado')ORDER BY id DESC");
    $delivery = $stmtDelivery->fetchAll(PDO::FETCH_ASSOC);

    $stmtPedidos = $pdo->query("Select * FROM pedidos ORDER BY id DESC");
    $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {}

$extras = [];

try {
    $stmtExtras = $pdo->prepare("
        SELECT
            id,
            nome,
            descricao,
            preco_venda,
            imagem_caminho
        FROM produtos
        WHERE ativo = 1
          AND LOWER(categorias) = 'extras'
        ORDER BY nome ASC
    ");

    $stmtExtras->execute();
    $extras = $stmtExtras->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $extras = [];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
</head>
<body>

<div>
    <div>
        <h2>Mesas</h2>
        <?php foreach($mesas as$p): ?>
            <button onclick="abrirMesas(<?= $p['id'] ?>)">
                Mesa <?= htmlspecialchars($p['numero']) ?>
                <p><?php if (!empty($p['nome_cliente'])): ?>
                    <span>
                <?= htmlspecialchars($p['nome_cliente']) ?>
                    </span>
                <?php endif; ?>
                </p>
            </button>
        <?php endforeach; ?>
    </div>
    <div>
        <h2>Delivery</h2>
        <?php if (empty($delivery)): ?>
            <p>Nenhum pedido de delivery.</p>
        <?php else: ?>
        <?php foreach ($delivery as $pedido): ?>
            <button type="button" onclick="abrirDelivery(<?= $pedido['id'] ?>)">
                Pedido <?= $pedido['id'] ?> - <?= htmlspecialchars($pedido['nome_cliente']) ?>
            </button>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<button onclick="abrirModalCadastro()">Fazer Novo Pedido</button>
    
<dialog id="modalCadastro">
    <h1 id="modalTituloCadastro">Fazer Novo Pedido</h1>

    <form method="POST" action="">

        <input type="hidden" id="form_acao" name="acao" value="cadastrar">
        <input type="hidden" id="form_id_pedido" name="id_pedido" value="">

        <div>
            <label for="nome">Nome do Cliente</label>
            <input type="text" id="nome_cliente" name="nome_cliente" required>
        </div>

        <div>
            <label for="tipo">Tipo de Pedido</label>
            <select id="tipo" name="tipo" onchange="atualizarFormulario()" required>
                <option value="" hidden></option>
                <option value="delivery">Delivery</option>
                <option value="local">Local</option>
            </select>
        </div>

        <div id="secao-mesas" style="display: none;">
            <label for="mesas">Mesa</label>
            <select id="mesa_id" name="mesa_id">
                <option value="" hidden>Selecione uma mesa</option>
                <?php foreach ($mesas as $mesa): ?>
                <option value="<?= $mesa['id'] ?>">Mesa <?= htmlspecialchars($mesa['numero']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <section class="area-cardapio">
                <div class="categorias-cardapio">
                    <button type="button" class="categoria-btn ativo" onclick="filtrarProdutos('todos', this)">
                        Todos
                    </button>

                    <?php

                    $categorias = [];

                    foreach ($produtosAtivos as $produto) {
                        if (
                            !empty($produto['categorias']) &&
                            !in_array($produto['categorias'], $categorias)
                        ) {
                            $categorias[] = $produto['categorias'];
                        }
                    }
                    foreach ($categorias as $categoria):
                    ?>
                        <button type="button" class="categoria-btn" onclick="filtrarProdutos('<?= htmlspecialchars($categoria, ENT_QUOTES) ?>', this)">
                            <?= htmlspecialchars($categoria) ?>
                        </button>
                    <?php endforeach; 
                    ?>
                </div>

                <div class="grade-produtos">
                    <?php foreach ($produtosAtivos as $produto): ?>
                        <button type="button" class="card-produto" 
                            data-categoria="<?= htmlspecialchars($produto['categorias'] ?? '') ?>"
                            onclick="adicionarProduto(<?= $produto['id'] ?>,
                                '<?= htmlspecialchars($produto['nome'], ENT_QUOTES) ?>',
                                <?= (float)$produto['preco_venda'] ?>)">
                            <div class="produto-imagem">
                                <?php if (!empty($produto['imagem_caminho'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($produto['imagem_caminho']) ?>"
                                        alt="<?= htmlspecialchars($produto['nome']) ?>"
                                    >
                                <?php endif; ?>
                            </div>
                            <p><?= htmlspecialchars($produto['nome']) ?></p>
                            <p>R$ <?= number_format($produto['preco_venda'], 2, ',', '.') ?></p>
                            <p><?= htmlspecialchars($produto['descricao']) ?></p> 
                        </button>

                    <?php endforeach; ?>
                </div>
            </section>
        <section class="resumo-pedido">

                <h2>Itens do Pedido</h2>
                <div id="listaProdutosPedido" >
                    <div class="pedido-vazio">
                        Nenhum produto adicionado.
                    </div>
                </div>
                <div class="total-pedido">
                    <span>Total</span>
                    <strong id="valorTotalTexto">
                        R$ 0,00
                    </strong>
                </div>
                <input type="hidden" name="valor_total" id="valor_total" value="0.00">
                <input type="hidden" name="itens_pedido" id="itens_pedido">
            </section>

        <div>
            <label for="chave_validacao">Chave de Validação</label>
            <input type="text" id="chave_validacao" name="chave_validacao" readonly>
        </div>

        <button type="submit" class="btn-enviar-pedido" id="btnSubmitModal">Enviar Para a Cozinha</button>
        <button type="button" onclick="document.getElementById('modalCadastro').close()">Fechar</button>

    </form>
</dialog>

<dialog id="modalCustomizacao">
    <h2 id="nomeProdutoCustomizacao">Personalizar produto</h2>
    <p>Escolha os ingredientes que deseja adicionar.</p>
    <hr>
    <h3>Adicionar</h3>
    <div id="adicionaisProduto">
        <?php if (empty($extras)): ?>
            <p>Nenhum extra cadastrado.</p>
        <?php else: ?>
        <?php foreach ($extras as $extra): ?>
            <div class="extra-item">
                <span>
                    <?= htmlspecialchars($extra['nome']) ?>
                    — R$ <?= number_format(
                        $extra['preco_venda'],
                        2,
                        ',',
                        '.'
                    ) ?>
                </span>
                <button
                    type="button"
                    onclick="alterarExtra(
                        <?= (int)$extra['id'] ?>,
                        '<?= htmlspecialchars($extra['nome'], ENT_QUOTES) ?>',
                        <?= (float)$extra['preco_venda'] ?>
                    )">
                    Adicionar
                </button>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <hr>
    <h3>Personalizações escolhidas</h3>
    <div id="customizacoesEscolhidas">
        <p>Nenhuma personalização.</p>
    </div>
    <br>
    <button type="button" onclick="confirmarCustomizacao()">Adicionar ao pedido</button>
    <button type="button" onclick="fecharCustomizacao()">Cancelar</button>
</dialog>

<dialog id="modalPedido">
    <h3 id="tituloModalPedido">Ações do Pedido</h3>
    <div>
        <p>
            <strong>Nome do Cliente:</strong>
            <span id="pedidoNomeCliente"></span>
        </p>
        <p>
            <strong>Tipo:</strong>
            <span id="pedidoTipo"></span>
        </p>
        <p>
            <strong>Status:</strong>
            <span id="pedidoStatus"></span>
        </p>
    
    </div>
    <h4>Itens do Pedido</h4>
    <div id="listaItensPedido" style="margin-bottom: 15px; background: #f4f4f4; padding: 10px; border-radius: 4px;">
    </div>
        <button type="button" id="btnEditarAcoes" onclick="carregarDadosEdicao()">Editar</button>
        <button type="button" style="color: red;" onclick="ModalFecharConta()">Fechar Conta</button>
        <button type="button" onclick="document.getElementById('modalPedido').close()">Cancelar</button>
    </div>
</dialog>

<dialog id="ModalFecharConta">
    <h3>Fechar Conta</h3>
    <p>Tem certeza que deseja fechar esta conta? Esta ação não poderá ser desfeita.</p>
    
    <form method="POST" action="">
        <input type="hidden" name="acao" value="deletar">
        <input type="hidden" name="id_pedido" id="deletar_id_pedido" value="">
        
        <div>
            <button type="submit" style="color: red;" onclick="">Sim, Excluir</button>
            <button type="button" onclick="document.getElementById('ModalFecharConta').close()">Cancelar</button>
        </div>
    </form>
</dialog>

<script>
let pedidoAtualData = null;
let produtosPedido = [];
let produtoCustomizando = null;

function abrirAcoesPedido() {
    const pedido = pedidoAtualData.pedido;
    const itens = pedidoAtualData.itens;

    document.getElementById('tituloModalPedido').innerText =
        pedido.tipo === 'local'
            ? 'Mesa ' + pedido.mesa_id
            : 'Pedido #' + pedido.id;

    document.getElementById('pedidoNomeCliente').innerText = pedido.nome_cliente;
    document.getElementById('pedidoStatus').innerText = pedido.status;
    document.getElementById('pedidoTipo').innerText = pedido.tipo === 'local' ? 'Local' : 'Delivery';

    const lista = document.getElementById('listaItensPedido');
    lista.innerHTML = '';
    let totalCalculado = 0;

    if (itens.length === 0) {
        lista.innerHTML = '<p>Nenhum item no pedido.</p>';
    } else {
        itens.forEach(function(item) {
            const quantidade = Number(item.quantidade) || 0;
            const preco = Number(item.preco_venda) || 0;
            const customizacoes = item.customizacoes || [];
            const totalExtrasUnitario = customizacoes.reduce(
                function(total, extra) {
                    const precoExtra = Number(extra.preco_venda) || 0;
                    const qtdExtra = Number(extra.quantidade) || 0;

                    if (extra.tipo_acao === 'adicionar') {
                        return total + precoExtra * qtdExtra;
                    }
                    return total;
                },
                0
            );

            const precoUnitario = preco + totalExtrasUnitario;
            const subtotal = precoUnitario * quantidade;
            totalCalculado += subtotal;
            const linha = document.createElement('div');
            linha.style.marginBottom = '12px';
            linha.style.paddingBottom = '10px';
            linha.style.borderBottom = '1px solid #ddd';

            let html = `
                <strong>${quantidade}x ${escapeHtml(item.nome)}</strong>
                <p>
                    Valor unitário:
                    R$ ${preco.toFixed(2).replace('.', ',')}
                </p>
            `;

            if (customizacoes.length > 0) {
                html += '<div><strong>Personalizações:</strong><ul>';
                customizacoes.forEach(function(extra) {
                    const qtdExtra = Number(extra.quantidade) || 0;
                    const precoExtra = Number(extra.preco_venda) || 0;
                    html += `
                        <li>
                            ${escapeHtml(extra.nome)}
                            (${qtdExtra}x) -
                            R$ ${(precoExtra * qtdExtra)
                                .toFixed(2).replace('.', ',')}
                        </li>
                    `;
                });
                html += '</ul></div>';
            }
            linha.innerHTML = html;
            lista.appendChild(linha);
        });
    }

    const total = document.createElement('p');
    total.innerHTML = `
        <strong>Total: R$
        ${totalCalculado.toFixed(2).replace('.', ',')}</strong>
    `;
    lista.appendChild(total);
    document.getElementById('modalPedido').showModal();
}

function carregarDadosEdicao() {

    const pedido = pedidoAtualData.pedido;
    const itens = pedidoAtualData.itens;

    resetarFormulario();

    document.getElementById('form_acao').value = 'editar';
    document.getElementById('form_id_pedido').value = pedido.id;
    document.getElementById('modalTituloCadastro').innerText = 'Editar Pedido #' + pedido.id;
    document.getElementById('btnSubmitModal').innerText = 'Salvar Alterações';
    document.getElementById('nome_cliente').value = pedido.nome_cliente;
    document.getElementById('tipo').value = pedido.tipo;
    document.getElementById('mesa_id').value = pedido.mesa_id || '';
    document.getElementById('chave_validacao').value = pedido.chave_validacao || '';
    
    produtosPedido = [];
    itens.forEach(function(item) {
        produtosPedido.push({
            id: parseInt(item.produto_id),
            nome: item.nome,
            preco: parseFloat(item.preco_venda),
            quantidade: parseInt(item.quantidade),
            customizacoes: item.customizacoes || []
        });
    });

    atualizarListaProdutos();

    document.getElementById('modalPedido').close();
    document.getElementById('modalCadastro').showModal();
}

function ModalFecharConta() {
    document.getElementById('modalPedido').close();
    document.getElementById('deletar_id_pedido').value = pedidoAtualData.pedido.id;
    document.getElementById('ModalFecharConta').showModal();
}

function abrirMesas(mesaId) {
    fetch(`?obter_pedido_mesa=${mesaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.encontrado) {
                pedidoAtualData = data;
                abrirAcoesPedido();
            }
        })
        .catch(error => {
            console.error('Erro ao buscar pedido:', error);
        });
}

function abrirDelivery(pedidoId) {

    fetch(`?obter_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.pedido) {
                pedidoAtualData = data;
                abrirAcoesPedido();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
}

function resetarFormulario() {
    const form = document.getElementById('modalCadastro').querySelector('form');
    form.reset();
    produtosPedido = [];
    produtoCustomizando = null;
    document.getElementById('listaProdutosPedido').innerHTML = `
        <div class="pedido-vazio">
            Nenhum produto adicionado.
        </div>
    `;
    document.getElementById('valorTotalTexto').innerText = 'R$ 0,00';
    document.getElementById('valor_total').value = '0.00';
    document.getElementById('itens_pedido').value = '';
    document.getElementById('secao-mesas').style.display = 'none';
    document.getElementById('modalCustomizacao').close();
}

function abrirModalCadastro() {
    resetarFormulario();
    document.getElementById('form_acao').value = 'cadastrar';
    document.getElementById('modalTituloCadastro').innerText ='Fazer Novo Pedido';
    document.getElementById('btnSubmitModal').innerText ='Enviar Para a Cozinha';
    atualizarFormulario();
    document.getElementById('chave_validacao').value =gerarChaveDeValidacao();
    document.getElementById('modalCadastro').showModal();
}

function gerarChaveDeValidacao(){
    return Math.random().toString(36).substring(2, 10).toUpperCase();
}

function abrirPedido(pedido, itens) {
    pedidoAtualData = {pedido: pedido,itens: itens};
    document.getElementById('tituloAcoes').innerText =
        pedido.tipo === 'local'
            ? 'Mesa ' + pedido.mesa_id
            : 'Pedido #' + pedido.id;
    document.getElementById('detalhes_produto_info').innerHTML = `
        <p><strong>Cliente:</strong> ${pedido.nome_cliente}</p>
        <p><strong>Tipo:</strong> ${pedido.tipo}</p>
        <p><strong>Status:</strong> ${pedido.status}</p>
    `;
    document.getElementById('modalAcoes').showModal();
}

function adicionarProduto(id, nome, preco) {
    produtoCustomizando = {
        id: id,
        nome: nome,
        preco: preco,
        quantidade: 1,
        customizacoes: []
    };

    document.getElementById('nomeProdutoCustomizacao').innerText = nome;
    document.getElementById('customizacoesEscolhidas').innerHTML =
        '<p>Nenhuma personalização.</p>';

    document.getElementById('modalCustomizacao').showModal();
}

function alterarExtra(id, nome, preco) {
    if (!produtoCustomizando) return;

    const existente = produtoCustomizando.customizacoes.find(
        item => item.produto_id === id && item.tipo_acao === 'adicionar'
    );

    if (existente) {
        existente.quantidade++;
    } else {
        produtoCustomizando.customizacoes.push({
            produto_id: id,
            nome: nome,
            preco: preco,
            tipo_acao: 'adicionar',
            quantidade: 1
        });
    }

    atualizarCustomizacoesEscolhidas();
}

function atualizarCustomizacoesEscolhidas() {
    const area = document.getElementById('customizacoesEscolhidas');
    const extras = produtoCustomizando.customizacoes;

    if (extras.length === 0) {
        area.innerHTML = '<p>Nenhuma personalização.</p>';
        return;
    }

    area.innerHTML = '';

    extras.forEach((extra, index) => {
        const linha = document.createElement('div');

        linha.textContent =
            `${extra.nome} (${extra.quantidade}x) — ` +
            `R$ ${(extra.preco * extra.quantidade).toFixed(2).replace('.', ',')} `;

        const remover = document.createElement('button');
        remover.type = 'button';
        remover.textContent = '−';
        remover.onclick = function() {
            extra.quantidade--;

            if (extra.quantidade <= 0) {
                extras.splice(index, 1);
            }

            atualizarCustomizacoesEscolhidas();
        };

        linha.appendChild(remover);
        area.appendChild(linha);
    });
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
        lista.innerHTML = `
            <div class="pedido-vazio">
                Nenhum produto adicionado.
            </div>
        `;
        document.getElementById('valorTotalTexto').innerText = 'R$ 0,00';
        document.getElementById('valor_total').value = '0.00';
        return;
    }
    produtosPedido.forEach(function(produto, index) {
        const totalExtras = (produto.customizacoes || [])
        .reduce((total, extra) => {
        return total + (extra.preco * extra.quantidade);
        }, 0);
        const subtotal = (produto.preco + totalExtras) * produto.quantidade;
        total += subtotal;
        const item = document.createElement('div');
        item.className = 'item-pedido';
        let observacao = '';
        if (produto.observacao) {
            observacao = `
                <div>
                    <strong>Observação:</strong>
                    ${escapeHtml(produto.observacao)}
                </div>
            `;
        }
        item.innerHTML = `
            <div>
                <strong>
                    ${escapeHtml(produto.nome)}
                </strong>
                <span>
                    ${produto.quantidade}x
                </span>
            </div>
            <div>
                R$ ${subtotal
                    .toFixed(2)
                    .replace('.', ',')}
            </div>
            ${observacao}
            <button type="button" onclick="removerProduto(${index})">Remover</button>
        `;
        lista.appendChild(item);
    });
    document.getElementById('valorTotalTexto').innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
    document.getElementById('valor_total').value = total.toFixed(2);
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

    console.log('Botão:', categoria);
    const produtos = document.querySelectorAll('.card-produto');
    console.log('Produtos:', produtos);

    produtos.forEach(function(produto) {

        const categoriaProduto = produto.dataset.categoria.trim();
        console.log('Categoria do produto:', categoriaProduto);
        if (
            categoria === 'todos' ||
            categoriaProduto === categoria.trim()
        ) {
            produto.style.display = '';
        } else {
            produto.style.display = 'none';
        }
    });

    document.querySelectorAll('.categoria-btn').forEach(function(btn) {btn.classList.remove('ativo');});
    botao.classList.add('ativo');
    
}

function atualizarFormulario() {
    const tipo = document.getElementById('tipo').value;
    const secaoMesas = document.getElementById('secao-mesas');
    const mesas = document.getElementById('mesa_id');

    secaoMesas.style.display = 'none';
    mesas.required = false;

    if (tipo === 'local') {
        secaoMesas.style.display = 'block';
        mesas.required = true;
    }
}

document.querySelector('#modalCadastro form').addEventListener('submit', function() {
    document.getElementById('itens_pedido').value =
        JSON.stringify(produtosPedido);
    });

</script>
</body>
</html>