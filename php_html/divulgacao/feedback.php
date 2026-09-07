<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Deixe sua Opinião</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/divulgacao/feedback.css">
    <link rel="stylesheet" href="/../css/header.css">

</head>
<body>

</body>
<header>
    <?php
    include __DIR__ . "/../../header.php";
    ?>
</header>

    <main>

        <div class="container-fluid p-0">
            <section class="feedback-nos">

                <div class="row div-feedback">

                    <div class="col-12 titulo-feedback text-center">

                        <h1>Deixe sua Opinião</h1>
                        <p>Sua opinião é muito importante para melhorarmos sempre!</p>

                    </div>

                </div>

                <div class="row justify-content-center">

                    <div class="col-12">

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

            </section>

        </div>

    </main>

    <footer>

    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

</html>