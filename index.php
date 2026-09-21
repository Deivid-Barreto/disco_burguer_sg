<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Disco Burguer-inicio</title>
 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="css/divulgacao/index.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="shortcut icon" href="../../img/logo2.jpeg" type="image/x-icon">
  
</head>

<body>

  <header>
    <?php
    include __DIR__ . "../header.php";
    ?>
  </header>

  <main>
  <div class="container-fluid p-0">
    <section class="hero align-items-center">
  <div class="row m-0 ">
    
   
    <div class="col-12 col-md-6 d-flex justify-content-center align-items-center">
      <div class="d-flex flex-column align-items-center align-items-md-start">
        
        
        <h1 class="titulo-hero d-flex flex-column align-items-center align-items-md-start text-start">
          <span class="neon-disco">DISCO</span> 
          <span class="neon-burguer">BURGUER</span>
        </h1>
        
        
        <button class="button-hero mt-3">Ver Cardápio</button>
      </div>

    </div>

    <!-- COLUNA DA IMAGEM -->
    <div class="col-12 col-md-6 d-flex justify-content-center mt-5 mt-md-0">
      <img class="img-burger-hero img-fluid" src="img/Icone-disco-burguer.png" alt="Logo Disco Burguer">
    </div> 
    
  </div>
</section>
  </div>
  

  <div class="container-fluid p-0">
    <section class="Mais-Pedidos">
      <div class="row m-0 align-items-center">
        <div class="col-12">  
          <h2 class="titulo-mp">OS MAIS PEDIDOS</h2>
      </div>

      <div class="row m-0  cards-container">
        <div class="col-4 card card-rosa">
          <h2>X-TUDO 80</h2>
          <img src="img/insta.png" alt="X-Tudo 80 ">
          <h3>Hamburguer 150g, salada, ovos...</h3>
          <button>VISUALIZAR</button>
        </div>

        <div class="col-4 card card-azul">
          <h2>NOSTALGIA 80</h2> 
          <img src="img/insta.png" alt="Nostalgia 80">
          <h3>150g de hamburguer bovino...</h3>
          <button>VISUALIZAR</button>
        </div>

        <div class="col-4 card card-amarelo">
          <h2>CHEDDAR 80</h2>
          <img src="img/insta.png" alt="Cheddar 80">
          <h3>150g de hamburguer bovino...</h3>
          <button>VISUALIZAR</button>
          </div>
        </div>
      </div>
    </section>
  </div>

 <section class="feedback" id="feedback">
    <div class="container">
        
        <!-- Bloco do Título -->
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                <div class="titulo-feedback">
                    <h1>Deixe sua opinião</h1>
                    <p>Sua opinião é muito importante para melhorarmos sempre!</p>
                </div>
            </div>
        </div>

        <!-- Bloco do Formulário Completo -->
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                <form class="card-feedback" id="form-feedback" method="post" action="processa_feedback.php">

                    <div class="feedback-label">
                        <span class="icone-label">
                            <i class="bi bi-chat-dots"></i>
                        </span>
                        <h3>Seu Comentário</h3>
                    </div>

                    <textarea class="campo-comentario" id="comentario" name="comentario"
                        placeholder="Escreva aqui seu comentário..." rows="5" required></textarea>

                    <div class="feedback-label">
                        <span class="icone-label">
                            <i class="bi bi-star"></i>
                        </span>
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

                        <input type="radio" name="avaliacao" id="estrela1" value="1">
                        <label for="estrela1" title="1 estrela">&#9733;</label>
                    </div>

                    <p class="texto-ajuda">Clique nas estrelas para avaliar</p>

                    <button type="submit" class="btn-enviar-feedback">Enviar Feedback</button>

                </form>
            </div>
        </div>

    </div>
</section>
  


</main>

 <footer id="contato">
    <?php
    include __DIR__ . "../footer.php";
    ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
</body>

</html>