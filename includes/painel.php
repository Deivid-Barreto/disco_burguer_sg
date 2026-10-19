<?php $paginaAtual = basename($_SERVER['SCRIPT_NAME']); ?>

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
      <a href="inicio.php" class="menu-link <?= $paginaAtual == 'inicio.php' ? 'ativo' : '' ?>"><i class="bi bi-house"></i> Início</a>
      <a href="relatorios.php" class="menu-link <?= $paginaAtual == 'relatorios.php' ? 'ativo' : '' ?>"><i class="bi bi-bar-chart"></i> Relatórios</a>
      <a href="financeiro.php" class="menu-link <?= $paginaAtual == 'financeiro.php' ? 'ativo' : '' ?>"><i class="bi bi-currency-dollar"></i> Financeiro</a>
      <a href="estoque.php" class="menu-link <?= $paginaAtual == 'estoque.php' ? 'ativo' : '' ?>"><i class="bi bi-box-seam"></i> Estoque</a>
      <a href="cardapio.php" class="menu-link <?= $paginaAtual == 'cardapio.php' ? 'ativo' : '' ?>"><i class="bi bi-journal-text"></i> Cardápio</a>
      <a href="funcionarios.php" class="menu-link <?= $paginaAtual == 'funcionarios.php' ? 'ativo' : '' ?>"><i class="bi bi-person-badge"></i> Funcionários</a>
      <a href="usuarios.php" class="menu-link <?= $paginaAtual == 'usuarios.php' ? 'ativo' : '' ?>"><i class="bi bi-people"></i> Usuários</a>
      <a href="configuracoes.php" class="menu-link <?= $paginaAtual == 'configuracoes.php' ? 'ativo' : '' ?>"><i class="bi bi-gear"></i> Configurações</a>
    </nav>
  </div>
</div>