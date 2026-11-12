<?php require __DIR__ . "/auth.php"; require_login(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Administrativo — Gelacionado</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="index.php" class="brand">
      <img src="logo.png" alt="Gelacionado" class="imagem_logo">
      <span>Gelacionado<small>PAINEL ADMINISTRATIVO</small></span>
    </a>

    <nav class="main-nav" id="main-nav">
      <a href="index.php">Início</a>
      <a href="servicos.php">Serviços</a>
      <a href="paineis-inteligentes.php">Painéis Inteligentes</a>
      <a href="contato.php">Contato</a>
    </nav>

    <div class="header-actions">
      <button id="admin-logout" class="btn btn-ghost btn-sm" style="display:none;">Sair</button>
      <a href="logout.php" class="btn btn-ghost btn-sm">Sair da conta</a>
      <button class="nav-toggle" aria-label="Abrir menu" aria-controls="main-nav">☰</button>
    </div>
  </div>
</header>

<main>

  <!-- GATE DE LOGIN (simples, apenas client-side) -->
  <div id="admin-login-gate" class="login-gate">
    <div class="login-box">
      <div class="gauge-mini">G</div>
      <h2 style="margin-bottom:6px;">Acesso restrito</h2>
      <p style="font-size:.9rem; margin-bottom:22px;">Entre com a senha de administrador para gerenciar o catálogo da Gelacionado.</p>

      <form id="admin-login-form" novalidate>
        <div class="form-row" style="text-align:left;">
          <label for="admin-pass">Senha de administrador</label>
          <input type="password" id="admin-pass" name="pass" required placeholder="••••••••">
          <div class="field-error field-error-login" style="display:none;">Senha incorreta. Tente novamente.</div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Entrar no painel</button>
      </form>
      <p style="font-size:.72rem; margin-top:18px; color:var(--text-faint);">Senha padrão de demonstração: gelacionado2025</p>
    </div>
  </div>

  <!-- PAINEL PRINCIPAL -->
  <div id="admin-shell" class="admin-shell" style="display:none;">
    <aside class="admin-side">
      <h4>GESTÃO DE CATÁLOGO</h4>
      <button type="button" class="admin-nav-item active" data-admin-view="produtos">📦 Produtos &amp; Serviços</button>
      <button type="button" class="admin-nav-item" data-admin-view="geral">📊 Visão geral</button>
      <h4 style="margin-top:28px;">ATALHOS</h4>
      <a class="admin-nav-item" href="servicos.php">Ver página de Serviços</a>
      <a class="admin-nav-item" href="paineis-inteligentes.php">Ver página de Painéis</a>
    </aside>

    <div class="admin-main">

      <!-- VISÃO: PRODUTOS & SERVIÇOS -->
      <div id="admin-view-produtos" class="admin-view">
        <div class="admin-toolbar">
          <div>
            <h2 style="margin-bottom:2px;">Catálogo de produtos e serviços</h2>
            <p style="margin:0; font-size:.88rem;">Cadastre, edite ou remova itens. As alterações refletem automaticamente nas páginas públicas.</p>
          </div>
          <div style="display:flex; gap:10px;">
            <button id="admin-reset-catalog" class="btn btn-ghost btn-sm">Restaurar padrão</button>
            <button id="admin-new-product" class="btn btn-primary btn-sm">+ Novo item</button>
          </div>
        </div>

        <div class="stat-card-row">
          <div class="stat-card">
            <div class="num" data-stat-total>0</div>
            <div class="lbl">Itens no catálogo</div>
          </div>
          <div class="stat-card">
            <div class="num" data-stat-servicos>0</div>
            <div class="lbl">Serviços de climatização</div>
          </div>
          <div class="stat-card">
            <div class="num" data-stat-paineis>0</div>
            <div class="lbl">Painéis inteligentes</div>
          </div>
          <div class="stat-card">
            <div class="num" data-stat-avg>R$ 0,00</div>
            <div class="lbl">Preço médio</div>
          </div>
        </div>

        <div class="admin-filter-row">
          <input type="search" id="admin-search" class="admin-search" placeholder="Buscar item pelo nome ou descrição...">
          <div class="filter-pills" id="admin-category-filter">
            <span class="filter-pill active" data-filter-category="todos">Todos</span>
            <span class="filter-pill" data-filter-category="servico">Serviços</span>
            <span class="filter-pill" data-filter-category="painel">Painéis</span>
          </div>
        </div>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Item</th>
              <th>Categoria</th>
              <th>Descrição</th>
              <th>Preço</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody id="admin-table-body">
            <tr><td colspan="5">Carregando…</td></tr>
          </tbody>
        </table>
      </div>

      <!-- VISÃO: GERAL (DASHBOARD) -->
      <div id="admin-view-geral" class="admin-view" style="display:none;">
        <div class="admin-toolbar">
          <div>
            <h2 style="margin-bottom:2px;">Visão geral do catálogo</h2>
            <p style="margin:0; font-size:.88rem;">Resumo rápido do que está publicado nas páginas de Serviços e Painéis Inteligentes.</p>
          </div>
        </div>

        <div class="stat-card-row">
          <div class="stat-card">
            <div class="num" data-overview-total>0</div>
            <div class="lbl">Itens no catálogo</div>
          </div>
          <div class="stat-card">
            <div class="num" data-overview-avg>R$ 0,00</div>
            <div class="lbl">Preço médio geral</div>
          </div>
          <div class="stat-card">
            <div class="num" data-overview-total-value>R$ 0,00</div>
            <div class="lbl">Valor somado do catálogo</div>
          </div>
        </div>

        <div class="overview-grid">
          <div class="overview-card">
            <h3>Item mais barato</h3>
            <div data-overview-cheapest>—</div>
          </div>
          <div class="overview-card">
            <h3>Item mais caro</h3>
            <div data-overview-priciest>—</div>
          </div>
          <div class="overview-card">
            <h3>Último item cadastrado</h3>
            <div data-overview-latest>—</div>
          </div>
        </div>
      </div>

    </div>
  </div>

</main>

<!-- MODAL DE CRIAÇÃO / EDIÇÃO -->
<div class="modal-overlay" id="product-modal">
  <div class="modal-box">
    <button type="button" class="modal-close" data-modal-close aria-label="Fechar">✕</button>
    <h3 class="modal-title">Novo item do catálogo</h3>

    <form id="product-form">
      <input type="hidden" id="p-id">

      <div class="form-row">
        <label for="p-category">Categoria *</label>
        <select id="p-category" required>
          <option value="servico">Serviço de climatização</option>
          <option value="painel">Painel inteligente</option>
        </select>
      </div>

      <div class="form-row">
        <label for="p-name">Nome do item *</label>
        <input type="text" id="p-name" required placeholder="Ex: Instalação de Split 9.000 BTUs">
      </div>

      <div class="form-row">
        <label for="p-desc">Descrição *</label>
        <textarea id="p-desc" required placeholder="Descreva o serviço ou produto para o cliente final"></textarea>
      </div>

      <div class="form-2col">
        <div class="form-row">
          <label for="p-price">Preço estimado (R$) *</label>
          <input type="number" id="p-price" min="0" step="0.01" required placeholder="0,00">
        </div>
        <div class="form-row">
          <label for="p-icon">Ícone (emoji)</label>
          <input type="text" id="p-icon" maxlength="4" placeholder="❄️">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Salvar item</button>
    </form>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>
