<?php
require_once __DIR__ . '/config.php';
$usuario = usuarioLogado();
$tituloPagina = 'Finalizar compra';
require __DIR__ . '/includes/header.php';
?>

<section class="secao">
  <div class="container pagina-form">
    <div class="card">
      <h2>Finalizar compra</h2>
      <div id="resumoItens" style="margin-bottom:20px;"></div>
      <p class="subtotal"><span>Total</span><span id="totalFinal">R$ 0,00</span></p>

      <form id="formPedido" action="<?= BASE_PATH ?>/processar_pedido.php" method="POST">
        <div class="campo">
          <label>Nome completo</label>
          <input type="text" name="nome" required value="<?= $usuario ? e($usuario['nome']) : '' ?>">
        </div>
        <div class="campo">
          <label>E-mail</label>
          <input type="email" name="email" required value="<?= $usuario ? e($usuario['email']) : '' ?>">
        </div>
        <div class="campo">
          <label>WhatsApp</label>
          <input type="text" name="whatsapp" placeholder="(11) 90000-0000" required>
        </div>
        <input type="hidden" name="itens_json" id="itensJson">
        <button type="submit" class="btn btn-primario" style="width:100%;">Ir para pagamento</button>
        <p class="selo-pagamento">💜 Pagamento por Pix: depois de finalizar, você recebe o QR Code e o código para pagar</p>
      </form>
    </div>
  </div>
</section>

<script>
(function () {
  const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let itens = [];
  try { itens = JSON.parse(localStorage.getItem('studioverso_carrinho')) || []; } catch (e) { itens = []; }
  const resumo = document.getElementById('resumoItens');
  const totalEl = document.getElementById('totalFinal');

  if (itens.length === 0) {
    resumo.innerHTML = '<p>Seu carrinho está vazio. <a href="<?= BASE_PATH ?>/index.php#catalogo">Voltar ao catálogo</a></p>';
    document.getElementById('formPedido').style.display = 'none';
  } else {
    resumo.innerHTML = itens.map(i =>
      `<div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:6px;">
        <span>${i.qtd}x ${esc(i.nome)}${i.resumo ? `<br><small style="color:var(--tinta-suave)">${esc(i.resumo)}</small>` : ''}</span><span>R$ ${(i.qtd * i.preco).toFixed(2).replace('.', ',')}</span>
      </div>`
    ).join('');
    const total = itens.reduce((s, i) => s + i.qtd * i.preco, 0);
    totalEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    document.getElementById('itensJson').value = JSON.stringify(itens.map(i => ({ id: i.id, qtd: i.qtd, pers: i.pers || null })));
  }

  // O carrinho NÃO é esvaziado aqui: ele só é limpo na página de retorno (pedido_retorno.php),
  // depois que o pagamento foi feito. Assim, o carrinho só é limpo quando o pedido é registrado.
  // Também evita pedido duplicado por clique duplo no botão.
  document.getElementById('formPedido')?.addEventListener('submit', ev => {
    const btn = ev.target.querySelector('button[type=submit]');
    if (btn) { btn.disabled = true; btn.textContent = 'Abrindo o pagamento...'; }
  });
  // Se o cliente voltar com o botão "voltar" do navegador, o botão precisa estar clicável de novo.
  window.addEventListener('pageshow', () => {
    const btn = document.querySelector('#formPedido button[type=submit]');
    if (btn) { btn.disabled = false; btn.textContent = 'Ir para pagamento'; }
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
