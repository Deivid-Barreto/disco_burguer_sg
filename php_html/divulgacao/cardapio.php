<?php
// Configuração da conexão com o banco de dados via MySQLi
$host    = '127.0.0.1';
$user    = 'root';
$pass    = '';
$db      = 'discoburguer';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Função auxiliar para consultar e formatar os produtos do banco por categoria usando MySQLi
function buscarProdutosPorCategoria($conn, $categoria) {
    $sql = "SELECT 
                nome, 
                descricao, 
                preco_venda, 
                COALESCE(NULLIF(imagem_caminho, ''), '../../img/whats.jpeg') AS imagem 
            FROM produtos 
            WHERE categorias = ? AND ativo = 1 AND visivel = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $produtos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($produtos as &$item) {
        $item['preco'] = 'R$ ' . number_format($item['preco_venda'], 2, ',', '.');
    }

    return $produtos;
}

// Carregamento dinâmico do banco de dados
$hamburgueres = buscarProdutosPorCategoria($conn, 'hamburguer');
$combos       = buscarProdutosPorCategoria($conn, 'combos');
$shakes       = buscarProdutosPorCategoria($conn, 'bebidas');
$porções      = buscarProdutosPorCategoria($conn, 'porcoes');
$sobremesas   = buscarProdutosPorCategoria($conn, 'sobremesa_gelada');
?> 

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disco Burguer Cardápio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="../../css/divulgacao/cardapio.css?v=<?php echo filemtime('../../css/divulgacao/cardapio.css'); ?>">
  <link rel="stylesheet" href="/../css/header.css">
  <link rel="stylesheet" href="/../css/footer.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alfa+Slab+One&family=Black+Ops+One&family=Monoton&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Righteous&display=swap" rel="stylesheet">
 
</head>

