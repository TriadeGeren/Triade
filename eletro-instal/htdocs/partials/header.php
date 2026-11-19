<?php /** Cabeçalho da loja. Defina antes: $active ('home','catalogo','servicos') */ $active = $active ?? ''; ?>
<a class="skip" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
  <div class="wrap bar">
    <a href="index.php" class="brand" aria-label="Eletro Instal — início">
      <span class="brand-mark"><svg class="ic"><use href="assets/img/icons.svg#i-bolt"/></svg></span>
      <span class="brand-name">Eletro<b>Instal</b></span>
    </a>
    <form class="search" action="catalogo.php" method="get" role="search">
      <svg class="ic"><use href="assets/img/icons.svg#i-search"/></svg>
      <input type="search" name="busca" placeholder="Buscar cabos, disjuntores, lâmpadas…" aria-label="Buscar produtos" autocomplete="off">
    </form>
    <nav class="nav" id="nav" aria-label="Principal">
      <a href="index.php" class="<?= $active === 'home' ? 'on' : '' ?>">Início</a>
      <a href="catalogo.php" class="<?= $active === 'catalogo' ? 'on' : '' ?>">Catálogo</a>
      <a href="servicos.php" class="<?= $active === 'servicos' ? 'on' : '' ?>">Serviços</a>
    </nav>
    <div class="actions">
      <div id="authSlot" class="auth-slot"><a class="btn btn-ghost btn-sm" href="login.php"><svg class="ic"><use href="assets/img/icons.svg#i-user"/></svg><span>Entrar</span></a></div>
      <a class="cart-link" href="carrinho.php" aria-label="Carrinho">
        <svg class="ic"><use href="assets/img/icons.svg#i-cart"/></svg>
        <span class="cart-count" id="cartCount" hidden>0</span>
      </a>
      <button class="burger" id="burger" aria-label="Abrir menu" aria-expanded="false"><svg class="ic"><use href="assets/img/icons.svg#i-menu"/></svg></button>
    </div>
  </div>
</header>
<div id="toasts" class="toasts" aria-live="polite"></div>
<main id="conteudo">
