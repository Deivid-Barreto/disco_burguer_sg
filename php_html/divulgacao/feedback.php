<?php
require_once __DIR__ . '/../../php/conexao.php'; 

$mensagem_status = null;
$tipo_mensagem = null;
$pedido_verificado = null;
$itens_pedido = [];
$nome_salvo = "";
$codigo_salvo = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'verificar_ticket') {
    $nome_salvo = trim($_POST['nome']);
    $codigo_salvo = trim($_POST['codigo']);

    $stmt = $pdo->prepare("SELECT id, nome_cliente, tipo, valor_total, status FROM pedidos WHERE chave_validacao = ?");
    $stmt->execute([$codigo_salvo]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        $mensagem_status = "Codigo do ticket invalido! Verifique o codigo no seu recibo.";
        $tipo_mensagem = "danger";
    }else {
        $stmt_fb = $pdo->prepare("SELECT id FROM feedbacks WHERE pedido_id = ?");
        $stmt_fb->execute([$pedido['id']]);
        if ($stmt_fb->fetch()) {
            $mensagem_status = "Este pedido ja foi avaliado anteriormente. Obrigado!";
            $tipo_mensagem = "warning";
        } else {
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?codigo=" . urlencode($codigo_salvo) . "&nome=" . urlencode($nome_salvo));
            exit;
        }
    }
}

