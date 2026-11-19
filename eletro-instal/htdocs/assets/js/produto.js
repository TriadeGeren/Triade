(function () {
  var E = window.EI, $ = E.$;
  var slug = new URLSearchParams(location.search).get('slug');
  if (!slug) { location.replace('catalogo.php'); return; }

  E.api('/produtos/' + encodeURIComponent(slug)).then(function (d) {
    var p = d.produto, imgs = p.imagens.length ? p.imagens : [{ caminho: '', alt: p.nome }];
    $('#crumbCat').innerHTML = '/ <a href="catalogo.php?categoria=' + encodeURIComponent(p.categoria_slug) + '">' + E.esc(p.categoria_nome) + '</a> / ' + E.esc(p.nome);
    var st = p.estoque < 1 ? ['none', 'Indisponível no momento'] : (p.estoque <= 5 ? ['low', 'Últimas ' + p.estoque + ' unidade' + (p.estoque > 1 ? 's' : '')] : ['', 'Em estoque']);

    $('#pd').innerHTML = '<div class="pd"><div class="gallery"><div class="main"><img id="mainImg" src="' + E.esc(E.img(imgs[0].caminho)) + '" alt="' + E.esc(p.nome) + '"></div>' +
      (imgs.length > 1 ? '<div class="thumbs">' + imgs.map(function (i, k) { return '<button class="' + (k ? '' : 'on') + '" data-src="' + E.esc(i.caminho) + '" aria-label="Imagem ' + (k + 1) + '"><img src="' + E.esc(i.caminho) + '" alt=""></button>'; }).join('') + '</div>' : '') + '</div>' +
      '<div><span class="pbrand">' + E.esc(p.marca || '') + '</span><h1>' + E.esc(p.nome) + '</h1>' +
      '<div class="meta"><span>SKU: ' + E.esc(p.sku) + '</span><span>' + E.esc(p.categoria_nome) + '</span></div>' +
      '<div><span class="big">' + E.money(p.preco_atual) + '</span>' + (p.em_promocao ? '<span class="price-old" style="margin-left:12px;font-size:1.05rem">' + E.money(p.preco) + '</span><span class="save">-' + p.desconto_pct + '%</span>' : '') + '</div>' +
      '<p class="muted" style="margin-top:14px">' + E.esc(p.descricao_curta || '') + '</p>' +
      '<div class="stock ' + st[0] + '"><i></i>' + st[1] + '</div>' +
      (p.estoque > 0 ? '<div class="buy"><div class="qty"><button type="button" id="qm" aria-label="Diminuir">' + E.ic('minus') + '</button><input id="qty" type="number" min="1" max="' + p.estoque + '" value="1" aria-label="Quantidade"><button type="button" id="qp" aria-label="Aumentar">' + E.ic('plus') + '</button></div>' +
        '<button class="btn btn-primary" id="add">' + E.ic('cart') + ' Adicionar ao carrinho</button><button class="btn btn-ghost" id="buy">Comprar agora</button></div>' : '<a class="btn btn-ghost" href="catalogo.php?categoria=' + encodeURIComponent(p.categoria_slug) + '">Ver produtos similares</a>') +
      '<div class="perks"><div>' + E.ic('lock') + 'Cadastro protegido conforme a LGPD</div><div>' + E.ic('headset') + 'Dúvidas? Fale com a equipe</div></div>' +
      (p.descricao ? '<div class="desc"><h2>Descrição</h2><div>' + E.esc(p.descricao) + '</div></div>' : '') + '</div></div>';

    var qty = $('#qty');
    function q() { return Math.min(p.estoque, Math.max(1, parseInt(qty.value, 10) || 1)); }
    if (qty) {
      $('#qm').onclick = function () { qty.value = Math.max(1, q() - 1); };
      $('#qp').onclick = function () { qty.value = Math.min(p.estoque, q() + 1); };
      qty.onchange = function () { qty.value = q(); };
      $('#add').onclick = function () { E.addToCart(p.id, q(), this).catch(function () {}); };
      $('#buy').onclick = function () { E.addToCart(p.id, q(), this).then(function () { location.href = 'carrinho.php'; }).catch(function () {}); };
    }
    E.$$('.thumbs button').forEach(function (b) {
      b.onclick = function () { $('#mainImg').src = b.dataset.src; E.$$('.thumbs button').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); };
    });
    if (d.relacionados && d.relacionados.length) {
      $('#rel').innerHTML = d.relacionados.map(function (r) { r.estoque = r.estoque || 0; r.categoria_nome = p.categoria_nome; return E.productCard(r); }).join('');
      $('#relSec').hidden = false;
    }
  }).catch(function (e) {
    $('#pd').innerHTML = '<div class="center-msg"><h1>' + (e.status === 404 ? '404' : 'Ops') + '</h1><p class="muted">' + E.esc(e.status === 404 ? 'Produto não encontrado ou indisponível.' : e.message) + '</p><p style="margin-top:20px"><a class="btn btn-primary" href="catalogo.php">Voltar ao catálogo</a></p></div>';
  });
})();
