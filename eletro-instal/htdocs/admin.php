<?php
$title = 'Painel administrativo — Eletro Instal';
$desc  = 'Gestão de pedidos, produtos, estoque, serviços e financeiro.';
$bodyClass = 'adm';
$extraHead = "  <meta name=\"robots\" content=\"noindex,nofollow\">\n  <link rel=\"stylesheet\" href=\"assets/css/admin.css?v=1\">\n";
include __DIR__ . '/partials/head.php';
?>
<div class="shell">
  <aside class="side" id="side" aria-label="Menu do painel">
    <a href="admin.php" class="brand side-brand"><span class="brand-mark"><svg class="ic"><use href="assets/img/icons.svg#i-bolt"/></svg></span><span class="brand-name">Eletro<b>Instal</b></span></a>
    <nav id="snav" class="snav"></nav>
    <div class="side-foot">
      <a href="index.php" class="snav-a"><svg class="ic"><use href="assets/img/icons.svg#i-home"/></svg> Ver loja</a>
      <button class="snav-a" id="exit"><svg class="ic"><use href="assets/img/icons.svg#i-logout"/></svg> Sair</button>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>
  <div class="main">
    <header class="top">
      <button class="burger" id="sideBtn" aria-label="Abrir menu"><svg class="ic"><use href="assets/img/icons.svg#i-menu"/></svg></button>
      <h1 id="ttl">Dashboard</h1>
      <div class="top-r"><span class="who" id="who"></span></div>
    </header>
    <div class="view" id="view"></div>
  </div>
</div>
<div id="toasts" class="toasts" aria-live="polite"></div>
<script src="assets/js/vendor/chart.umd.js"></script>
<script src="assets/js/app.js?v=1"></script>
<script src="assets/js/admin.js?v=1"></script>
<script src="assets/js/admin-a.js?v=1"></script>
<script src="assets/js/admin-b.js?v=1"></script>
</body>
</html>
