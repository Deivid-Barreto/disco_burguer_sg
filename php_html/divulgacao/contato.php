<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contatos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/../../css/divulgacao/contato.css">
    <link rel="stylesheet" href="/../../css/header.css">
</head>

<body>
    <header>
    <?php
    include __DIR__ . "/../../header.php";
    ?>
    </header>
    <main>

        <section class="contato">
            <div class="row">
                <div class="col-6">
                    <div class="card" style="width: 18rem;">
                        <form action="" method="POST">

                            <label>Nome:</label>
                            <input name="nome" required>

                            <label>Email:</label>
                            <input type="email" name="email" required>

                            <label>Telefone:</label>
                            <input type="tel" name="telefone" required>

                            <label>Comentário:</label>
                            <textarea type="text" name="comentario" required></textarea>

                            <button>Enviar</button>
                    </div>
                </div>





                <div class="col-6 ">

                    <div class="img-contato">
                        <img src="../../img/whats.jpeg" class="card-img-top " alt="">

                        <div class="text-contato">
                            <h1> oi</h1>
                            <h5 class="">(99) 99999-9999</h5>

                        </div>

                    </div>

                    <img src="../../img/email.jpeg" class="card-img-top" alt="">
                    <h3 class="card-title">E-mail</h3>
                    <h5 class="card-title">burguerdisco@gmail.com</h5>

                    <img src="../../img/insta.png" class="card-img-top" alt="">
                    <h3 class="card-title">Instagram</h3>
                    <h5 class="card-title">@Disco_Burguer</h5>

                    <img src="../../img/relogio.png" class="card-img-top" alt="">
                    <h3 class="card-title">Horário de Funcionamento</h3>
                    <h5 class="card-title">Segunda á sexta: 11h ás 23h</h5>
                    <h5 class="card-title">Sábado á domingo: 12h ás 00h</h5>

                    <img src="../../img/localizacao.jpg" class="card-img-top" alt="">
                    <h3 class="card-title">Localização</h3>
                    <h5 class="card-title">Rua Fagundes N. 80, Liberdade - São Paulo</h5>

                </div>
            </div>
        </section>\s

    </main>
</body>

</html>