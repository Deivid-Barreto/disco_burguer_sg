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
  <nav class="navbar navbar-expand-lg">
      <div class="container-fluid ps-md-5 pe-md-5 align-items-center">

        <a class="navbar-brand icone_header order-1" href="#">
          <img src="../../img/icone-disco-burguer.png" alt="Logo">
        </a>

        <div class="button_header order-2 order-lg-3 ms-auto ms-lg-3 m-4">
          <button type="button" class="">Pedir agora!</button>  
        </div>

        <button class="navbar-toggler order-3" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
          aria-label="Toggle navigation">
          <span class="icone-menu">☰</span>
          <span class="icone-fechar">✕</span>
        </button>

        <div class="collapse navbar-collapse order-4 order-lg-2" id="navbarSupportedContent">
          <ul class="navbar-nav ms-auto align-items-end align-items-md-center">
            <li class="nav-item me-4 ">
              <a class="nav-link" href="index.php">Inicio</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link" href="php_html/divulgacao/cardapio.php">Cardápio</a>
            </li>
           
            <li class="nav-item me-4">
             <a class="nav-link" href="php_html/divulgacao/feedback.php">Feedback</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link" href="php_html/divulgacao/quem_somos.php">Quem Somos</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link" href="php_html/divulgacao/local.php">Local</a>
            </li>
            <li class="nav-item me-4">
              <a class="nav-link"href="#contato">Contato</a>
            </li>
          </ul>
        </div>

      </div>
    </nav>
  </header>

  <main>
  <div class="container-fluid p-0">
  <section class="hero align-items-center">
    <div class="container">
      <!-- justify-content-center agrupa as colunas no centro do ecrã -->
      <div class="row g-4  justify-content-center align-items-center">
        
        <!-- Coluna do Texto: agora com col-md-5 e alinhada à direita (md-end) para colar no centro -->
        <div class="col-12 col-md-12 d-flex justify-content-center justify-content-md-start ">
          <div class="d-flex flex-column align-items-center align-items-md-start">
            
            <h1 class="titulo-hero d-flex flex-column align-items-center align-items-md-start text-start mt-5">
              <span class="neon-disco">DISCO</span> 
              <span class="neon-burguer">BURGUER</span>
            </h1>
            
            <button class="button-hero mt-5">Ver Cardápio ></button>
          </div>
        </div>
    
       
        
      </div>
    </div>
  </section>
</div>
  <div class="checker"></div>


  <div class="container-fluid p-0">
    <section class="Mais-Pedidos">
      <div class="row m-0 align-items-center">
        <div class="col-12">
          <h2 class="titulo-mp">OS MAIS PEDIDOS</h2>
        </div>
      </div>

      <div class="row m-0">
        <div class="col-12">

          <div class="carrossel-container">
            <button type="button" class="seta-carrossel seta-anterior">‹</button>

            <div class="carrossel-pista">

              <div class="carrossel-item item-rosa item-ativo">
                <h2>X-TUDO 80</h2>
                <img src="img/insta.png" alt="X-Tudo 80">
                <h3>Hamburguer 150g, salada, ovos...</h3>
                <button type="button">VISUALIZAR</button>
              </div>

              <div class="carrossel-item item-azul">
                <h2>NOSTALGIA 80</h2>
                <img src="img/insta.png" alt="Nostalgia 80">
                <h3>150g de hamburguer bovino...</h3>
                <button type="button">VISUALIZAR</button>
              </div>

              <div class="carrossel-item item-amarelo">
                <h2>CHEDDAR 80</h2>
                <img src="img/insta.png" alt="Cheddar 80">
                <h3>150g de hamburguer bovino...</h3>
                <button type="button">VISUALIZAR</button>
              </div>

            </div>
            <button type="button" class="seta-carrossel seta-proximo">›</button>
          </div>

        </div>
      </div>
    </section>
  </div>
<div class="checker"></div>

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
    include __DIR__ . "../includes/footer.php";
    ?>
  </footer> 

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
   
      <script>
    const itens = document.querySelectorAll(".carrossel-item");
    const total = itens.length;
    let indiceAtual = 0;

    function atualizarCarrossel() {
      itens.forEach(function (item, i) {
        let distancia = i - indiceAtual;

        if (distancia > total / 2) distancia -= total;
        if (distancia < -total / 2) distancia += total;

        item.classList.remove("item-ativo", "item-esquerda", "item-direita");

        if (distancia === 0) item.classList.add("item-ativo");
        else if (distancia === -1) item.classList.add("item-esquerda");
        else if (distancia === 1) item.classList.add("item-direita");
      });
    }

    document.querySelector(".seta-proximo").addEventListener("click", function () {
      indiceAtual = (indiceAtual + 1) % total;
      atualizarCarrossel();
    });

    document.querySelector(".seta-anterior").addEventListener("click", function () {
      indiceAtual = (indiceAtual - 1 + total) % total;
      atualizarCarrossel();
    });

    atualizarCarrossel();
  </script>




</body>

</html>