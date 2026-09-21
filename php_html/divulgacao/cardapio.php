<?php
// Array com os dados de cada item do cardápio
$hamburgueres = [
  [
    "nome" => "NOSTALGIA 80",
    "descricao" => "Pão brioche, hambúrguer artesanal, cheddar, bacon crocante, onion rings e molho especial da casa.",
    "preco" => "R$ 36,90",
    "imagem" => "../../img/whats.jpeg"
  ],
  [
    "nome" => "CLÁSSICO DISCO",
    "descricao" => "Pão brioche, hambúrguer artesanal, queijo prato, alface, tomate, cebola roxa e molho da casa.",
    "preco" => "R$ 32,90",
    "imagem" => "../../img/whats.jpeg"
  ],
  [
    "nome" => "DANCE BURGUER",
    "descricao" => "Pão brioche, hambúrguer artesanal, queijo prato, bacon, alface, tomate e maionese temperada.",
    "preco" => "R$ 34,90",
    "imagem" => "../../img/whats.jpeg"
  ]
];


$combos = [
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ]
];

$shakes = [
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ]
];

$porções = [
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ]
];
$sobremesas = [
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ],
  [
    "nome" => "2 burgers + 1 fritas grande",
    "descricao" => "Acompanha um chiken jr e um duplo cheddar , com as fritas grandes",
    "preco" => "R$ 60,00",
    "imagem" => "../../img/whats.jpeg"

  ]
];
?>




<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disco Burguer Cardápio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="../../css/divulgacao/cardapio.css?v=<?php echo filemtime('../../css/divulgacao/cardapio.css'); ?>">
  <link rel="stylesheet" href="/../css/header.css">
  <link rel="stylesheet" href="/../css/footer.css">
</head>

