(function () {
  var E = window.EI, $ = E.$, root = $('#root'), cart = null, picker = null, metodo = 'pix', busy = false;

  function load() {
    return E.api('/carrinho').then(function (d) { cart = d; E.setCartCount(d.total_itens); render(); })
      .catch(function (e) { root.innerHTML = '<div class="empty">' + E.esc(e.message) + '</div>'; });
  }
  function act(p) {
    if (busy) return; busy = true;
    p.then(function (d) { cart = d; E.setCartCount(d.total_itens); render(); })
      .catch(function (e) { E.toast(e.message, 'err'); load(); })
      .then(function () { busy = false; }, function () { busy = false; });
  }

  function itemsHTML() {
    return cart.itens.map(function (i) {
      return '<div class="citem" data-id="' + i.id + '"><a href="produto.php?slug=' + encodeURIComponent(i.slug) + '"><img src="' + E.esc(E.img(i.imagem)) + '" alt=""></a>' +
        '<div><h3><a href="produto.php?slug=' + encodeURIComponent(i.slug) + '">' + E.esc(i.nome) + '</a></h3><div class="sku">SKU ' + E.esc(i.sku) + ' · ' + E.money(i.preco_unitario) + ' cada</div>' +
        '<div style="display:flex;align-items:center"><div class="qty"><button data-q="-1" aria-label="Diminuir">' + E.ic('minus') + '</button><input value="' + i.quantidade + '" data-qi inputmode="numeric" aria-label="Quantidade"><button data-q="1" aria-label="Aumentar">' + E.ic('plus') + '</button></div>' +
        '<button class="rm" data-rm>' + E.ic('trash') + ' Remover</button></div>' +
        (i.disponivel ? '' : '<div class="warn-line">Estoque disponível: ' + i.estoque + ' un. Ajuste a quantidade.</div>') + '</div>' +
        '<div class="sub">' + E.money(i.subtotal) + '</div></div>';
    }).join('');
  }

  function summaryHTML() {
    var c = cart;
    return '<aside class="panel sum"><h2>Resumo do pedido</h2>' +
      '<div class="line"><span>Subtotal (' + c.total_itens + ' ite' + (c.total_itens === 1 ? 'm' : 'ns') + ')</span><span>' + E.money(c.subtotal) + '</span></div>' +
      (c.desconto ? '<div class="line disc"><span>Desconto</span><span>-' + E.money(c.desconto) + '</span></div>' : '') +
      '<div class="line"><span>Frete</span><span>' + (c.frete ? E.money(c.frete) : 'Grátis') + '</span></div>' +
      '<div class="line total"><span>Total</span><span>' + E.money(c.total) + '</span></div>' +
      (c.cupom ? '<div class="coupon-on"><span>' + E.ic('tag') + ' Cupom <b>' + E.esc(c.cupom.codigo) + '</b> aplicado</span><button data-rmcupom class="muted" style="font-size:.85rem">Remover</button></div>' :
        '<form class="coupon" id="cupomForm"><input class="input" id="cupom" placeholder="Cupom de desconto" aria-label="Cupom" autocapitalize="characters"><button class="btn btn-dark btn-sm" style="height:42px">Aplicar</button></form>') +
      '<div id="sumAction"></div></aside>';
  }

  function checkoutHTML() {
    if (!E.Session.token()) {
      return '<div class="panel" style="margin-top:20px"><h2>Finalizar compra</h2><div class="alert info">Para finalizar o pedido, entre na sua conta ou crie uma nova. Seu carrinho será mantido.</div>' +
        '<div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-primary" href="login.php?next=carrinho.php">Entrar</a><a class="btn btn-ghost" href="registro.php?next=carrinho.php">Criar conta</a></div></div>';
    }
    return '<div class="panel" style="margin-top:20px" id="ck"><h2>Entrega</h2><div id="addr"></div>' +
      '<h2 style="margin-top:26px">Forma de pagamento</h2><div class="pay">' +
      [['pix', 'PIX'], ['transferencia', 'Transferência'], ['dinheiro', 'Dinheiro']].map(function (m) { return '<label><input type="radio" name="metodo" value="' + m[0] + '"' + (m[0] === metodo ? ' checked' : '') + '>' + m[1] + '</label>'; }).join('') + '</div>' +
      '<div class="hint" style="margin:-6px 0 16px">O pagamento é combinado com a loja após o pedido (você receberá as instruções).</div>' +
      '<div class="field"><label class="f">Observações (opcional)</label><textarea class="input" id="obs" maxlength="500" placeholder="Ex.: horário de entrega, ponto de referência…"></textarea></div>' +
      '<div id="ckErr"></div><button class="btn btn-primary btn-block" id="finish">Finalizar pedido · ' + E.money(cart.total) + '</button></div>';
  }

  function render() {
    if (!cart.itens.length) {
      root.innerHTML = '<div class="empty">' + E.ic('cart') + '<p><b>Seu carrinho está vazio</b></p><p>Explore o catálogo e adicione produtos.</p><p style="margin-top:18px"><a class="btn btn-primary" href="catalogo.php">Ver catálogo</a></p></div>';
      return;
    }
    root.innerHTML = '<div class="cart-layout"><div><div class="panel"><h2>Produtos</h2>' + itemsHTML() + '</div>' + checkoutHTML() + '</div>' + summaryHTML() + '</div>';

    var addr = $('#addr');
    if (addr) E.AddressPicker(addr).then(function (p) { picker = p; });
    E.$$('input[name=metodo]').forEach(function (r) { r.onchange = function () { metodo = r.value; }; });
    var fin = $('#finish'); if (fin) fin.onclick = finish;
    if (cart.itens.some(function (i) { return !i.disponivel; }) && fin) { fin.disabled = true; $('#ckErr').innerHTML = '<div class="alert err">Ajuste as quantidades acima: alguns itens excedem o estoque.</div>'; }
  }

  function finish() {
    var err = $('#ckErr'), btn = $('#finish'); err.innerHTML = '';
    var addr;
    try { addr = picker.value(); } catch (e) { err.innerHTML = '<div class="alert err">' + E.esc(e.message) + '</div>'; return; }
    btn.disabled = true; btn.textContent = 'Enviando pedido…';
    var body = Object.assign({ metodo: metodo, observacoes: ($('#obs').value || '').trim() }, addr);
    E.api('/checkout', { method: 'POST', body: body }).then(function (d) {
      E.setCartCount(0); success(d);
    }).catch(function (e) {
      err.innerHTML = '<div class="alert err">' + E.esc(e.message) + '</div>';
      btn.disabled = false; btn.textContent = 'Finalizar pedido · ' + E.money(cart.total);
      if (e.status === 409) load();
    });
  }

  function success(d) {
    var p = d.pedido, wa = d.whatsapp ? E.waLink(d.whatsapp, 'Olá! Acabei de fazer o pedido ' + p.numero + ' (' + E.money(p.total) + ') e quero pagar via ' + (E.METODOS[p.metodo] || p.metodo) + '.') : null;
    root.innerHTML = '<div class="panel success" style="max-width:640px;margin:0 auto"><div class="ok-ic">' + E.ic('check') + '</div><h2>Pedido realizado!</h2><p class="muted">Guardamos seus produtos. Número do pedido:</p><div class="num">' + E.esc(p.numero) + '</div>' +
      '<p>Total: <b>' + E.money(p.total) + '</b> · Pagamento: <b>' + E.esc(E.METODOS[p.metodo] || p.metodo) + '</b></p>' +
      '<div class="alert info" style="text-align:left;margin:22px 0">' + (wa ? 'Envie uma mensagem para a loja para receber as instruções de pagamento. O pedido é confirmado após a aprovação do pagamento.' : 'A equipe da loja verá seu pedido e entrará em contato com as instruções de pagamento. Acompanhe o status em “Minha conta”.') + '</div>' +
      '<div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">' + (wa ? '<a class="btn btn-wa" target="_blank" rel="noopener" href="' + E.esc(wa) + '">' + E.ic('whatsapp') + ' Combinar pagamento</a>' : '') +
      '<a class="btn btn-primary" href="conta.php#pedidos">Acompanhar pedido</a><a class="btn btn-ghost" href="catalogo.php">Continuar comprando</a></div></div>';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  root.addEventListener('click', function (e) {
    var row = e.target.closest('.citem'), t = e.target;
    if (row) {
      var id = row.dataset.id, inp = $('[data-qi]', row), qty = parseInt(inp.value, 10) || 1;
      var b = t.closest('[data-q]');
      if (b) act(E.api('/carrinho/itens/' + id, { method: 'PATCH', body: { quantidade: Math.max(1, qty + parseInt(b.dataset.q, 10)) } }));
      if (t.closest('[data-rm]')) act(E.api('/carrinho/itens/' + id, { method: 'DELETE' }));
    }
    if (t.closest('[data-rmcupom]')) act(E.api('/carrinho/cupom', { method: 'DELETE' }));
  });
  root.addEventListener('change', function (e) {
    if (e.target.matches('[data-qi]')) { var id = e.target.closest('.citem').dataset.id; act(E.api('/carrinho/itens/' + id, { method: 'PATCH', body: { quantidade: Math.max(1, parseInt(e.target.value, 10) || 1) } })); }
  });
  root.addEventListener('submit', function (e) {
    if (e.target.id === 'cupomForm') {
      e.preventDefault(); var c = $('#cupom').value.trim(); if (!c) return;
      E.api('/carrinho/cupom', { method: 'POST', body: { codigo: c } }).then(function (d) { cart = d; render(); E.toast('Cupom aplicado!'); }).catch(function (er) { E.toast(er.message, 'err'); });
    }
  });
  load();
})();
