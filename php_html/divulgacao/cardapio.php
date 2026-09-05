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
?>




<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="../../css/divulgacao/cardapio.css">
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


    <div class="container-fluid">
      <div class="row">
        <div class="col-12 inicio-cardapio text-center">
          <h1>Cardápio</h1>

          <div class="">
            <button>Hamburguers</button>
            <button>Combos</button>
            <button>Shakes</button>
            <button>Porções</button>
            <button>Sobremesas</button>
          </div>
        </div>
      </div>

      <section class="hamburguers">

        <div class="container ">
          <h2 class="titulo-section">burguers</h2>

          <?php foreach ($hamburgueres as $lanche): ?>
            <div class="row itens-cardapio">

              <div class="col-2 m-0">
                <img src="<?php echo $lanche['imagem']; ?>" alt="<?php echo htmlspecialchars($lanche['nome']); ?>">
              </div>

              <div class="col-6 text-itens-cardapio ">
                <h2><?php echo htmlspecialchars($lanche['nome']); ?></h2>
                <h3><?php echo htmlspecialchars($lanche['descricao']); ?></h3>
              </div>

              <div class="col-2 preco-itens-cardapio">
                <h3>R$</h3>
                <h2><?php echo htmlspecialchars($lanche['preco']); ?></h2>
              </div>

              <div class="col-2 div-button-itens-cardapio ">
                <button class="button-itens-cardapio">ola</button>
              </div>

            </div>
          <?php endforeach; ?>

        </div>

      </section>
  </main>





  <footer>
    <?php
    include __DIR__ . "/../../footer.php";
    ?>

  </footer>

<section class="hamburguers">



        <div class="container ">
          <h2 class="titulo-section">burguers</h2>

          <div class="row itens-cardapio">

            <div class="col-2 m-0">

              <img src="../../img/whats.jpeg" alt="">
            </div>

            <div class="col-6 text-itens-cardapio ">

              <h2>hamburguer de salshiha </h2>
              <h3>VEM COM UMA DELICIOSA salshiha SUCULENTA E CREMEOSA</h3>
            </div>

            <div class="col-2 preco-itens-cardapio">

              <h3>R$</h3>
              <h2>36,90</h2>

            </div>

            <div class="col-2 div-button-itens-cardapio ">

              <button class="button-itens-cardapio">ola</button>
            </div>
          </div>
        </div>

      </section> modifica esse html pra funcionar no array

</body>

</html>