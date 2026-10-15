<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Deixe sua Opinião</title>
    <!-- CSS Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <!-- NOVO: Link para o Bootstrap Icons (Para as bolinhas não ficarem vazias) -->
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
        <?php
        include __DIR__ . "/../../includes/header.php";
        ?>
    </header>

    <main>
       <section class="feedback" id="feedback">
    <div class="container h-100 d-flex flex-column justify-content-center">
        
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
        <div class="row justify-content-center w-100 m-0">
            <div class="col-12 col-md-10 col-lg-8 d-flex justify-content-center">
                <form class="card-feedback w-100" id="form-feedback" method="post" action="processa_feedback.php">

                    <!-- NOVO CAMPO: NOME -->
                    <div class="feedback-label">
                        <span class="icone-label">
                            <i class="bi bi-person"></i>
                        </span>
                        <h3>Seu Nome</h3>
                    </div>
                    <input type="text" class="campo-input" id="nome" name="nome" placeholder="Como devemos te chamar?" required>

                    <!-- NOVO CAMPO: TICKET / CÓDIGO -->
                    <div class="feedback-label">
                        <span class="icone-label">
                            <i class="bi bi-ticket-perforated"></i>
                        </span>
                        <h3>Código do Ticket</h3>
                    </div>
                    <input type="text" class="campo-input" id="codigo" name="codigo" placeholder="Digite o código do seu recibo" required>

                    <!-- CAMPO: COMENTÁRIO -->
                    <div class="feedback-label">
                        <span class="icone-label">
                            <i class="bi bi-chat-dots"></i>
                        </span>
                        <h3>Seu Comentário</h3>
                    </div>
                    <textarea class="campo-comentario" id="comentario" name="comentario"
                        placeholder="Escreva aqui seu comentário..." rows="5" required></textarea>

                    <!-- CAMPO: ESTRELAS -->
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

                        <input type="radio" name="avaliacao" id="estrela1" value="1" required>
                        <label for="estrela1" title="1 estrela">&#9733;</label>
                    </div>

                    <p class="texto-ajuda">Clique nas estrelas para avaliar</p>

                    <!-- Botão de Enviar -->
                    <button type="submit" class="btn-enviar-feedback">Enviar Feedback</button>

                </form>
            </div>
        </div>

    </div>
</section>

    <section class="comentarios d-flex align-items-center justify-content-center">
    <!-- Container ajustado para ocupar toda a largura da tela -->
    <div class="container-fluid position-relative px-4 px-md-5"> 
        
        <div class="row">
            <div class="col-12">
                <h1 class="text-center titulo-comentario mb-5">Comentários!</h1> 
            </div>
        </div>

        <!-- Envoltório do Carrossel com Setas -->
        <div class="row position-relative w-100 m-0 d-flex align-items-center">
            
            <!-- Seta Esquerda -->
            <button class="seta-scroll seta-esquerda" id="btn-esquerda">❮</button>

            <div class="col-12 p-0">
                <!-- Contêiner do Scroll (com ID para o JS) -->
                <div class="container-scroll" id="scroll-cards">

                    <!-- Card 1 -->
                    <div class="card-comentario item d-flex flex-column">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../../img/shakes.png" alt="Foto de Perfil" class="foto-perfil">
                            <h2>Santos do Roque</h2>
                        </div>
                        <p class="estrelas">⭐⭐⭐⭐⭐</p>
                        <h3>"Simplesmente incrível! O ambiente é ótimo e o shake é o melhor que já provei. Voltarei mais vezes com certeza."</h3>
                    </div>

                    <!-- Card 2 -->
                    <div class="card-comentario item d-flex flex-column">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../../img/shakes.png" alt="Foto de Perfil" class="foto-perfil">
                            <h2>Maria Silva</h2>
                        </div>
                        <p class="estrelas">⭐⭐⭐⭐⭐</p>
                        <h3>"Atendimento impecável e o sabor é maravilhoso. Super recomendo para quem quer algo saudável e gostoso!"</h3>
                    </div>

                    <!-- Card 3 -->
                    <div class="card-comentario item d-flex flex-column">
                        <div class="d-flex align-items-center mb-4">
                            <img src="../../img/shakes.png" alt="Foto de Perfil" class="foto-perfil">
                            <h2>Naruto</h2>
                        </div>
                        <p class="estrelas">⭐⭐⭐⭐</p>
                        <h3>"Muito bom! O lugar tem uma energia fantástica. Só achei que poderia ter mais opções de sabores, mas é excelente."</h3>
                    </div>

                </div>
            </div>

            <!-- Seta Direita -->
            <button class="seta-scroll seta-direita" id="btn-direita">❯</button>

        </div>
    </div>
</section>
<!-- SCRIPT PARA AS SETAS FUNCIONAREM -->

  <script src="../../js/scroll-comentarios.js"></script>

    </main>

    <footer id="contato">
        <?php
        include __DIR__ . "/../../includes/footer.php";
        ?>
    </footer>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>