if (isset($_GET['codigo']) && empty($pedido_verificado)) {
    $codigo_salvo = trim($_GET['codigo']);
    $nome_salvo = trim($_GET['nome'] ?? '');

    $stmt = $pdo->prepare("SELECT id, nome_cliente, tipo, valor_total, status FROM pedidos WHERE chave_validacao = ?");
    $stmt->execute([$codigo_salvo]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        $mensagem_status = "Codigo do ticket invalido! Verifique o codigo no seu recibo.";
        $tipo_mensagem = "danger";
    } else if ($pedido['status'] !== 'finalizado') {
        $mensagem_status = "Este pedido ainda nao foi finalizado. Aguarde a conclusao para avaliar!";
        $tipo_mensagem = "warning";
    } else {
        $stmt_fb = $pdo->prepare("SELECT id FROM feedbacks WHERE pedido_id = ?");
        $stmt_fb->execute([$pedido['id']]);
        if ($stmt_fb->fetch()) {
            $mensagem_status = "Este pedido ja foi avaliado anteriormente. Obrigado!";
            $tipo_mensagem = "warning";
        } else {
            $stmt_itens = $pdo->prepare("
                SELECT ip.quantidade, pr.nome, COALESCE(NULLIF(pr.imagem_caminho, ''), '../../img/whats.jpeg') AS imagem 
                FROM itens_pedidos ip
                JOIN produtos pr ON ip.produto_id = pr.id
                WHERE ip.pedido_id = ? AND ip.status_item != 'cancelado' AND pr.make_or_buy = 'produto_acabado'
            ");
            $stmt_itens->execute([$pedido['id']]);
            $itens_pedido = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

            $pedido_verificado = $pedido;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'enviar_feedback') {
    $pedido_id = intval($_POST['pedido_id']);
    $nome_informado = trim($_POST['nome']);
    $comentario = trim($_POST['comentario']);
    $nota = intval($_POST['avaliacao']);

    $status_moderacao = 'aprovado';

    // Lista personalizada de termos add +
    $palavras_proibidas = ['bosta', 'merda', 'caralho', 'puta', 'porra', 'desgraça', 'corno', 'pinto', 'cacete'];
    
    $comentario_minusculo = mb_strtolower($comentario);
    foreach ($palavras_proibidas as $palavra) {
        
        if (strpos($comentario_minusculo, $palavra) !== false) {
            $status_moderacao = 'bloqueado_filtro';
            break;
        }
    }

    try {
        $stmt_insert = $pdo->prepare("INSERT INTO feedbacks (pedido_id, nome, nota, comentario, status_moderacao) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->execute([$pedido_id, $nome_informado, $nota, $comentario, $status_moderacao]);

        $param_url = ($status_moderacao === 'bloqueado_filtro') ? "enviado=2" : "enviado=1";

        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?" . $param_url);
        exit;
    } catch (PDOException $e) {
        $mensagem_status = "Ocorreu um erro ao registar o feedback.";
        $tipo_mensagem = "danger";
    }
}

// fazer o style
if (isset($_GET['enviado'])) {
    if ($_GET['enviado'] == '1') {
        $mensagem_status = "Feedback enviado com sucesso e publicado no mural!";
        $tipo_mensagem = "success";
    } elseif ($_GET['enviado'] == '2') {
        $mensagem_status = "O seu comentário foi recebido, mas continha termos inadequados e não pôde ser publicado no mural.";
        $tipo_mensagem = "warning";
    }
}

$stmt_mural = $pdo->query("
    SELECT 
        f.nota,
        f.comentario,
        f.data_feedback,
        f.nome,
        (SELECT pr.nome FROM itens_pedidos ip JOIN produtos pr ON ip.produto_id = pr.id WHERE ip.pedido_id = f.pedido_id LIMIT 1) AS nome_produto,
        (SELECT COALESCE(NULLIF(pr.imagem_caminho, ''), '../../img/whats.jpeg') FROM itens_pedidos ip JOIN produtos pr ON ip.produto_id = pr.id WHERE ip.pedido_id = f.pedido_id LIMIT 1) AS imagem_produto
    FROM feedbacks f
    WHERE f.status_moderacao = 'aprovado'
    ORDER BY f.data_feedback DESC
    LIMIT 10
");
$feedbacks_aprovados = $stmt_mural->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Deixe sua Opiniao</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="../../css/divulgacao/feedback.css">
    <link rel="stylesheet" href="../../css/header.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&family=Black+Ops+One&family=Monoton&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Righteous&display=swap" rel="stylesheet">
</head>

<body>

    <header>
        <?php include __DIR__ . "/../../includes/header.php"; ?>
    </header>

    <main>
       <section class="feedback" id="feedback">
        
        <div class="container h-100 d-flex flex-column justify-content-center">
            <div class="efeito">
                
                <div class="row justify-content-center">
                    <div class="col-12 d-flex justify-content-center">
                        <div class="titulo-feedback">
                            <h1>Deixe sua opiniao</h1>
                            <p>Sua opiniao e muito importante para melhorarmos sempre!</p>
                        </div>
                    </div>
                </div>

                <?php if ($mensagem_status): ?>
                    <div class="row justify-content-center w-100 m-0 mb-3">
                        <div class="col-12 col-md-10 col-lg-8">
                            <div class="alert alert-<?= $tipo_mensagem ?> text-center" role="alert">
                                <?= htmlspecialchars($mensagem_status) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center w-100 m-0">
                    <div class="col-12 col-md-10 col-lg-8 d-flex justify-content-center">
                        
                        <?php if (!$pedido_verificado): ?>
                            <form class="card-feedback w-100" id="form-verificar" method="post" action="">
                                <input type="hidden" name="acao" value="verificar_ticket">

                                <div class="feedback-label">
                                    <span class="icone-label"><i class="bi bi-person"></i></span>
                                    <h3>Seu Nome</h3>
                                </div>
                                <input type="text" class="campo-input" id="nome" name="nome" value="<?= htmlspecialchars($nome_salvo) ?>" placeholder="Como devemos te chamar?" required>

                                <div class="feedback-label">
                                    <span class="icone-label"><i class="bi bi-ticket-perforated"></i></span>
                                    <h3>Codigo do Ticket</h3>
                                </div>
                                <input type="text" class="campo-input" id="codigo" name="codigo" value="<?= htmlspecialchars($codigo_salvo) ?>" placeholder="Digite o codigo do seu recibo" required>

                                <button type="submit" class="btn-enviar-feedback mt-3">Validar Ticket</button>
                            </form>

                        <!-- estilizar  -->
                        <?php else: ?>
                            <form class="card-feedback w-100" id="form-feedback" method="post" action="">
                                <input type="hidden" name="acao" value="enviar_feedback">
                                <input type="hidden" name="pedido_id" value="<?= $pedido_verificado['id'] ?>">
                                <input type="hidden" name="nome" value="<?= htmlspecialchars($nome_salvo) ?>">
                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($codigo_salvo) ?>">

                                <div class="feedback-label">
                                    <span class="icone-label"><i class="bi bi-basket"></i></span>
                                    <h3>Selecione os itens que deseja avaliar</h3>
                                </div>

                                <div class="mb-4 text-start">
                                    <div class="row g-3">
                                        <?php foreach ($itens_pedido as $item): ?>
                                            <div class="col-12 col-md-6">
                                                <div class="card bg-dark text-white p-2 d-flex flex-row align-items-center h-100 card-checkbox" onclick="toggleCard(this)">
                                                    <img src="<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;" class="me-3">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 fs-6"><?= htmlspecialchars($item['nome']) ?></h6>
                                                        <small class="text-muted">Quantidade: <?= $item['quantidade'] ?></small>
                                                    </div>
                                                    <div class="ms-2">
                                                        <input class="form-check-input" type="checkbox" name="itens_avaliados[]" value="<?= htmlspecialchars($item['nome']) ?>" style="pointer-events: none;">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="feedback-label">
                                    <span class="icone-label"><i class="bi bi-chat-dots"></i></span>
                                    <h3>Seu Comentario</h3>
                                </div>
                                <textarea class="campo-comentario" id="comentario" name="comentario"
                                    placeholder="Escreva aqui seu comentario..." rows="5" required></textarea>

                                <div class="feedback-label">
                                    <span class="icone-label"><i class="bi bi-star"></i></span>
                                    <h3>Avalie Nossa Loja</h3>
                                </div>

                                <div class="avaliacao-estrelas" id="avaliacao-estrelas">
                                    <input type="radio" name="avaliacao" id="estrela5" value="5">
                                    <label for="estrela5" title="5 estrelas">&#9733;</label>

                                    <input type="radio" name="avaliacao" id="estrela4" value="4">
                                    <label for="estrela4" title="4 estrelas">&#9733;</label>

                                    <input type="radio" name="avaliacao" id="estrela3" value="3">
                                    <label for="estrela3" title="3 estrelas">&#9733;</label>

                                    <input type="radio" name="avaliacao" id="estrela2" value="2">
                                    <label for="estrela2" title="2 estrelas">&#9733;</label>

                                    <input type="radio" name="avaliacao" id="estrela1" value="1" required>
                                    <label for="estrela1" title="1 estrela">&#9733;</label>
                                </div>

                                <p class="texto-ajuda">Clique nas estrelas para avaliar</p>

                                <button type="submit" class="btn-enviar-feedback">Enviar Feedback</button>
                                <a href="" class="btn btn-outline-secondary w-100 mt-2">Digitar outro codigo</a>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="comentarios d-flex align-items-center justify-content-center">
        <div class="container-fluid position-relative px-4 px-md-5"> 
            
            <div class="row">
                <div class="col-12">
                    <h1 class="text-center titulo-comentario mb-5">Comentarios!</h1> 
                </div>
            </div>

            <div class="row position-relative w-100 m-0 d-flex align-items-center">
                
                <button class="seta-scroll seta-esquerda" id="btn-esquerda">❮</button>

                <div class="col-12 p-0">
                    <div class="container-scroll" id="scroll-cards">

                        <?php if (empty($feedbacks_aprovados)): ?>
                            <p class="text-center text-white w-100">Ainda nao existem comentarios aprovados. Seja o primeiro a avaliar!</p>
                        <?php else: ?>
                            <!-- ESTILIZAR OS CARDS DE COMENTARIOS -->
                            <?php foreach ($feedbacks_aprovados as $fb): ?>
                                <div class="card-comentario item d-flex flex-column">
                                    <div class="d-flex align-items-center mb-4">
                                        <img src="<?= htmlspecialchars($fb['imagem_produto'] ?? '../../img/whats.jpeg') ?>" alt="Produto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;" class="me-3">
                                        <div>
                                            <h2 class="mb-0 fs-6"><?= htmlspecialchars($fb['nome'] ?: 'Cliente') ?></h2>
                                            <small class="text-muted"><?= htmlspecialchars($fb['nome_produto'] ?? '') ?></small>
                                        </div>
                                    </div>
                                    <p class="estrelas"><?= str_repeat('★', $fb['nota']) ?></p>
                                    <h3>"<?= htmlspecialchars($fb['comentario']) ?>"</h3>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>

                <button class="seta-scroll seta-direita" id="btn-direita">❯</button>

            </div>
        </div>
    </section>

    <script>
    function toggleCard(cardElement) {
        const checkbox = cardElement.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        if (checkbox.checked) {
            cardElement.classList.add('selecionado');
        } else {
            cardElement.classList.remove('selecionado');
        }
    }
    </script>

    <script src="../../js/scroll-comentarios.js"></script>

    </main>

    <footer id="contato">
        <?php include __DIR__ . "/../../includes/footer.php"; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>