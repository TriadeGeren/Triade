<?php
require_once __DIR__ . '/config.php';

require_once __DIR__ . '/includes/mercadopago.php';

$pedidoId = (int)($_GET['pedido'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param('i', $pedidoId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

// Não confiamos no que vem na URL: perguntamos ao Mercado Pago qual é o status real.
if ($pedido && $pedido['status_pagamento'] !== 'aprovado') {
    try {
        $pedido['status_pagamento'] = mpSincronizarPedido($conn, $pedidoId) ?? $pedido['status_pagamento'];
    } catch (Throwable $e) {
        error_log('Retorno MP: ' . $e->getMessage());
    }
}
$statusPg = $pedido['status_pagamento'] ?? '';
// Mostra só o final do WhatsApp (a página é aberta só com o número do pedido)
$zapFinal = $pedido && $pedido['whatsapp_cliente'] ? '(**) *****-' . substr(preg_replace('/\D+/', '', $pedido['whatsapp_cliente']), -4) : '';

$tituloPagina = 'Status do pedido';
require __DIR__ . '/includes/header.php';
?>

<section class="secao">
  <div class="container pagina-form">
    <div class="card" style="text-align:center;">
      <?php if (!$pedido): ?>
        <h2>Pedido não encontrado</h2>
        <p>Não localizamos esse pedido. Se você acabou de pagar, chame a gente no WhatsApp com o número do pedido.</p>

      <?php elseif ($statusPg === 'aprovado'): ?>
        <h2>Pagamento aprovado! 🧵💜</h2>
        <p>Seu pedido <strong>#<?= (int)$pedido['id'] ?></strong> foi confirmado. Vamos começar a produção e falar com você
        pelo WhatsApp <strong><?= e($zapFinal) ?></strong> sobre prazo e envio.</p>

      <?php elseif ($statusPg === 'pendente'): ?>
        <h2>Pagamento em análise ⏳</h2>
        <p>Recebemos seu pedido <strong>#<?= (int)$pedido['id'] ?></strong> e o pagamento está sendo processado
        (comum em boleto ou análise de cartão). Assim que for aprovado, avisaremos por e-mail e WhatsApp.</p>

      <?php else: ?>
        <h2>Pagamento não concluído</h2>
        <p>Seu pedido <strong>#<?= (int)$pedido['id'] ?></strong> foi salvo, mas o pagamento não foi aprovado.
        Você pode tentar novamente ou finalizar pelo WhatsApp.</p>
        <a href="<?= BASE_PATH ?>/finalizar.php" class="btn btn-fio" style="margin-right:10px;">Tentar pagar novamente</a>
      <?php endif; ?>

      <a href="<?= BASE_PATH ?>/index.php" class="btn btn-primario" style="margin-top:16px;">Voltar à loja</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