<body>
  <header>
    <?php include __DIR__ . "/../../includes/header.php"; ?>
  </header>

  <main>
    <!-- SEÇÃO 1: TOPO E CATEGORIAS -->
    <section id="inicio-cardapio-secao">
      <div class="container">
        <div class="row m-0">
          <div class="col-12 inicio-cardapio">
            <h1>Cardápio</h1>

            <div class="menu-categorias">
              <a href="#hamburguers" class="btn-categoria active img-categorias">
                <img src="../../img/hamburguer.png" alt="">
                <span class="text-center">BURGERS</span>
              </a>
              <a href="#combos" class="btn-categoria img-categorias">
                <img src="../../img/combo.png" alt="">
                <span class="text-center">COMBOS</span>
              </a>
              <a href="#shakes" class="btn-categoria img-categorias">
                <img src="../../img/bebida.png" alt="">
                <span class="text-center">BEBIDAS</span>
              </a>
              <a href="#porcoes" class="btn-categoria img-categorias">
                <img src="../../img/porcoes.png" alt="">
                <span class="text-center">PORÇÕES</span>
              </a>
              <a href="#sobremesas" class="btn-categoria img-categorias">
                <img src="../../img/bolo.png" alt="">
                <span class="text-center">SOBREMESAS</span>
              </a>
            </div>

          </div>
        </div>
      </div>
    </section> 

    <!-- SEÇÃO 2: LISTA DE ITENS DO CARDÁPIO -->
    <section id="itens-cardapio-secao">
      <div class="container">

        <!-- BURGUERS -->
        <section id="hamburguers" class="secao-cardapio">
          <div class="linha">
            <h2 class="titulo-section">burguers</h2>
            <span class="linha-neon"></span>
          </div>
          <?php foreach ($hamburgueres as $lanche): ?>
            <div class="itens-cardapio">
              <img src="<?php echo $lanche['imagem']; ?>" alt="<?php echo htmlspecialchars($lanche['nome']); ?>">
              <div class="text-itens-cardapio">
                <h2><?php echo htmlspecialchars($lanche['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($lanche['descricao']); ?></h3>
              </div>
              <div class="preco-itens-cardapio">
                <h3><?php echo htmlspecialchars($lanche['preco']); ?></h3>
              </div>
              <div class="div-button-itens-cardapio">
                <button class="button-itens-cardapio btn-abrir-modal" 
                        data-nome="<?php echo htmlspecialchars($lanche['nome'], ENT_QUOTES); ?>"
                        data-descricao="<?php echo htmlspecialchars($lanche['descricao'], ENT_QUOTES); ?>"
                        data-preco="<?php echo htmlspecialchars($lanche['preco'], ENT_QUOTES); ?>"
                        data-imagem="<?php echo htmlspecialchars($lanche['imagem'], ENT_QUOTES); ?>">
                  ver
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <!-- COMBOS -->
        <section id="combos" class="secao-cardapio">
          <div class="linha">
            <h2 class="titulo-section">Combos</h2>
            <span class="linha-neon"></span>
          </div>
          <?php foreach ($combos as $combo): ?>
            <div class="itens-cardapio">
              <img src="<?php echo $combo['imagem']; ?>" alt="<?php echo htmlspecialchars($combo['nome']); ?>">
              <div class="text-itens-cardapio">
                <h2><?php echo htmlspecialchars($combo['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($combo['descricao']); ?></h3>
              </div>
              <div class="preco-itens-cardapio">
                <h3><?php echo htmlspecialchars($combo['preco']); ?></h3>
              </div>
              <div class="div-button-itens-cardapio">
                <button class="button-itens-cardapio btn-abrir-modal" 
                        data-nome="<?php echo htmlspecialchars($combo['nome'], ENT_QUOTES); ?>"
                        data-descricao="<?php echo htmlspecialchars($combo['descricao'], ENT_QUOTES); ?>"
                        data-preco="<?php echo htmlspecialchars($combo['preco'], ENT_QUOTES); ?>"
                        data-imagem="<?php echo htmlspecialchars($combo['imagem'], ENT_QUOTES); ?>">
                  ver
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <!-- SHAKES -->
        <section id="shakes" class="secao-cardapio">
          <div class="linha">
            <h2 class="titulo-section">Shakes</h2>
            <span class="linha-neon"></span>
          </div>
          <?php foreach ($shakes as $shake): ?>
            <div class="itens-cardapio">
              <img src="<?php echo $shake['imagem']; ?>" alt="<?php echo htmlspecialchars($shake['nome']); ?>">
              <div class="text-itens-cardapio">
                <h2><?php echo htmlspecialchars($shake['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($shake['descricao']); ?></h3>
              </div>
              <div class="preco-itens-cardapio">
                <h3><?php echo htmlspecialchars($shake['preco']); ?></h3>
              </div>
              <div class="div-button-itens-cardapio">
                <button class="button-itens-cardapio btn-abrir-modal" 
                        data-nome="<?php echo htmlspecialchars($shake['nome'], ENT_QUOTES); ?>"
                        data-descricao="<?php echo htmlspecialchars($shake['descricao'], ENT_QUOTES); ?>"
                        data-preco="<?php echo htmlspecialchars($shake['preco'], ENT_QUOTES); ?>"
                        data-imagem="<?php echo htmlspecialchars($shake['imagem'], ENT_QUOTES); ?>">
                  ver
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <!-- PORÇÕES -->
        <section id="porcoes" class="secao-cardapio">
          <div class="linha">
            <h2 class="titulo-section">Porções</h2>
            <span class="linha-neon"></span>
          </div>
          <?php foreach ($porções as $porção): ?>
            <div class="itens-cardapio">
              <img src="<?php echo $porção['imagem']; ?>" alt="<?php echo htmlspecialchars($porção['nome']); ?>">
              <div class="text-itens-cardapio">
                <h2><?php echo htmlspecialchars($porção['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($porção['descricao']); ?></h3>
              </div>
              <div class="preco-itens-cardapio">
                <h3><?php echo htmlspecialchars($porção['preco']); ?></h3>
              </div>
              <div class="div-button-itens-cardapio">
                <button class="button-itens-cardapio btn-abrir-modal" 
                        data-nome="<?php echo htmlspecialchars($porção['nome'], ENT_QUOTES); ?>"
                        data-descricao="<?php echo htmlspecialchars($porção['descricao'], ENT_QUOTES); ?>"
                        data-preco="<?php echo htmlspecialchars($porção['preco'], ENT_QUOTES); ?>"
                        data-imagem="<?php echo htmlspecialchars($porção['imagem'], ENT_QUOTES); ?>">
                  ver
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

        <!-- SOBREMESAS -->
        <section id="sobremesas" class="secao-cardapio">
          <div class="linha">
            <h2 class="titulo-section">Sobremesas</h2>
            <span class="linha-neon"></span>
          </div>
          <?php foreach ($sobremesas as $sobremesa): ?>
            <div class="itens-cardapio">
              <img src="<?php echo $sobremesa['imagem']; ?>" alt="<?php echo htmlspecialchars($sobremesa['nome']); ?>">
              <div class="text-itens-cardapio">
                <h2><?php echo htmlspecialchars($sobremesa['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($sobremesa['descricao']); ?></h3>
              </div>
              <div class="preco-itens-cardapio">
                <h3><?php echo htmlspecialchars($sobremesa['preco']); ?></h3>
              </div>
              <div class="div-button-itens-cardapio">
                <button class="button-itens-cardapio btn-abrir-modal" 
                        data-nome="<?php echo htmlspecialchars($sobremesa['nome'], ENT_QUOTES); ?>"
                        data-descricao="<?php echo htmlspecialchars($sobremesa['descricao'], ENT_QUOTES); ?>"
                        data-preco="<?php echo htmlspecialchars($sobremesa['preco'], ENT_QUOTES); ?>"
                        data-imagem="<?php echo htmlspecialchars($sobremesa['imagem'], ENT_QUOTES); ?>">
                  ver
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>

      </div>
    </section>

  
    <?php include __DIR__ . "/../../includes/modal_cardapio.php";
     ?>

  </main>

  <footer id="contato">
    <?php include __DIR__ . "/../../includes/footer.php"; ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="../../js/cardapio.js"></script>
  <script src="../../js/modal_cardapio.js"></script>

</body>
</html>