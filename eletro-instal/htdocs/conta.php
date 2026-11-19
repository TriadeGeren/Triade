<?php
$title = 'Minha conta — Eletro Instal';
$desc  = 'Acompanhe seus pedidos, solicitações de serviço e dados da conta.';
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <div class="page-head"><div class="crumbs"><a href="index.php">Início</a> / Minha conta</div><h1 id="hello">Minha conta</h1></div>
  <div class="tabs" id="tabs" role="tablist">
    <button data-t="pedidos" class="on">Pedidos</button><button data-t="servicos">Serviços</button><button data-t="enderecos">Endereços</button><button data-t="senha">Segurança</button>
  </div>
  <div id="pane" style="padding-bottom:70px"></div>
</div>
<script src="assets/js/conta.js?v=1" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
