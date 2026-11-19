(function () {
  var E = window.EI;
  E.api('/categorias').then(function (d) {
    var cats = (d.categorias || []).slice(0, 8);
    E.$('#cats').innerHTML = cats.map(function (c) {
      return '<a class="cat" href="catalogo.php?categoria=' + encodeURIComponent(c.slug) + '"><span class="cat-ic">' + E.ic(E.catIcon(c.slug)) + '</span><b>' + E.esc(c.nome) + '</b><small>' + c.total_produtos + ' produto' + (c.total_produtos === 1 ? '' : 's') + '</small></a>';
    }).join('') || '';
  }).catch(function () { E.$('#cats').innerHTML = ''; });

  var dest = E.$('#destaques'); dest.innerHTML = E.skeletons(4);
  E.api('/produtos/destaques').then(function (d) {
    var p = d.produtos || [];
    dest.innerHTML = p.length ? p.slice(0, 8).map(E.productCard).join('') : '<div class="empty" style="grid-column:1/-1">Nenhum produto em destaque ainda.</div>';
  }).catch(function (e) { dest.innerHTML = '<div class="empty" style="grid-column:1/-1">' + E.esc(e.message) + '</div>'; });

  E.api('/servicos').then(function (d) {
    var s = (d.servicos || []).slice(0, 3), box = E.$('#servicos');
    if (!s.length) { box.parentNode.hidden = true; return; }
    box.innerHTML = s.map(function (x) {
      return '<article class="svc"><span class="cat-ic">' + E.ic('bolt') + '</span><h3>' + E.esc(x.nome) + '</h3><p>' + E.esc((x.descricao || '').slice(0, 120)) + (x.descricao && x.descricao.length > 120 ? '…' : '') + '</p>' +
        '<div class="from">' + (x.tipo_preco === 'preco_inicial' ? '<small>a partir de</small>' + E.money(x.preco_inicial) : 'Sob orçamento') + '</div>' +
        '<a class="btn btn-ghost btn-sm" href="servicos.php">Solicitar</a></article>';
    }).join('');
  }).catch(function () { E.$('#servicos').innerHTML = ''; });
})();
