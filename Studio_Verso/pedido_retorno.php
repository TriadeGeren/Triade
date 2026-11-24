<?php
/**
 * Tela do pedido: mostra como pagar (Pix) enquanto o pagamento não foi confirmado
 * e a confirmação depois que você marca o pedido como pago em Admin > Pedidos.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/pix.php';

$pedidoId = (int)($_GET['pedido'] ?? 0);

$stmt = $conn->prepare('SELECT id, usuario_id, nome_cliente, whatsapp_cliente, total, status_pagamento FROM pedidos WHERE id = ?');
$stmt->bind_param('i', $pedidoId);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

// Só quem fez o pedido (mesmo navegador), o dono da conta ou um administrador abre esta tela.
$usuario = usuarioLogado();
$pode = $pedido && (
    in_array($pedidoId, $_SESSION['pedidos_meus'] ?? [], true)
    || ($usuario && ((int)($usuario['is_admin'] ?? 0) === 1 || (int)($pedido['usuario_id'] ?? 0) === (int)$usuario['id']))
);
if (!$pode) $pedido = null;

$statusPg = $pedido['status_pagamento'] ?? '';
$totalFmt = $pedido ? number_format((float)$pedido['total'], 2, ',', '.') : '';
$payload = ($pedido && pixConfigurado())
    ? pixPayload(PIX_CHAVE, PIX_NOME, PIX_CIDADE, (float)$pedido['total'])
    : '';
$zapUrl = $pedido
    ? 'https://wa.me/' . WHATSAPP_LOJA . '?text=' . rawurlencode('Olá! Fiz o pedido #' . $pedido['id'] . ' (R$ ' . $totalFmt . ') no site e estou enviando o comprovante do Pix.')
    : 'https://wa.me/' . WHATSAPP_LOJA;

$tituloPagina = 'Seu pedido';
require __DIR__ . '/includes/header.php';
?>

<section class="secao">
  <div class="container pagina-form" style="max-width:520px;">
    <div class="card" style="text-align:center;">
      <?php if (!$pedido): ?>
        <h2>Pedido não encontrado</h2>
        <p>Não localizamos esse pedido neste aparelho. Se você já fez o pedido, chame a gente no WhatsApp com o número dele.</p>
        <a href="<?= e($zapUrl) ?>" class="btn btn-primario" target="_blank" rel="noopener">Falar no WhatsApp</a>

      <?php elseif ($statusPg === 'aprovado'): ?>
        <h2>Pagamento confirmado! 🧵💜</h2>
        <p>Seu pedido <strong>#<?= (int)$pedido['id'] ?></strong> foi confirmado. Vamos começar a produção e falar com você pelo WhatsApp sobre prazo e envio.</p>

      <?php elseif ($statusPg === 'recusado' || $statusPg === 'estornado'): ?>
        <h2>Pedido #<?= (int)$pedido['id'] ?></h2>
        <div class="alerta alerta-erro">O pagamento deste pedido não está ativo. Chame a gente no WhatsApp para resolver.</div>
        <a href="<?= e($zapUrl) ?>" class="btn btn-primario" target="_blank" rel="noopener">Falar no WhatsApp</a>

      <?php else: ?>
        <h2>Pedido #<?= (int)$pedido['id'] ?> recebido! 💜</h2>
        <p>Falta só o pagamento. Pague por <strong>Pix</strong> e envie o comprovante pelo WhatsApp.</p>
        <p class="pix-valor">Valor: <strong>R$ <?= $totalFmt ?></strong></p>

        <?php if ($payload !== ''): ?>
          <div class="pix-qr" id="pixQr" aria-label="QR Code do Pix"></div>
          <p class="pix-legenda">Abra o app do seu banco e escolha <em>Pix &gt; Pagar com QR Code</em>. Se o QR não abrir, use o código abaixo (Pix Copia e Cola).</p>

          <label class="pix-rotulo" for="pixCodigo">Pix Copia e Cola</label>
          <textarea id="pixCodigo" class="pix-codigo" rows="4" readonly><?= e($payload) ?></textarea>
          <button type="button" class="btn btn-primario" id="pixCopiar" style="width:100%;">Copiar código Pix</button>

          <p class="pix-legenda" style="margin-top:14px;">Ou pague para a chave Pix:<br><strong class="pix-chave"><?= e(PIX_CHAVE) ?></strong><br><small><?= e(PIX_NOME) ?></small></p>
        <?php else: ?>
          <div class="alerta alerta-erro">O Pix da loja ainda não foi configurado. Chame a gente no WhatsApp que combinamos o pagamento.</div>
        <?php endif; ?>

        <a href="<?= e($zapUrl) ?>" class="btn btn-fio" target="_blank" rel="noopener" style="width:100%;margin-top:14px;">📲 Enviar comprovante no WhatsApp</a>
        <p class="pix-legenda" style="margin-top:12px;">Assim que confirmarmos o pagamento, o pedido entra em produção.</p>
      <?php endif; ?>

      <a href="<?= BASE_PATH ?>/index.php" class="btn btn-primario" style="margin-top:16px;">Voltar à loja</a>
    </div>
  </div>
</section>

<?php if ($pedido): ?>
<script>
  // O pedido já foi registrado: o carrinho pode ser esvaziado.
  try { localStorage.removeItem('studioverso_carrinho'); } catch (e) {}
</script>
<?php endif; ?>

<?php if ($pedido && $payload !== '' && $statusPg !== 'aprovado'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<script>
(function () {
  var codigo = document.getElementById('pixCodigo');
  var btn = document.getElementById('pixCopiar');
  var qr = document.getElementById('pixQr');

  // QR Code desenhado como SVG: cada quadradinho tem tamanho exato e há margem branca em volta
  // (bancos leem melhor assim). Se a biblioteca não carregar, o Copia e Cola continua valendo.
  try {
    var q = qrcode(0, 'M');
    q.addData(codigo.value);
    q.make();
    var n = q.getModuleCount(), m = 4, tam = n + 2 * m, d = '';
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (q.isDark(r, c)) d += 'M' + (c + m) + ' ' + (r + m) + 'h1v1h-1z';
      }
    }
    qr.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + tam + ' ' + tam + '" width="240" height="240" shape-rendering="crispEdges" role="img" aria-label="QR Code do Pix">'
      + '<rect width="' + tam + '" height="' + tam + '" fill="#fff"/><path d="' + d + '" fill="#000"/></svg>';
  } catch (e) {
    qr.style.display = 'none';
  }

  btn.addEventListener('click', function () {
    function ok() { btn.textContent = '✔ Código copiado!'; setTimeout(function () { btn.textContent = 'Copiar código Pix'; }, 2500); }
    codigo.focus(); codigo.select(); codigo.setSelectionRange(0, 99999);
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(codigo.value).then(ok, function () { try { document.execCommand('copy'); ok(); } catch (e) {} });
    } else {
      try { document.execCommand('copy'); ok(); } catch (e) {}
    }
  });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
