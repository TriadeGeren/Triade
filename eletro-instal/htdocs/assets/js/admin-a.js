/* Painel: Dashboard, Pedidos, Produtos */
(function () {
  'use strict';
  var E = window.EI, A = window.EIA, $ = E.$, esc = E.esc, money = E.money;
  if (!A) return;
  var COLOR = { warn: '#ffc43d', info: '#19d3ff', vio: '#9b8cff', ok: '#2ee59d', bad: '#ff5d6c', mut: '#64748b' };
  var PED_ST = ['aguardando_pagamento', 'pagamento_aprovado', 'em_preparacao', 'enviado', 'em_transporte', 'entregue', 'cancelado'];

  /* ================= DASHBOARD ================= */
  function delta(cur, prev) {
    if (!prev) return cur ? '<span class="delta up">novo no período</span>' : '<span class="delta flat">sem dados anteriores</span>';
    var p = (cur - prev) / prev * 100, c = p > 0.5 ? 'up' : (p < -0.5 ? 'down' : 'flat');
    return '<span class="delta ' + c + '">' + (p > 0 ? '▲ +' : (p < 0 ? '▼ ' : '')) + p.toFixed(1).replace('.', ',') + '% vs. mês anterior</span>';
  }
  A.add({ id: 'dashboard', label: 'Dashboard', icon: 'chart', perm: 'dashboard.ver', render: function (v) {
    return E.api('/admin/dashboard').then(function (d) {
      var k = d.kpis;
      v.innerHTML =
        '<div class="kpis">' +
        '<div class="card kpi"><span class="ico">' + E.ic('dollar') + '</span><div><small>Faturamento do mês</small><b>' + money(k.faturamento_mes) + '</b>' + delta(k.faturamento_mes, k.faturamento_mes_anterior) + '</div></div>' +
        '<div class="card kpi"><span class="ico g">' + E.ic('box') + '</span><div><small>Pedidos no mês</small><b>' + k.pedidos_mes + '</b><span class="delta flat">exceto cancelados</span></div></div>' +
        '<div class="card kpi"><span class="ico v">' + E.ic('tag') + '</span><div><small>Ticket médio</small><b>' + money(k.ticket_medio) + '</b><span class="delta flat">por pedido</span></div></div>' +
        '<div class="card kpi"><span class="ico o">' + E.ic('wallet') + '</span><div><small>Aguardando pagamento</small><b>' + k.pedidos_aguardando_pagamento + '</b><a class="delta up" href="#pedidos">ver pedidos →</a></div></div></div>' +
        '<div class="mini">' +
        '<a class="card" href="#servicos"><span>Solicitações abertas</span><b>' + k.solicitacoes_abertas + '</b></a>' +
        '<a class="card ' + (k.estoque_baixo_total ? 'alert-on' : '') + '" href="#estoque"><span>Estoque baixo</span><b style="color:' + (k.estoque_baixo_total ? 'var(--warn)' : 'inherit') + '">' + k.estoque_baixo_total + '</b></a>' +
        '<div class="card"><span>Clientes</span><b>' + k.clientes_total + '</b></div><div class="card"><span>Produtos ativos</span><b>' + k.produtos_ativos + '</b></div></div>' +
        '<div class="charts"><div class="card"><h3>Faturamento mensal</h3><div class="sub">Últimos 6 meses, sem pedidos cancelados</div><div class="chart-box"><canvas id="cFat"></canvas></div></div>' +
        '<div class="card"><h3>Pedidos por status</h3><div class="sub">Todos os pedidos</div><div class="chart-box" id="stBox"><canvas id="cSt"></canvas></div></div></div>' +
        '<div class="charts c3"><div class="card"><h3>Receitas × despesas</h3><div class="sub">Valores efetivamente recebidos e pagos</div><div class="chart-box sm"><canvas id="cFin"></canvas></div></div>' +
        '<div class="card"><h3>Mais vendidos</h3><div class="sub">Pedidos com pagamento aprovado</div><div id="top"></div></div>' +
        '<div class="card"><h3>Estoque baixo</h3><div class="sub">No mínimo ou abaixo</div><div id="low"></div></div></div>' +
        '<div class="card"><h3>Últimos pedidos</h3><div class="sub">&nbsp;</div><div id="last"></div></div>';

      A.chart($('#cFat'), { type: 'bar', data: { labels: d.faturamento_mensal.map(function (m) { return A.mes(m.mes); }), datasets: [{ label: 'Faturamento', data: d.faturamento_mensal.map(function (m) { return m.faturamento; }), backgroundColor: '#19d3ff', borderRadius: 8, maxBarThickness: 46 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + money(c.parsed.y); } } } }, scales: { y: { beginAtZero: true, ticks: { callback: function (x) { return 'R$ ' + x.toLocaleString('pt-BR'); } } }, x: { grid: { display: false } } } } });

      if (d.status_pedidos.length) A.chart($('#cSt'), { type: 'doughnut', data: { labels: d.status_pedidos.map(function (s) { return E.label(s.status); }), datasets: [{ data: d.status_pedidos.map(function (s) { return +s.total; }), backgroundColor: d.status_pedidos.map(function (s) { return COLOR[(E.ST[s.status] || [0, 'mut'])[1]]; }), borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14 } } } } });
      else $('#stBox').innerHTML = '<div class="empty-s">Ainda não há pedidos.</div>';

      A.chart($('#cFin'), { type: 'bar', data: { labels: d.resumo_financeiro.map(function (m) { return A.mes(m.mes); }), datasets: [
        { label: 'Receitas', data: d.resumo_financeiro.map(function (m) { return m.receita_recebida; }), backgroundColor: '#2ee59d', borderRadius: 5, maxBarThickness: 18 },
        { label: 'Despesas', data: d.resumo_financeiro.map(function (m) { return m.despesas_pagas; }), backgroundColor: '#ff5d6c', borderRadius: 5, maxBarThickness: 18 },
        { label: 'Resultado', type: 'line', data: d.resumo_financeiro.map(function (m) { return m.resultado; }), borderColor: '#ffc43d', backgroundColor: '#ffc43d', tension: .35, pointRadius: 3 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } }, scales: { x: { grid: { display: false } } } } });

      var max = d.top_produtos.reduce(function (a, p) { return Math.max(a, +p.total_vendido); }, 1);
      $('#top').innerHTML = d.top_produtos.length ? '<div class="list-s">' + d.top_produtos.map(function (p) { return '<div style="display:block"><div style="display:flex;justify-content:space-between;gap:8px"><span>' + esc(p.nome) + '</span><b>' + p.total_vendido + '</b></div><div class="bar-h"><i style="width:' + (p.total_vendido / max * 100) + '%"></i></div></div>'; }).join('') + '</div>' : '<div class="empty-s">Sem vendas aprovadas ainda.</div>';
      $('#low').innerHTML = d.estoque_baixo.length ? '<div class="list-s">' + d.estoque_baixo.slice(0, 6).map(function (p) { return '<div><span>' + esc(p.nome) + '</span><b class="' + (+p.quantidade === 0 ? 'stk-zero' : 'stk-low') + '">' + p.quantidade + ' / ' + p.estoque_minimo + '</b></div>'; }).join('') + '</div>' : '<div class="empty-s">Estoque saudável 👍</div>';
      $('#last').innerHTML = A.table([
        { h: 'Pedido', f: function (r) { return '<b>' + esc(r.numero) + '</b>'; } }, { h: 'Cliente', f: function (r) { return esc(r.cliente_nome); } },
        { h: 'Data', f: function (r) { return E.dt(r.criado_em); } }, { h: 'Total', cls: 'num', f: function (r) { return money(r.total); } }, { h: 'Status', f: function (r) { return E.badge(r.status); } }], d.ultimos_pedidos, 'Nenhum pedido ainda.');
    });
  } });

  /* ================= PEDIDOS ================= */
  A.add({ id: 'pedidos', label: 'Pedidos', icon: 'box', perm: 'pedidos.ver', render: function (v) {
    var S = { status: '', busca: '', page: 1 };
    v.innerHTML = '<div class="bar2"><input class="input" id="q" type="search" placeholder="Buscar por nº, cliente ou e-mail…"><select class="input" id="st"><option value="">Todos os status</option>' + PED_ST.map(function (s) { return '<option value="' + s + '">' + E.label(s) + '</option>'; }).join('') + '</select></div><div id="list"></div><div id="pgr"></div>';
    function load() {
      var q = new URLSearchParams({ page: S.page, limit: 15 }); if (S.status) q.set('status', S.status); if (S.busca) q.set('busca', S.busca);
      return E.api('/admin/pedidos?' + q).then(function (d) {
        $('#list').innerHTML = A.table([
          { h: 'Pedido', f: function (r) { return '<b>' + esc(r.numero) + '</b>'; } },
          { h: 'Cliente', f: function (r) { return '<span class="nm">' + esc(r.cliente_nome) + '</span><br><small>' + esc(r.cliente_email) + '</small>'; } },
          { h: 'Data', f: function (r) { return E.dt(r.criado_em); } }, { h: 'Pagamento', f: function (r) { return esc(E.METODOS[r.metodo] || r.metodo || '—'); } },
          { h: 'Total', cls: 'num', f: function (r) { return money(r.total); } }, { h: 'Status', f: function (r) { return E.badge(r.status); } },
          { h: '', cls: 'r', f: function (r) { return '<div class="acts">' + A.iconBtn('eye', 'data-open="' + r.id + '"', 'Abrir pedido') + '</div>'; } }], d.pedidos, 'Nenhum pedido encontrado.');
        A.pager(d.paginacao, $('#pgr'), function (p) { S.page = p; load(); });
      });
    }
    $('#q').oninput = A.debounce(function () { S.busca = this.value.trim(); S.page = 1; load(); }); $('#st').onchange = function () { S.status = this.value; S.page = 1; load(); };
    v.onclick = function (e) { var b = e.target.closest('[data-open]'); if (b) openOrder(b.dataset.open, load); };
    return load();
  } });

  function openOrder(id, after) {
    E.api('/admin/pedidos/' + id).then(function (d) {
      var o = d.pedido, canEdit = E.Session.can('pedidos.atualizar');
      var m = E.modal({ title: 'Pedido ' + o.numero, size: 'lg', body:
        '<div style="display:flex;gap:12px;align-items:center;margin-bottom:18px">' + E.badge(o.status) + '<span class="muted">' + E.dt(o.criado_em) + '</span></div>' +
        '<dl class="kv"><dt>Cliente</dt><dd>' + esc(o.cliente_nome) + ' · ' + esc(o.cliente_email) + (o.cliente_telefone ? ' · ' + esc(o.cliente_telefone) : '') + '</dd>' +
        '<dt>Entrega</dt><dd>' + esc(o.entrega_destinatario || '') + ' — ' + esc(o.entrega_logradouro || '') + ', ' + esc(o.entrega_numero || '') + ' · ' + esc(o.entrega_bairro || '') + ' · ' + esc(o.entrega_cidade || '') + '/' + esc(o.entrega_uf || '') + ' · CEP ' + esc(o.entrega_cep || '') + '</dd>' +
        '<dt>Pagamento</dt><dd>' + (d.pagamento ? esc(E.METODOS[d.pagamento.metodo] || d.pagamento.metodo) + ' ' + E.badge(d.pagamento.status) : '—') + '</dd>' + (o.observacoes ? '<dt>Observações</dt><dd>' + esc(o.observacoes) + '</dd>' : '') + '</dl>' +
        A.table([{ h: 'Produto', f: function (i) { return esc(i.nome_produto) + '<br><small>' + esc(i.sku) + '</small>'; } }, { h: 'Qtd', f: function (i) { return i.quantidade; } }, { h: 'Unit.', cls: 'num', f: function (i) { return money(i.preco_unitario); } }, { h: 'Subtotal', cls: 'num', f: function (i) { return money(i.subtotal); } }], d.itens) +
        '<div style="text-align:right;margin:14px 0;line-height:1.8"><span class="muted">Subtotal ' + money(o.subtotal) + (+o.desconto ? ' · Desconto -' + money(o.desconto) : '') + ' · Frete ' + money(o.frete) + '</span><br><b style="font-size:1.25rem">Total ' + money(o.total) + '</b></div>' +
        (canEdit && o.status !== 'cancelado' ? '<div class="card" style="margin:16px 0"><h3>Atualizar status</h3><div class="sub">Aprovar o pagamento lança a receita; cancelar devolve o estoque.</div><div class="fgrid"><div class="field"><select class="input" id="ns">' + PED_ST.filter(function (s) { return s !== o.status; }).map(function (s) { return '<option value="' + s + '">' + E.label(s) + '</option>'; }).join('') + '</select></div><div class="field"><input class="input" id="nobs" placeholder="Observação (opcional)" maxlength="255"></div></div><div id="serr"></div><button class="btn btn-primary btn-sm" id="supd">Atualizar</button></div>' : '') +
        '<h4 style="margin:18px 0 12px">Histórico</h4><div class="tl">' + d.historico.map(function (h) { return '<div>' + E.badge(h.status_novo) + ' <span class="muted">' + E.dt(h.criado_em) + (h.usuario ? ' · ' + esc(h.usuario) : '') + (h.observacao ? ' · ' + esc(h.observacao) : '') + '</span></div>'; }).join('') + '</div>' });
      var b = m.$('#supd');
      if (b) b.onclick = function () {
        var ns = m.$('#ns').value;
        var go = function () { b.disabled = true; A.save(E.api('/admin/pedidos/' + id + '/status', { method: 'PATCH', body: { status: ns, observacao: m.$('#nobs').value.trim() } }), 'Status atualizado', function () { m.close(); after(); }).catch(function (e) { m.$('#serr').innerHTML = A.alertErr(e.message); b.disabled = false; }); };
        if (ns === 'cancelado') A.confirm('Cancelar o pedido ' + o.numero + '? O estoque dos itens será devolvido e a ação não pode ser desfeita.', 'Cancelar pedido').then(function (ok) { if (ok) go(); }); else go();
      };
    }).catch(function (e) { E.toast(e.message, 'err'); });
  }

  /* ================= PRODUTOS ================= */
  function prodFields(creating, cats) {
    var f = [
      { n: 'nome', l: 'Nome do produto', req: true, full: true, max: 160 },
      { n: 'sku', l: 'SKU (código)', req: true, max: 40, hint: 'Único. Letras, números, ponto e hífen.' },
      { n: 'categoria_id', l: 'Categoria', t: 'select', req: true, o: [['', 'Selecione…']].concat(cats.filter(function (c) { return +c.ativo; }).map(function (c) { return [c.id, (c.pai_id ? '— ' : '') + c.nome]; })) },
      { n: 'marca', l: 'Marca', max: 80 },
      { n: 'preco', l: 'Preço (R$)', t: 'number', step: '0.01', min: 0, req: true },
      { n: 'preco_promocional', l: 'Preço promocional (R$)', t: 'number', step: '0.01', min: 0, hint: 'Opcional. Deve ser menor que o preço.' },
      { n: 'custo', l: 'Custo (R$)', t: 'number', step: '0.01', min: 0, hint: 'Uso interno; nunca aparece no site.' },
      { n: 'promocao_inicio', l: 'Promoção — início', t: 'datetime-local' }, { n: 'promocao_fim', l: 'Promoção — fim', t: 'datetime-local' },
      { n: 'peso_kg', l: 'Peso (kg)', t: 'number', step: '0.001', min: 0 }
    ];
    f.push(creating ? { n: 'estoque_inicial', l: 'Estoque inicial', t: 'number', min: 0, step: '1' } : { n: 'estoque_minimo', l: 'Estoque mínimo (alerta)', t: 'number', min: 0, step: '1' });
    if (creating) f.push({ n: 'estoque_minimo', l: 'Estoque mínimo (alerta)', t: 'number', min: 0, step: '1' });
    f.push({ n: 'descricao_curta', l: 'Descrição curta', full: true, max: 255 }, { n: 'descricao', l: 'Descrição completa', t: 'textarea', full: true, rows: 6 },
      { n: 'meta_titulo', l: 'Título para o Google (SEO)', max: 70 }, { n: 'meta_descricao', l: 'Descrição para o Google (SEO)', max: 160 },
      { n: 'destaque', l: 'Mostrar em destaque na página inicial', t: 'checkbox' }, { n: 'ativo', l: 'Produto ativo (visível na loja)', t: 'checkbox', def: true });
    return f;
  }

  function imagesBlock(p) {
    return '<div class="field full" style="margin-top:6px"><label class="f">Imagens do produto</label><div class="imgs" id="imgs"></div>' +
      '<label class="btn btn-ghost btn-sm" style="cursor:pointer">' + E.ic('upload') + ' Enviar imagens<input type="file" id="up" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden></label><div class="hint">JPG, PNG, WEBP ou GIF até 3 MB cada. A primeira vira a imagem principal.</div><div id="uerr"></div></div>';
  }
  function bindImages(m, id, list) {
    function draw() {
      m.$('#imgs').innerHTML = list.length ? list.map(function (i) { return '<div class="im' + (+i.principal ? ' main' : '') + '"><img src="' + esc(i.caminho) + '" alt="">' + (+i.principal ? '' : '<button class="mk" data-mk="' + i.id + '">Principal</button>') + '<button class="rmi" data-rm="' + i.id + '" aria-label="Remover">' + E.ic('x') + '</button></div>'; }).join('') : '<span class="muted" style="font-size:.88rem">Nenhuma imagem ainda.</span>';
    }
    draw();
    m.$('#imgs').onclick = function (e) {
      var rm = e.target.closest('[data-rm]'), mk = e.target.closest('[data-mk]');
      if (rm) E.api('/admin/imagens/' + rm.dataset.rm, { method: 'DELETE' }).then(function () { return E.api('/admin/produtos/' + id); }).then(function (d) { list = d.produto.imagens; draw(); }).catch(function (er) { E.toast(er.message, 'err'); });
      if (mk) E.api('/admin/imagens/' + mk.dataset.mk + '/principal', { method: 'POST' }).then(function () { return E.api('/admin/produtos/' + id); }).then(function (d) { list = d.produto.imagens; draw(); }).catch(function (er) { E.toast(er.message, 'err'); });
    };
    m.$('#up').onchange = function () {
      var fd = new FormData(); Array.prototype.forEach.call(this.files, function (f) { fd.append('imagens[]', f); }); if (!this.files.length) return;
      m.$('#uerr').innerHTML = '<div class="hint">Enviando…</div>'; var inp = this;
      E.api('/admin/produtos/' + id + '/imagens', { method: 'POST', body: fd }).then(function (d) { list = d.imagens; draw(); m.$('#uerr').innerHTML = ''; inp.value = ''; })
        .catch(function (er) { m.$('#uerr').innerHTML = A.alertErr(er.message); inp.value = ''; });
    };
  }

  function editProduct(id, after) {
    Promise.all([A.loadCats(), id ? E.api('/admin/produtos/' + id) : Promise.resolve(null)]).then(function (r) {
      var cats = r[0], p = r[1] ? r[1].produto : null, v = p ? Object.assign({}, p) : { ativo: 1 };
      if (p) { v.promocao_inicio = A.dtLocal(p.promocao_inicio); v.promocao_fim = A.dtLocal(p.promocao_fim); if (v.preco_promocional === null) v.preco_promocional = ''; }
      var m = A.form({ title: p ? 'Editar produto' : 'Novo produto', size: 'lg', fields: prodFields(!p, cats), values: v, extra: p ? imagesBlock(p) : '<div class="hint">Depois de salvar, você poderá enviar as imagens do produto.</div>',
        ok: p ? 'Salvar alterações' : 'Criar produto',
        onSubmit: function (val) {
          return E.api(p ? '/admin/produtos/' + id : '/admin/produtos', { method: p ? 'PUT' : 'POST', body: val }).then(function (d) {
            m.close(); after();
            if (!p) { E.toast('Produto criado. Agora adicione as imagens.'); editProduct(d.produto.id, after); } else E.toast('Produto atualizado');
          });
        } });
      if (p) bindImages(m, id, p.imagens);
    }).catch(function (e) { E.toast(e.message, 'err'); });
  }

  A.add({ id: 'produtos', label: 'Produtos', icon: 'tag', perm: 'produtos.ver', render: function (v) {
    var S = { busca: '', categoria_id: '', page: 1 };
    return A.loadCats().then(function (cats) {
      v.innerHTML = '<div class="bar2"><input class="input" id="q" type="search" placeholder="Buscar por nome, SKU ou marca…"><select class="input" id="cf"><option value="">Todas as categorias</option>' + cats.map(function (c) { return '<option value="' + c.id + '">' + esc(c.nome) + '</option>'; }).join('') + '</select>' +
        (E.Session.can('produtos.criar') ? '<button class="btn btn-primary btn-sm sp" id="new">' + E.ic('plus') + ' Novo produto</button>' : '') + '</div><div id="list"></div><div id="pgr"></div>';
      function load() {
        var q = new URLSearchParams({ page: S.page, limit: 15 }); if (S.busca) q.set('busca', S.busca); if (S.categoria_id) q.set('categoria_id', S.categoria_id);
        return E.api('/admin/produtos?' + q).then(function (d) {
          $('#list').innerHTML = A.table([
            { h: '', f: function (p) { return '<img class="thumb" src="' + esc(E.img(p.imagem)) + '" alt="">'; } },
            { h: 'Produto', f: function (p) { return '<span class="nm">' + esc(p.nome) + '</span><br><small>' + esc(p.sku) + (p.marca ? ' · ' + esc(p.marca) : '') + '</small>'; } },
            { h: 'Categoria', f: function (p) { return esc(p.categoria_nome); } },
            { h: 'Preço', cls: 'num', f: function (p) { return p.preco_promocional ? '<b>' + money(p.preco_promocional) + '</b><br><small style="text-decoration:line-through">' + money(p.preco) + '</small>' : money(p.preco); } },
            { h: 'Estoque', cls: 'num', f: function (p) { var c = +p.estoque === 0 ? 'stk-zero' : (+p.estoque <= +p.estoque_minimo ? 'stk-low' : ''); return '<span class="' + c + '">' + p.estoque + '</span>'; } },
            { h: 'Status', f: function (p) { return (+p.ativo ? '<span class="badge b-ok">Ativo</span>' : '<span class="badge b-mut">Inativo</span>') + (+p.destaque ? ' <span class="badge b-vio">Destaque</span>' : ''); } },
            { h: '', cls: 'r', f: function (p) { return '<div class="acts">' + (E.Session.can('produtos.editar') ? A.iconBtn('edit', 'data-ed="' + p.id + '"', 'Editar') : '') + (E.Session.can('produtos.excluir') ? A.iconBtn('trash', 'data-del="' + p.id + '" data-nm="' + esc(p.nome) + '"', 'Excluir', 'del') : '') + '</div>'; } }], d.produtos, 'Nenhum produto encontrado.');
          A.pager(d.paginacao, $('#pgr'), function (p) { S.page = p; load(); });
        });
      }
      $('#q').oninput = A.debounce(function () { S.busca = this.value.trim(); S.page = 1; load(); }); $('#cf').onchange = function () { S.categoria_id = this.value; S.page = 1; load(); };
      var nb = $('#new'); if (nb) nb.onclick = function () { editProduct(null, load); };
      v.onclick = function (e) {
        var ed = e.target.closest('[data-ed]'), del = e.target.closest('[data-del]');
        if (ed) editProduct(ed.dataset.ed, load);
        if (del) A.confirm('Excluir o produto "' + del.dataset.nm + '"? Ele deixará de aparecer na loja (o histórico de pedidos é preservado).', 'Excluir').then(function (ok) { if (ok) A.save(E.api('/admin/produtos/' + del.dataset.del, { method: 'DELETE' }), 'Produto excluído', load).catch(function (er) { E.toast(er.message, 'err'); }); });
      };
      return load();
    });
  } });
})();
