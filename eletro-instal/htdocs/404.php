<?php
http_response_code(404);
$title = 'Página não encontrada — Eletro Instal';
$desc  = 'A página que você procura não existe.';
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap"><div class="center-msg"><h1>404</h1><p class="muted">Página não encontrada.</p><p style="margin-top:22px"><a class="btn btn-primary" href="index.php">Voltar ao início</a></p></div></div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
