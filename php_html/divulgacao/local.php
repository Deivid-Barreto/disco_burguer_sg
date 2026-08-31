<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disco Burguer - Quem Somos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="/../css/divulgacao/local.css">
  <link rel="stylesheet" href="/../css/header.css">
   
  

</head>

<body>
  
<header>
  <?php
        include __DIR__ . "/../../header.php";
        ?>
</header>

<main>

  <div class="container-fluid p-0 ">
    <section class="local">

      <div class="row div-local">

        <div class="col-12 titulo-local">

          <h1>Localização</h1>

        </div>

      </div>


      <div class="row">

        <div class="col-6 card-info">

          <div class="text-contato">

            <h3>Rua Fagundes N.80</h3>
            <h3>Liberdade - São Paulo</h3>
            <h5>Horário de Funcionamento</h5>
            <p>Segunda á sexta: 11h ás 23h</p>
            <p>Sábado á domingo: 12h ás 00h</p>


            <a href="https://www.google.com/maps/dir/?api=1&destination=Rua+Fagundes,+80,+Liberdade,+São+Paulo,+SP,+01508-030"
              target="_blank" rel="noopener noreferrer">
              <button>Como chegar</button>
            </a>

            <a href="https://wa.me/5511984688318"
              target="_blank" rel="noopener noreferrer">
              <button>WhatsApp</button>
            </a>

          </div>

        </div>

        <div class="col-6 card-local">

         <iframe

            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.247535300889!2d-46.64132752730844!3d-23.559551614652044!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59a5e36e2a11%3A0x602492a8496ab7cc!2sR.%20Fagundes%2C%2080%20-%20Liberdade%2C%20S%C3%A3o%20Paulo%20-%20SP%2C%2001508-030!5e0!3m2!1spt-BR!2sbr!4v1787604470440!5m2!1spt-BR!2sbr"
            width="600"
            height="450"
            style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin">

          </iframe>
          
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

</body>
</html>