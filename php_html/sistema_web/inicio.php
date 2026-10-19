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
    <link rel="stylesheet" href="../../css/sistema_web/inicio.css">
<body>



<body>
 
    
    <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1"
        id="menuLateral" aria-labelledby="menuLateralLabel">
  
       <div class="offcanvas-header">
  <div class="d-flex flex-column align-items-center flex-grow-1">
    <img class="icone-painel pb-2 pt-3" src="../../img/icone-disco-burguer.png" alt="Disco Burguer">
    <h6 class="text-center">-SISTEMA DE GESTÃO-</h6>
  </div>

</div>
 
        <div class="offcanvas-body">
            <nav class="d-flex flex-column">
                <a href="inicio.php" class="menu-link ativo"><i class="bi bi-house"></i> Início</a>
                <a href="relatorios.php" class="menu-link"><i class="bi bi-bar-chart"></i> Relatórios</a>
                <a href="financeiro.php" class="menu-link"><i class="bi bi-currency-dollar"></i> Financeiro</a>
                <a href="estoque.php" class="menu-link"><i class="bi bi-box-seam"></i> Estoque</a>
                <a href="cardapio.php" class="menu-link"><i class="bi bi-journal-text"></i> Cardápio</a>
                <a href="funcionarios.php" class="menu-link"><i class="bi bi-person-badge"></i> Funcionários</a>
                <a href="usuarios.php" class="menu-link"><i class="bi bi-people"></i> Usuários</a>
                <a href="configuracoes.php" class="menu-link"><i class="bi bi-gear"></i> Configurações</a>
            </nav>
        </div>
    </div>

  <div class="container-fluid">
 <header class="conteudo card-admin-header mt-2 ">

 <div class="d-flex align-items-center gap-4 p-5 ">
            <button class="btn btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral"
                aria-controls="menuLateral" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex flex-column">
            <h1 class="h4 m-0">Olá, Administrador!</h1>
            <h2 class="h4 m-0">Bem-vindo de volta ao Disco Burguer</h2>
            </div>
        </div>


</header>

</div>
    <!-- ========== CONTEÚDO ========== -->
    <main class="conteudo">
 
       
 
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
 
    <!-- ========== JAVASCRIPT ========== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
 
    <script>
         const menu = document.getElementById('menuLateral');

    // quando o menu abrir: empurra o conteúdo
    menu.addEventListener('show.bs.offcanvas', () => {
        document.body.classList.add('menu-aberto');
    });

    // quando o menu fechar: o conteúdo volta ao lugar
    menu.addEventListener('hidden.bs.offcanvas', () => {
        document.body.classList.remove('menu-aberto');
    });

    // se a tela for grande (computador), abre o menu sozinho
    if (window.innerWidth >= 992) {
        bootstrap.Offcanvas.getOrCreateInstance(menu).show();
    }
    </script>
</body>
</html>