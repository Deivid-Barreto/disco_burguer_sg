<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disco Burguer - Localização</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Righteous&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../css/divulgacao/local.css">
  <link rel="stylesheet" href="/../css/header.css">
</head>

<body>

  <header>
     <?php
    include __DIR__ . "/../../includes/header.php";
    ?>
  </header>

  <main>

    <div class="container-fluid p-0">
      <section class="local">

        <!-- sol + grade decorativos -->
        <div class="cena-fundo" aria-hidden="true">
          <div class="sol"></div>
          <div class="grade"></div>
        </div>

        <div class="row div-local w-100 m-0 justify-content-center">
          <div class="col-12 titulo-local text-center">
            <h1>Localização</h1>
          </div>
        </div>

        <!-- Estrutura Grid do Bootstrap para responsividade -->
        <div class="row linha-local w-100 m-0 justify-content-center align-items-stretch" style="max-width: 1200px;">

          <!-- Coluna 1: Informações (Ocupa 100% no telemóvel e 5 colunas no PC) -->
          <div class="col-12 col-lg-5 mb-4 mb-lg-0 d-flex">
            <div class="card-info w-100">

              <div class="text-contato">
                <div class="endereco">
                  <span class="pin-icone">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                      <path d="M12 21s7-6.5 7-11a7 7 0 1 0-14 0c0 4.5 7 11 7 11Z" fill="#f12c5f" stroke="#f12c5f" stroke-width="1"/>
                      <circle cx="12" cy="10" r="2.6" fill="#ffdd57"/>
                    </svg>
                  </span>
                  <div>
                    <h3>Rua Fagundes N. 80</h3>
                    <h3>Liberdade - São Paulo</h3>
                  </div>
                </div>

                <hr class="divisor">

                <h5>Horário de Funcionamento</h5>
                <p>Segunda a Sexta: 11h às 23h</p>
                <p>Sábado e Domingo: 12h às 00h</p>

                <a href="https://www.google.com/maps/dir/?api=1&destination=Rua+Fagundes,+80,+Liberdade,+São+Paulo,+SP,+01508-030"
                  target="_blank" rel="noopener noreferrer" class="link-botao">
                  <button id="btn-chegar">Como chegar</button>
                </a>

                <a href="https://wa.me/5511984688318" target="_blank" rel="noopener noreferrer" class="link-botao">
                  <button id="btn-whats">WhatsApp</button>
                </a>
              </div>

            </div>
          </div>

          <!-- Coluna 2: Mapa (Ocupa 100% no telemóvel e 7 colunas no PC) -->
          <div class="col-12 col-lg-7 d-flex">
            <div class="card-local w-100">
              <div class="card-local-interno">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.247535300889!2d-46.64132752730844!3d-23.559551614652044!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59a5e36e2a11%3A0x602492a8496ab7cc!2sR.%20Fagundes%2C%2080%20-%20Liberdade%2C%20S%C3%A3o%20Paulo%20-%20SP%2C%2001508-030!5e0!3m2!1spt-BR!2sbr!4v1787604470440!5m2!1spt-BR!2sbr"
                  allowfullscreen="" loading="lazy"
                  referrerpolicy="strict-origin-when-cross-origin">
                </iframe>
              </div>
            </div>
          </div>

        </div>

      </section>
    </div>

  </main>

  <footer id="contato">
    <?php
    include __DIR__ . "/../../includes/footer.php";
    ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

</body>
</html>