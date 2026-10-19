<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disco Burguer - Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- painel.css ANTES do css da página -->
    <link rel="stylesheet" href="../../css/painel.css">
    <link rel="stylesheet" href="../../css/sistema_web/inicio.css">
</head>

<body>

    <?php include __DIR__ . "/../../includes/painel.php"; ?>

     <?php include __DIR__ . "/../../includes/header_sistema.php"; ?>

    

    <div class="container-fluid">
        <main class="conteudo mt-4">
            <div class="row g-4">
                <div class="col-12">
                    <div class="card-painel">Gráfico de faturamento (col-12)</div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card-painel">Produtos mais vendidos (col-lg-6)</div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card-painel">Alertas de estoque (col-lg-6)</div>
                </div>
            </div>
        </main>
    </div>

    <!-- JAVASCRIPT: o Bootstrap vem PRIMEIRO, o painel.js DEPOIS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../../js/painel.js"></script>
</body>

</html>