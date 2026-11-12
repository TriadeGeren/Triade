<?php $usuario = usuarioLogado(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tituloPagina) ? e($tituloPagina) . ' — Studio Verso' : 'Studio Verso — Artesanato Personalizado' ?></title>
<meta name="description" content="Sketchbooks personalizados, amigurumis e desenhos por encomenda, feitos à mão pelo Studio Verso.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&family=Caveat:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
<link rel="icon" href="<?= BASE_PATH ?>/assets/img/LogoStudioVersoOficial.jpeg">
<script>(function(){try{var t=localStorage.getItem('sv_tema');if(!t&&window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)t='escuro';if(t==='escuro')document.documentElement.setAttribute('data-tema','escuro');}catch(e){}})();</script>
<script src="<?= BASE_PATH ?>/assets/js/tema.js" defer></script>
</head>
<body>

<header class="topbar">
  <div class="container">
    <a href="<?= BASE_PATH ?>/index.php" class="logo">
      <img src="<?= BASE_PATH ?>/assets/img/LogoStudioVersoOficial.jpeg" alt="Studio Verso">
      Studio <span>Verso</span>
    </a>

    <nav class="menu">
      <a href="<?= BASE_PATH ?>/index.php#catalogo">Catálogo</a>
      <a href="<?= BASE_PATH ?>/index.php?cat=sketchbooks#catalogo">Sketchbooks</a>
      <a href="<?= BASE_PATH ?>/index.php?cat=amigurumis#catalogo">Amigurumis</a>
      <a href="<?= BASE_PATH ?>/index.php#encomenda">Sob Encomenda</a>
      <a href="<?= BASE_PATH ?>/index.php#sobre">Sobre</a>
      <a href="<?= BASE_PATH ?>/contato.php">Contato</a>
    </nav>

    <div class="acoes">
      <button type="button" class="btn-tema" data-alternar-tema title="Mudar para o modo escuro">🌙</button>
      <?php if ($usuario): ?>
        <a href="<?= BASE_PATH ?>/logout.php" title="Sair, <?= e($usuario['nome']) ?>">☺</a>
      <?php else: ?>
        <a href="<?= BASE_PATH ?>/login.php" title="Entrar">☺</a>
      <?php endif; ?>
      <button id="btnAbrirCarrinho" title="Carrinho">
        🧺 <span class="badge-carrinho" id="qtdCarrinho">0</span>
      </button>
      <button class="menu-mobile-btn">☰</button>
    </div>
  </div>
</header>

<!-- Carrinho lateral -->
<div class="overlay" id="overlay"></div>
<aside class="drawer" id="drawerCarrinho">
  <div class="drawer-topo">
    <h3>Seu carrinho</h3>
    <button id="btnFecharCarrinho">✕</button>
  </div>
  <div class="itens-carrinho" id="listaCarrinho">
    <p style="color:var(--tinta-suave)">Seu carrinho está vazio.</p>
  </div>
  <div class="drawer-baixo">
    <div class="subtotal">
      <span>Subtotal</span>
      <span id="subtotalCarrinho">R$ 0,00</span>
    </div>
    <a href="<?= BASE_PATH ?>/finalizar.php" class="btn btn-primario" style="display:block;text-align:center;">Finalizar compra</a>
    <p style="font-size:.75rem;color:var(--tinta-suave);margin-top:10px;">
      Pagamento seguro via Pix, cartão ou boleto pelo Mercado Pago.
    </p>
  </div>
</aside>
