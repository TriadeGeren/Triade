<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painéis Inteligentes — Gelacionado</title>
<meta name="description" content="Catálogo de painéis inteligentes de automação e controle de ambiente: centrais touch, sensores e kits comerciais. Adicione ao carrinho e peça orçamento pelo WhatsApp.">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="index.php" class="brand">
      <img src="logo.png" alt="Gelacionado" class="imagem_logo">
      <span>Gelacionado<small>CLIMATIZAÇÃO &amp; AUTOMAÇÃO</small></span>
    </a>

    <nav class="main-nav" id="main-nav">
      <a href="index.php">Início</a>
      <a href="servicos.php">Serviços</a>
      <a href="paineis-inteligentes.php" class="current">Painéis Inteligentes</a>
      <a href="sobre.php">Sobre</a>
      <a href="contato.php">Contato</a>
    </nav>

    <div class="header-actions">
      <a href="carrinho.php" class="cart-button">
        🛒 Carrinho
        <span class="cart-count" data-cart-count>0</span>
      </a>
      <a href="logout.php" class="btn btn-ghost btn-sm">Sair</a>
      <button class="nav-toggle" aria-label="Abrir menu" aria-controls="main-nav">☰</button>
    </div>
  </div>
</header>

<main>
  <section class="page-hero">
    <div class="container">
      <div class="breadcrumb"><a href="index.php">Início</a> / Painéis Inteligentes</div>
      <span class="eyebrow">Automação de ambientes</span>
      <h1>Painéis inteligentes Gelacionado</h1>
      <p class="lead">Centrais de controle, sensores e kits de automação que colocam a climatização do seu ambiente sob comando de um toque, do app ou da voz.</p>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="catalog-toolbar">
        <div class="filter-pills">
          <span class="filter-pill active">Todos os painéis</span>
        </div>
        <a href="carrinho.php" class="btn btn-ghost btn-sm">Ir para o carrinho →</a>
      </div>

      <div class="product-grid" data-catalog="painel">
        <!-- preenchido dinamicamente por script.js a partir do localStorage -->
        <div class="empty-state">Carregando catálogo…</div>
      </div>
    </div>
  </section>

  <section class="alt-bg">
    <div class="container">
      <div class="grid-3">
        <div class="feature-card">
          <div class="icon">📱</div>
          <h3>Controle por app</h3>
          <p>Ajuste temperatura, modo e agendamento de qualquer lugar, com histórico de consumo.</p>
        </div>
        <div class="feature-card">
          <div class="icon">🗣️</div>
          <h3>Comando de voz</h3>
          <p>Compatível com Alexa e Google Home para integrar sua climatização à casa inteligente.</p>
        </div>
        <div class="feature-card">
          <div class="icon">🏗️</div>
          <h3>Instalação inclusa</h3>
          <p>Nossa equipe configura o painel, testa as integrações e treina o uso no mesmo dia.</p>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="cta-strip">
        <div>
          <h2>Quer automatizar toda a sua loja ou escritório?</h2>
          <p>Temos soluções multi-zona para ambientes comerciais de qualquer porte.</p>
        </div>
        <a href="contato.php" class="btn btn-amber">Solicitar projeto comercial</a>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand" style="margin-bottom:12px;">
          <img src="logo.png" alt="Gelacionado" class="Footer_logo">
          <span>Gelacionado</span>
        </div>
        <p style="font-size:.88rem;">Climatização e automação inteligente de ambientes em Fortaleza e região metropolitana.</p>
      </div>
      <div>
        <h4>NAVEGAÇÃO</h4>
        <ul>
          <li><a href="index.php">Início</a></li>
          <li><a href="servicos.php">Serviços</a></li>
          <li><a href="sobre.php">Sobre nós</a></li>
        </ul>
      </div>
      <div>
        <h4>ATENDIMENTO</h4>
        <ul>
          <li><a href="contato.php">Fale conosco</a></li>
          <li><a href="carrinho.php">Meu carrinho</a></li>
          <li><a href="admin.php">Painel administrativo</a></li>
        </ul>
      </div>
      <div>
        <h4>CONTATO</h4>
        <ul>
          <li><a href="https://wa.me/5585991025669" target="_blank" rel="noopener noreferrer">(85) 9 9102-5669</a></li>
          <li><a href="https://www.instagram.com/gelacionado/" target="_blank" rel="noopener noreferrer">@gelacionado</a></li>
          <li>Fortaleza — CE</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Gelacionado. Todos os direitos reservados.</span>
      <span>Climatização e automação inteligente.</span>
    </div>
  </div>
</footer>

<script src="script.js"></script>
</body>
</html>
