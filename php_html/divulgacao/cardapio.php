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
<h1 >Cardápio</h1>

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



<div class="container">
  <h2 >burguers</h2>

<div class="row itens-cardapio">



<div class="col-2 m-0">
 
<img src="../../img/whats.jpeg" alt="">
</div>

<div class="col-6 text-itens-cardapio p-0">

<h2 >hamburguer de salshiha </h2>
<h3>VEM COM UMA DELICIOSA salshiha SUCULENTA E CREMEOSA</h3>
</div>

<div class="col-2 preco-itens-cardapio">

<h3>R$</h3>
<h2>36,90</h2>

</div>

<div class="col-2 ">

<button class="button-itens-cardapio" >ola</button>
</div>
</div>
</div>
  
</section>
</div>
</main>





<footer>
<?php    
 include __DIR__ . "/../../footer.php";
 ?>

</footer>
    


</body>
</html>