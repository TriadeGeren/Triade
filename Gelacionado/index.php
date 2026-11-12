<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gelacionado — Climatização e Automação Inteligente | Fortaleza-CE</title>
<meta name="description" content="Instalação de ar-condicionado, manutenção e painéis inteligentes de automação para climatizar e controlar seu ambiente em Fortaleza-CE.">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="index.php" class="brand">
      <img src="logo.png" alt="Logo" class="imagem_logo" >
      <span>Gelacionado<small>CLIMATIZAÇÃO &amp; AUTOMAÇÃO</small></span>
    </a>

    <nav class="main-nav" id="main-nav">
      <a href="index.php" class="current">Início</a>
      <a href="servicos.php">Serviços</a>
      <a href="paineis-inteligentes.php">Painéis Inteligentes</a>
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

  <!-- HERO -->
  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-copy">
        <span class="eyebrow">Fortaleza · CE</span>
        <h1>O clima certo do jeito mais inteligente de controlar.</h1>
        <p class="lead">A Gelacionado instala, mantém e conecta seu ar-condicionado a painéis inteligentes que colocam temperatura, agendamento e conforto na palma da sua mão.</p>
        <div class="hero-cta">
          <a href="servicos.php" class="btn btn-primary">Ver catálogo de serviços</a>
          <a href="contato.php" class="btn btn-ghost">Falar com um especialista</a>
        </div>
        <div class="hero-stats">
          <div>
            <div class="stat-num">1.200+</div>
            <div class="stat-label">Instalações realizadas</div>
          </div>
          
          <div>
            <div class="stat-num">4,9★</div>
            <div class="stat-label">Avaliação média dos clientes</div>
          </div>
        </div>
      </div>

      <div class="gauge-panel">
        <div class="gauge-row">
          <span>PAINEL FROST · DEMONSTRAÇÃO</span>
          <span class="gauge-live-badge">🖱️ interativo</span>
        </div>

        <div style="position:relative; display:flex; justify-content:center;">
          <div class="gauge-ring" id="gauge-ring" role="slider" tabindex="0" aria-label="Ajustar temperatura simulada" aria-valuemin="16" aria-valuemax="30" aria-valuenow="21"></div>
          <div class="gauge-center">
            <div class="temp" id="gauge-temp">21°C</div>
            <div class="cond" id="gauge-cond">Confortável</div>
          </div>
        </div>

        <div class="gauge-legend">
          <div><strong>Velocidade</strong>
            <button type="button" class="gauge-fan-btn" id="gauge-fan">Média</button>
          </div>
          <div><strong id="gauge-mode-label">Eco</strong>consumo estimado</div>
        </div>
        <p class="gauge-hint">Simulação de como o painel funciona — clique e arraste (ou toque e arraste no celular) para ajustar.</p>
      </div>
    </div>
  </section>

  <!-- DESTAQUES -->
  <section>
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Por que a Gelacionado</span>
        <h2>Climatização e automação sob o mesmo teto</h2>
        <p>Da instalação do split à automação por painel, cuidamos de cada etapa para que o seu ambiente fique gelado, silencioso e sob controle total.</p>
      </div>

      <div class="grid-3">
        <div class="feature-card">
          <div class="icon">❄️</div>
          <h3>Instalação especializada</h3>
          <p>Equipe técnica certificada para split, inverter, multi-split e sistemas comerciais de maior porte.</p>
        </div>
        <div class="feature-card">
          <div class="icon">🎛️</div>
          <h3>Painéis inteligentes</h3>
          <p>Controle sua climatização por app ou comando de voz, com agendamento e cenas por ambiente.</p>
        </div>
        <div class="feature-card">
          <div class="icon">🛠️</div>
          <h3>Manutenção contínua</h3>
          <p>Higienização, recarga de gás e revisões preventivas que evitam quebras e contas de energia altas.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- RESUMO DE SERVIÇOS -->
  <section class="alt-bg">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Catálogo</span>
        <h2>Serviços mais procurados</h2>
      </div>

      <div class="grid-3">
        <div class="feature-card">
          <h3>Instalação Split Inverter</h3>
          <p>A partir de <strong style="color:var(--white)">R$ 480</strong> — instalação completa com suporte e teste de carga.</p>
          <a href="servicos.php" class="btn btn-ghost btn-sm">Ver detalhes</a>
        </div>
        <div class="feature-card">
          <h3>Higienização Completa</h3>
          <p>A partir de <strong style="color:var(--white)">R$ 150</strong> — limpeza profunda contra fungos e odores.</p>
          <a href="servicos.php" class="btn btn-ghost btn-sm">Ver detalhes</a>
        </div>
        <div class="feature-card">
          <h3>Painel Frost One</h3>
          <p>A partir de <strong style="color:var(--white)">R$ 890</strong> — central touch com agendamento inteligente.</p>
          <a href="paineis-inteligentes.php" class="btn btn-ghost btn-sm">Ver detalhes</a>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section>
    <div class="container">
      <div class="cta-strip">
        <div>
          <h2>Pronto para climatizar com inteligência?</h2>
          <p>Monte seu orçamento no catálogo e envie direto para o nosso WhatsApp.</p>
        </div>
        <a href="servicos.php" class="btn btn-amber">Montar orçamento</a>
      </div>
    </div>
  </section>

</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand" style="margin: bottom 20px;">
          <img src="logo.png" alt="Logo" class="Footer_logo" >
          <span>Gelacionado</span>
        </div>
        <p style="font-size:.88rem;">Climatização e automação inteligente de ambientes em Fortaleza e região metropolitana.</p>
      </div>
      <div>
        <h4>NAVEGAÇÃO</h4>
        <ul>
          <li><a href="servicos.php">Serviços</a></li>
          <li><a href="paineis-inteligentes.php">Painéis Inteligentes</a></li>
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
