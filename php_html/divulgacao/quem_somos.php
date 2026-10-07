<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Quem Somos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../../css/divulgacao/quem_somos.css">

</head>

<body>
    <header>
    <?php
    include __DIR__ . "/../../includes/header.php";
    ?>
  </header>
    <main>
        <div class="container-fluid p-0 ">

            <section class="quem-somos">
                <div class="container">
               
                    <div class="row m-0 linha-titulo align-items-center">

                  
                        <div class="col-12 col-md-6 titulo-quem-somos mb-4 mb-md-0 d-flex flex-column align-items-center align-items-md-start text-center text-md-start order-2 order-md-1">
                            <h1>
                                <span class="">Quem</span>
                                <span class="somos">Somos</span>
                            </h1>
                            <h2 class="text-center text-md-start mt-3 mt-md-0">O Disco Burguer nasceu da paixão por música boa e hambúrguer de verdade. Fundado em 2024, trazemos uma experiência retro-moderna para os nossos clientes.</h2>
                        </div>

                    
                        <div class="col-12 col-md-6 bola mt-4 mt-md-0 order-1 order-md-2">
                       
                            <img class="img-bola_cristal img-fluid" src="../../img/globo.png" alt="Globo de Cristal">
                        </div>

                    </div>
                </div>
            </section>

            
            <section class="texto-quem-somos">
                <div class="card-quem-somos">
                    <!-- Adicionei w-100 para o row preencher todo o card -->
                    <div class="row w-100 m-0 align-items-stretch">

                  
                        <div class="col-12 col-md-6 mb-5 mb-md-0 order-2 order-md-1">

                            <div class="container-titulo-missao justify-content-center justify-content-md-start mt-4 mt-md-0">
                                <h3 class="titulo-missao">Nossa Missão</h3>
                                <span class="linha-azul-neon"></span>
                            </div>

                            <h3 class="texto text-center text-md-start">
                                A ideia da Disco Burguer surgiu quando dois integrantes do grupo, Gabriel e Nathaly,
                                decidiram procurar objetos antigos na casa dos pais de Nathaly. Durante essa busca,
                                encontraram uma coleção de discos de vinil dos anos 80, juntamente com uma vitrola ainda em
                                pleno funcionamento.<br><br>

                                Ao colocarem um dos discos para tocar, foram envolvidos por uma atmosfera repleta de
                                memórias, energia, cores vibrantes e referências às discotecas da década de 1980. Mesmo não
                                tendo vivido aquela época, reconheceram, por meio de fotos de seus pais e conteúdos
                                pesquisados, o espírito marcante daquele período, além do estilo das lanchonetes retrô
                                vibrantes que faziam sucesso na época.<br><br>

                                Ao compartilharem essa descoberta com os demais integrantes do grupo — Gabriele, Geovanna,
                                Lailla e Marcos — surgiu o seguinte questionamento:<br><br>

                                “E se criássemos um espaço onde as pessoas pudessem reviver o passado, mesmo estando no
                                presente?”<br>
                                A partir dessa reflexão nasceu a ideia da Disco Burguer, uma hamburgueria temática inspirada
                                nos anos 80, que une música, nostalgia e gastronomia em uma experiência diferenciada.<br><br>

                                O nome “Disco” foi escolhido por representar os discos de vinil, um dos maiores símbolos
                                culturais da década de 1980. Mais do que um objeto musical, o disco simboliza conexão,
                                memória afetiva, celebração e a cultura das discotecas, que marcaram a época com dança,
                                expressão e convivência social. Assim, o termo reforça a proposta da marca de unir passado e
                                presente em um ambiente dinâmico e envolvente.<br><br>

                                A proposta da empresa é oferecer mais do que hambúrgueres: proporcionar uma verdadeira
                                viagem ao passado, em um ambiente onde as novas gerações possam vivenciar a atmosfera que
                                marcou a juventude de seus pais, agora reinterpretada de forma moderna e atualizada.
                                A Disco Burguer nasce com o propósito de transformar refeições em experiências, estimulando
                                interação social, a memória afetiva e o entretenimento.
                            </h3>
                        </div>

                        <!-- COLUNA DA IMAGEM: Fica em cima no celular (order-1) e na direita no PC (order-md-2) -->
                        <div class="col-12 col-md-6 img-grupo mt-4 mt-md-0 order-1 order-md-2">
                            <img class="img-fluid" src="../../img/grupo-disco-burguer.png" alt="Equipe Disco Burguer">
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </main>

    <footer  id="contato">
    <?php
    include __DIR__ . "/../../includes/footer.php";
    ?>
    </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmxc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html> 