<body>
  <header>
    <?php
    include __DIR__ . "/../../header.php";
    ?>
  </header>

  <main>

    <section id="inicio-cardapio-secao">
    <div class="container">
      

     
      <div class="row m-0">
        <div class="col-12 inicio-cardapio">
        <h1 class="">Cardápio</h1>

        <div class="menu-categorias">
        <a href="#hamburguers" class="btn-categoria active img-categorias">
        <img src="../../img/burguer.png" alt="">
        <span class="text-center">BURGERS</span>
    </a>

    <a href="#combos" class="btn-categoria img-categorias">
        <img src="../../img/combos.png" alt="">
        <span class="text-center">COMBOS</span>
    </a>

    <a href="#shakes" class="btn-categoria img-categorias">
        <img src="../../img/shakes.png" alt="">
        <span class="text-center">SHAKES</span>
    </a>

    <a href="#porcoes" class="btn-categoria img-categorias">
        <img src="../../img/porcoes.png" alt="">
        <span class="text-center" >PORÇÕES</span>
    </a>

    <a href="#sobremesas" class="btn-categoria img-categorias">
        <img src="../../img/bolo.png" alt="">
        <span class="text-center">SOBREMESAS</span>
    </a>
         
          </div>
        </div>
      </div>
 </section> 
 <section id="itens-cardapio-secao">
      <div class="container">
       

       
        <section id="hamburguers" class="secao-cardapio">
          <div class="linha">
          
          <h2 class="titulo-section">burguers</h2>
          <span class="linha-neon"></span>
          </div>
          <?php foreach ($hamburgueres as $lanche): ?>
            <div class="row  itens-cardapio">

              <div class="col-2 m-0">
                <img src="<?php echo $lanche['imagem']; ?>" alt="<?php echo htmlspecialchars($lanche['nome']); ?>">
              </div>

              <div class="col-6 text-itens-cardapio ">
                <h2><?php echo htmlspecialchars($lanche['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($lanche['descricao']); ?></h3>
              </div>

              <div class="col-2 preco-itens-cardapio">

                <h3><?php echo htmlspecialchars($lanche['preco']); ?></h3>
              </div>

              <div class="col-2 div-button-itens-cardapio ">
                <button class="button-itens-cardapio">ola</button>
              </div>

            </div>
          <?php endforeach; ?>

      </section>

      <section id="combos" class="secao-cardapio">
          <div class="linha">
          <h2 class="titulo-section">Combos</h2>
          <span class="linha-neon"></span>
          </div>

        <?php foreach ($combos as $combo): ?>
          <div class="row itens-cardapio">

            <div class="col-2 m-0">
              <img src="<?php echo $combo['imagem']; ?>" alt="<?php echo htmlspecialchars($combo['nome']); ?>">
            </div>

            <div class="col-6 text-itens-cardapio ">
              <h2><?php echo htmlspecialchars($combo['nome']); ?></h2>
              <h3><?php echo htmlspecialchars($combo['descricao']); ?></h3>
            </div>

            <div class="col-2 preco-itens-cardapio">

              <h3><?php echo htmlspecialchars($combo['preco']); ?></h3>
            </div>

            <div class="col-2 div-button-itens-cardapio ">
              <button class="button-itens-cardapio">ola</button>
            </div>

          </div>
        <?php endforeach; ?>


      </section>

      <section id="shakes" class="secao-cardapio">
        <div class="linha">
        <h2 class="titulo-section">Shakes</h2>
        <span class="linha-neon"></span>
        </div>

        <?php foreach ($shakes as $shake): ?>
          <div class="row itens-cardapio">

            <div class="col-2 m-0">
              <img src="<?php echo $shake['imagem']; ?>" alt="<?php echo htmlspecialchars($shake['nome']); ?>">
            </div>

            <div class="col-6 text-itens-cardapio ">
              <h2><?php echo htmlspecialchars($shake['nome']); ?></h2>
              <h3><?php echo htmlspecialchars($shake['descricao']); ?></h3>
            </div>

            <div class="col-2 preco-itens-cardapio">

              <h3><?php echo htmlspecialchars($shake['preco']); ?></h3>
            </div>

            <div class="col-2 div-button-itens-cardapio ">
              <button class="button-itens-cardapio">ola</button>
            </div>

          </div>
        <?php endforeach; ?>


      </section>

      <section id="porcoes" class="secao-cardapio">

        <div class="linha">
        <h2 class="titulo-section">Porções</h2>
        <span class="linha-neon"></span>
        </div>

        <?php foreach ($porções as $porção): ?>
          <div class="row  itens-cardapio">

            <div class="col-2 m-0">
              <img src="<?php echo $porção['imagem']; ?>" alt="<?php echo htmlspecialchars($porção['nome']); ?>">
            </div>

            <div class="col-6 text-itens-cardapio ">
              <h2><?php echo htmlspecialchars($porção['nome']); ?></h2>
              <h3><?php echo htmlspecialchars($porção['descricao']); ?></h3>
            </div>

            <div class="col-2 preco-itens-cardapio">

              <h3><?php echo htmlspecialchars($porção['preco']); ?></h3>
            </div>

            <div class="col-2 div-button-itens-cardapio ">
              <button class="button-itens-cardapio">ola</button>
            </div>

          </div>
        <?php endforeach; ?>


      </section>

      <section id="sobremesas" class="secao-cardapio">

      <div class="linha">
      <h2 class="titulo-section">Sobremesas</h2>
      <span class="linha-neon"></span>
      </div>

        <?php foreach ($sobremesas as $sobremesa): ?>
          <div class="row itens-cardapio">

            <div class="col-2 m-0">
              <img src="<?php echo $sobremesa['imagem']; ?>" alt="<?php echo htmlspecialchars($sobremesa['nome']); ?>">
            </div>

            <div class="col-6 text-itens-cardapio ">
              <h2><?php echo htmlspecialchars($sobremesa['nome']); ?></h2>
              <h3><?php echo htmlspecialchars($sobremesa['descricao']); ?></h3>
            </div>

            <div class="col-2 preco-itens-cardapio">

              <h3><?php echo htmlspecialchars($sobremesa['preco']); ?></h3>
            </div>

            <div class="col-2 div-button-itens-cardapio ">
              <button class="button-itens-cardapio">ola</button>
            </div>

          </div>
        <?php endforeach; ?>


     
      </div>
    </div> 
    </section>
    </section>
  
  </main>





  <footer  id="contato">
    <?php
    include __DIR__ . "/../../footer.php";
    ?>

  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../js/cardapio.js"></script>
   <script src="../../js/scroll.js"></script>


</body>

</html>