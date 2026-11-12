<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrinho de Orçamento — Gelacionado</title>
<meta name="description" content="Revise os serviços e painéis inteligentes escolhidos e envie seu pedido de orçamento direto para o WhatsApp da Gelacionado.">
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
      <div class="breadcrumb"><a href="index.php">Início</a> / Carrinho</div>
      <span class="eyebrow">Seu orçamento</span>
      <h1>Carrinho de orçamento</h1>
      <p class="lead">Confira os itens escolhidos, preencha seus dados e envie o pedido direto para o nosso WhatsApp — sem pagamento online, apenas o alinhamento do orçamento.</p>
    </div>
  </section>

  <section>
    <div class="container cart-layout">
      <div>
        <h3 style="margin-bottom:16px;">Itens selecionados (<span data-cart-item-count>0</span>)</h3>
        <div class="cart-list" data-cart-list>
          <div class="empty-state">Carregando carrinho…</div>
        </div>

        <div class="form-panel" style="margin-top:32px;">
          <h3 class="mt-0">Seus dados para o orçamento</h3>
          <form id="cart-form" novalidate>
            <div class="form-2col">
              <div class="form-row">
                <label for="cliente-nome">Nome completo *</label>
                <input type="text" id="cliente-nome" name="nome" required minlength="2" placeholder="Seu nome">
                <div class="field-error">Informe seu nome completo.</div>
              </div>
              <div class="form-row">
                <label for="cliente-ambiente">Tipo de ambiente *</label>
                <select id="cliente-ambiente" name="ambiente" required>
                  <option value="">Selecione</option>
                  <option value="Residência">Residência</option>
                  <option value="Apartamento">Apartamento</option>
                  <option value="Escritório">Escritório</option>
                  <option value="Loja / Comércio">Loja / Comércio</option>
                  <option value="Indústria / Galpão">Indústria / Galpão</option>
                </select>
                <div class="field-error">Selecione o tipo de ambiente.</div>
              </div>
            </div>

            <div class="form-row">
              <label for="cliente-endereco">Endereço / Bairro *</label>
              <input type="text" id="cliente-endereco" name="endereco" required minlength="4" placeholder="Ex: Rua X, 123 — Aldeota, Fortaleza-CE">
              <div class="field-error">Informe o endereço ou bairro para a visita técnica.</div>
            </div>

            <div class="form-row">
              <label for="cliente-obs">Observações</label>
              <textarea id="cliente-obs" name="obs" placeholder="Detalhes do ambiente, horário preferido, urgência, etc. (opcional)"></textarea>
            </div>

            <button type="submit" id="whatsapp-checkout" class="btn btn-amber btn-block">Solicitar Orçamento via WhatsApp</button>
            <div class="form-status" data-checkout-status></div>
          </form>
        </div>
      </div>

      <aside>
        <div class="summary-card">
          <h3 class="mt-0">Resumo</h3>
          <div class="summary-line">
            <span>Itens</span>
            <span data-cart-item-count>0</span>
          </div>
          <div class="summary-line">
            <span>Subtotal</span>
            <span data-cart-subtotal>R$ 0,00</span>
          </div>
          <div class="summary-total">
            <span>Total estimado</span>
            <span data-cart-total>R$ 0,00</span>
          </div>
          <p style="font-size:.78rem; margin-top:14px;">Valores estimados. O orçamento final é confirmado após visita técnica, sem compromisso de pagamento online.</p>
        </div>
      </aside>
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
          <li><a href="sobre.php">Sobre nós</a></li>
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
