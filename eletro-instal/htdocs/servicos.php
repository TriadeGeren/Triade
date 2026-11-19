<?php
$title = 'Serviços elétricos — Eletro Instal';
$desc  = 'Instalação de tomadas, chuveiros, quadros de distribuição, iluminação LED, manutenção e diagnóstico. Solicite seu orçamento.';
$active = 'servicos';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <div class="page-head"><div class="crumbs"><a href="index.php">Início</a> / Serviços</div><h1>Serviços elétricos</h1>
    <p class="muted" style="margin-top:8px;max-width:640px">Instalações, manutenção e diagnóstico com profissionais habilitados. Descreva sua necessidade e receba um orçamento.</p></div>
  <div class="pop" id="catChips" style="margin:22px 0"></div>
  <div class="svc-grid" id="list" style="padding-bottom:70px"></div>
</div>
<script src="assets/js/servicos.js?v=1" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
