<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sobre a Gelacionado — Fortaleza, CE</title>
<meta name="description" content="Conheça a história e os diferenciais da Gelacionado, especialista em climatização e automação inteligente de ambientes em Fortaleza-CE.">
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
      <a href="paineis-inteligentes.php">Painéis Inteligentes</a>
      <a href="sobre.php" class="current">Sobre</a>
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
      <div class="breadcrumb"><a href="index.php">Início</a> / Sobre</div>
      <span class="eyebrow">Nossa história</span>
      <h1>Do split ao painel: 9 anos climatizando Fortaleza</h1>
      <p class="lead">A Gelacionado nasceu em Fortaleza para resolver um problema simples: instalar ar-condicionado bem-feito, com técnicos que respeitam prazo e prometem só o que entregam. Hoje somos referência também em automação de ambientes.</p>
    </div>
  </section>

  <section>
    <div class="container about-grid">
      <div>
        <div class="frost-rule"></div>
        <h2>De técnicos autônomos a referência em automação</h2>
        <p>Começamos em 2017 atendendo bairros da capital cearense com instalações de split. Com a demanda crescendo, estruturamos uma equipe própria, padronizamos processos de higienização e manutenção e, em 2022, lançamos nossa linha de painéis inteligentes — a resposta da Gelacionado para quem queria não só um ambiente gelado, mas um ambiente sob controle.</p>
        <p>Hoje atendemos residências, escritórios e comércios em Fortaleza e região metropolitana, unindo know-how em refrigeração com automação residencial de verdade.</p>

        <div class="tag-list">
          <span class="tag-chip">Fundada em 2017</span>
          <span class="tag-chip">Sede em Fortaleza — CE</span>
          <span class="tag-chip">Equipe própria certificada</span>
        </div>
      </div>

      <div class="map-card">
        <iframe
          src="https://www.google.com/maps?q=Fortaleza,CE&output=embed"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="Mapa de atendimento da Gelacionado em Fortaleza"></iframe>
        <div class="map-caption">Atendimento em toda Fortaleza e Região Metropolitana — CE.</div>
      </div>
    </div>
  </section>

  <section class="alt-bg">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Diferenciais</span>
        <h2>Por que clientes voltam a chamar a Gelacionado</h2>
      </div>

      <div class="diff-list" style="max-width:760px;">
        <div class="diff-item">
          <div class="diff-num">01</div>
          <div>
            <h3>Técnicos próprios, não terceirizados</h3>
            <p>Toda a equipe é treinada internamente, o que garante padrão de instalação e segurança em cada serviço.</p>
          </div>
        </div>
        <div class="diff-item">
          <div class="diff-num">02</div>
          <div>
            <h3>Orçamento transparente pelo WhatsApp</h3>
            <p>Você monta o pedido no catálogo do site e recebe o valor estimado antes de fechar qualquer serviço.</p>
          </div>
        </div>
        <div class="diff-item">
          <div class="diff-num">03</div>
          <div>
            <h3>Automação pensada para o dia a dia</h3>
            <p>Nossos painéis são configurados com cenas simples — sem complicação técnica para o cliente final.</p>
          </div>
        </div>
        <div class="diff-item">
          <div class="diff-num">04</div>
          <div>
            <h3>Garantia em todos os serviços</h3>
            <p>Instalações e recargas de gás saem com garantia registrada e suporte pós-serviço.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="cta-strip">
        <div>
          <h2>Quer conhecer nosso trabalho de perto?</h2>
          <p>Fale com a equipe Gelacionado e agende uma visita técnica gratuita.</p>
        </div>
        <a href="contato.php" class="btn btn-amber">Ir para contato</a>
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
          <li><a href="paineis-inteligentes.php">Painéis Inteligentes</a></li>
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
