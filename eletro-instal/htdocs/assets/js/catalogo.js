(function () {
  var E = window.EI, $ = E.$;
  var S = { categoria: '', busca: '', marca: '', preco_min: '', preco_max: '', em_estoque: '', order: 'recente', page: 1 };
  var cats = [], timer;

  function fromUrl() {
    var q = new URLSearchParams(location.search);
    Object.keys(S).forEach(function (k) { if (q.get(k)) S[k] = q.get(k); });
    S.page = parseInt(S.page, 10) || 1;
  }
  function toUrl() {
    var q = new URLSearchParams();
    Object.keys(S).forEach(function (k) { if (S[k] && !(k === 'page' && S[k] === 1) && !(k === 'order' && S[k] === 'recente')) q.set(k, S[k]); });
    history.replaceState(null, '', location.pathname + (q.toString() ? '?' + q : ''));
  }
  function syncInputs() {
    $('#pmin').value = S.preco_min; $('#pmax').value = S.preco_max; $('#fMarca').value = S.marca;
    $('#fEstoque').checked = !!S.em_estoque; $('#fOrder').value = S.order;
    var s = document.querySelector('.search input'); if (s && S.busca) s.value = S.busca;
  }
  function renderCats() {
    var total = cats.reduce(function (a, c) { return a + c.total_produtos; }, 0);
    var h = '<label><input type="radio" name="cat" value=""' + (S.categoria ? '' : ' checked') + '><span>Todas</span><small>' + total + '</small></label>';
    cats.forEach(function (c) { h += '<label><input type="radio" name="cat" value="' + E.esc(c.slug) + '"' + (S.categoria === c.slug ? ' checked' : '') + '><span>' + E.esc(c.nome) + '</span><small>' + c.total_produtos + '</small></label>'; });
    $('#fCats').innerHTML = h;
    E.$$('input[name=cat]').forEach(function (r) { r.addEventListener('change', function () { S.categoria = r.value; S.page = 1; load(); }); });
  }
  function load() {
    toUrl();
    var cur = cats.filter(function (c) { return c.slug === S.categoria; })[0];
    $('#pageTitle').textContent = S.busca ? 'Resultados para “' + S.busca + '”' : (cur ? cur.nome : 'Catálogo');
    document.title = (cur ? cur.nome : 'Catálogo') + ' — Eletro Instal';
    $('#crumbCat').textContent = cur ? '/ ' + cur.nome : '';
    $('#grid').innerHTML = E.skeletons(6);
    var q = new URLSearchParams({ limit: 12, page: S.page, order: S.order });
    ['categoria', 'busca', 'marca', 'preco_min', 'preco_max', 'em_estoque'].forEach(function (k) { if (S[k]) q.set(k, S[k]); });
    E.api('/produtos?' + q).then(function (d) {
      var p = d.produtos, pg = d.paginacao;
      $('#count').textContent = pg.total + ' produto' + (pg.total === 1 ? '' : 's') + ' encontrado' + (pg.total === 1 ? '' : 's');
      $('#grid').innerHTML = p.length ? p.map(E.productCard).join('') :
        '<div class="empty" style="grid-column:1/-1">' + E.ic('search') + '<p><b>Nenhum produto encontrado</b></p><p>Tente remover alguns filtros ou buscar outro termo.</p></div>';
      var h = '';
      if (pg.pages > 1) for (var i = 1; i <= pg.pages; i++) h += '<button class="' + (i === pg.page ? 'on' : '') + '" data-p="' + i + '" aria-label="Página ' + i + '">' + i + '</button>';
      $('#pager').innerHTML = h;
    }).catch(function (e) { $('#grid').innerHTML = '<div class="empty" style="grid-column:1/-1">' + E.esc(e.message) + '</div>'; $('#count').textContent = ''; });
  }
  function debounce() { clearTimeout(timer); timer = setTimeout(function () { S.page = 1; load(); }, 400); }

  fromUrl();
  Promise.all([E.api('/categorias'), E.api('/marcas')]).then(function (r) {
    cats = r[0].categorias || [];
    $('#fMarca').innerHTML += (r[1].marcas || []).map(function (m) { return '<option>' + E.esc(m) + '</option>'; }).join('');
    renderCats(); syncInputs(); load();
  }).catch(function () { syncInputs(); load(); });

  $('#pmin').addEventListener('input', function () { S.preco_min = this.value; debounce(); });
  $('#pmax').addEventListener('input', function () { S.preco_max = this.value; debounce(); });
  $('#fMarca').addEventListener('change', function () { S.marca = this.value; S.page = 1; load(); });
  $('#fEstoque').addEventListener('change', function () { S.em_estoque = this.checked ? '1' : ''; S.page = 1; load(); });
  $('#fOrder').addEventListener('change', function () { S.order = this.value; S.page = 1; load(); });
  $('#fClear').addEventListener('click', function () { var o = S.order; S = { categoria: '', busca: '', marca: '', preco_min: '', preco_max: '', em_estoque: '', order: o, page: 1 }; var s = document.querySelector('.search input'); if (s) s.value = ''; renderCats(); syncInputs(); load(); });
  $('#fToggle').addEventListener('click', function () { $('#filters').classList.toggle('open'); });
  $('#pager').addEventListener('click', function (e) { var b = e.target.closest('[data-p]'); if (b) { S.page = +b.dataset.p; load(); window.scrollTo({ top: 0, behavior: 'smooth' }); } });
})();
