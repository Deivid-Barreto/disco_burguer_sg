<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contatos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/divulgacao/contato.css">
    <link rel="stylesheet" href="../../css/header.css">
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid p-0 align-items-center">

                <a class="navbar-brand icone_header order-1" href="#">
                    <img src="../../img/icone.png" alt="Logo">
                </a>

                <div class="button_header order-2 order-lg-3 ms-auto ms-lg-3 m-4">
                    <button type="button" class="">Pedir agora!</button>
                </div>

                <button class="navbar-toggler order-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="icone-menu">☰</span>
                    <span class="icone-fechar">✕</span>
                </button>

                <div class="collapse navbar-collapse order-4 order-lg-2" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto align-items-end align-items-md-center">
                        <li class="nav-item ">
                            <a class="nav-link" href="../../index.html">Inicio</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Cardápio
                            </a>

                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Hambúrgueres</a></li>
                                <li><a class="dropdown-item" href="#">Bebidas</a></li>
                                <li><a class="dropdown-item" href="#">Combos</a></li>
                                <li><a class="dropdown-item" href="#">Milkshakes</a></li>
                                <li><a class="dropdown-item" href="#">Acompanhamentos</a></li>
                            </ul>

                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="novidades.html">Novidades</a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link" href="sobre.html">Quem Somos</a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link" href="local.html">Local</a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link" href="contato.html">Contato</a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav>
    </header>
    <main>

        <section class="formulario">
            <div class="row">
                <div class="col-12">
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
            </div>
        </section>

        <section class="informacoes">
            <div class="row">
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="../../img/whats.jpeg" class="card-img-top" alt="">
                        <div class="card-body">
                            <h3 class="card-title">Whatsapp</h3>
                            <h5 class="card-title">(99) 99999-9999</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="../../img/email.jpeg" class="card-img-top" alt="">
                        <div class="card-body">
                            <h3 class="card-title">E-mail</h3>
                            <h5 class="card-title">burguerdisco@gmail.com</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="../../img/insta.png" class="card-img-top" alt="">
                        <div class="card-body">
                            <h3 class="card-title">Instagram</h3>
                            <h5 class="card-title">@Disco_Burguer</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="../../img/relogio.png" class="card-img-top" alt="">
                        <div class="card-body">
                            <h3 class="card-title">Horário de Funcionamento</h3>
                            <h5 class="card-title">Segunda á sexta: 11h ás 23h</h5>
                            <h5 class="card-title">Sábado á domingo: 12h ás 00h</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card" style="width: 18rem;">
                        <img src="../../img/localizacao.jpg" class="card-img-top" alt="">
                        <div class="card-body">
                            <h3 class="card-title">Localização</h3>
                            <h5 class="card-title">Rua Fagundes N. 80, Liberdade - São Paulo</h5>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
</body>

</html>