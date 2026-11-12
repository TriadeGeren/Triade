<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contato — Gelacionado</title>
<meta name="description" content="Fale com a Gelacionado pelo WhatsApp, Instagram ou formulário. Atendimento em Fortaleza-CE.">
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
      <a href="sobre.php">Sobre</a>
      <a href="contato.php" class="current">Contato</a>
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
      <div class="breadcrumb"><a href="index.php">Início</a> / Contato</div>
      <span class="eyebrow">Estamos por perto</span>
      <h1>Fale com a Gelacionado</h1>
      <p class="lead">Tire dúvidas, peça um orçamento avulso ou acompanhe nosso trabalho nas redes. Respondemos rápido pelo WhatsApp.</p>
    </div>
  </section>

  <section>
    <div class="container contact-grid">
      <div>
        <div class="contact-card">
          <div class="ic">💬</div>
          <div>
            <h3>WhatsApp / Telefone</h3>
            <p>Atendimento comercial e suporte técnico.</p>
            <a class="value" href="https://wa.me/5585991025669" target="_blank" rel="noopener noreferrer">(85) 9 9102-5669</a>
          </div>
        </div>

        <div class="contact-card">
          <div class="ic">📷</div>
          <div>
            <h3>Instagram</h3>
            <p>Acompanhe instalações, bastidores e novidades.</p>
            <a class="value" href="https://www.instagram.com/gelacionado/" target="_blank" rel="noopener noreferrer">@gelacionado</a>
          </div>
        </div>

        <div class="contact-card">
          <div class="ic">📍</div>
          <div>
            <h3>Área de atendimento</h3>
            <p>Fortaleza e Região Metropolitana — CE.</p>
          </div>
        </div>

        <div class="contact-card">
          <div class="ic">🕑</div>
          <div>
            <h3>Horário de atendimento</h3>
            <p>Segunda a sábado, das 8h às 18h.</p>
          </div>
        </div>
      </div>

      <div>
        <div class="form-panel">
          <h3 class="mt-0">Envie uma mensagem direta</h3>
          <p style="font-size:.88rem;">Preencha os campos abaixo — ao enviar, abriremos o WhatsApp com sua mensagem pronta.</p>

          <form id="contact-form" novalidate>
            <div class="form-row">
              <label for="contato-nome">Nome completo *</label>
              <input type="text" id="contato-nome" name="nome" required minlength="2" placeholder="Como podemos te chamar?">
              <div class="field-error">Informe seu nome para continuar.</div>
            </div>

            <div class="form-row">
              <label for="contato-telefone">Telefone ou e-mail *</label>
              <input type="text" id="contato-telefone" name="telefone" required minlength="6" placeholder="(85) 9 0000-0000">
              <div class="field-error">Informe um telefone ou e-mail para retorno.</div>
            </div>

            <div class="form-row">
              <label for="contato-mensagem">Mensagem *</label>
              <textarea id="contato-mensagem" name="mensagem" required minlength="10" placeholder="Conte o que você precisa: instalação, manutenção, automação..."></textarea>
              <div class="field-error">Escreva uma mensagem com pelo menos 10 caracteres.</div>
            </div>

            <button type="submit" class="btn btn-amber btn-block">Enviar via WhatsApp</button>
            <div class="form-status"></div>
          </form>
        </div>
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
          <li><a href="sobre.php">Sobre nós</a></li>
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
