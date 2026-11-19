/* Painel: Estoque, Serviços, Clientes, Cupons, Financeiro, Categorias, Configurações */
(function () {
  'use strict';
  var E = window.EI, A = window.EIA, $ = E.$, esc = E.esc, money = E.money;
  if (!A) return;
  var SOL_ST = ['solicitado', 'em_analise', 'orcamento_enviado', 'orcamento_aprovado', 'agendado', 'em_execucao', 'concluido', 'recusado', 'cancelado'];
  var yesNo = function (v) { return +v ? '<span class="badge b-ok">Sim</span>' : '<span class="badge b-mut">Não</span>'; };

  /* ================= ESTOQUE ================= */
  A.add({ id: 'estoque', label: 'Estoque', icon: 'layers', perm: 'estoque.ver', render: function (v) {
    var S = { busca: '', page: 1, baixo: false };
    v.innerHTML = '<div class="bar2"><input class="input" id="q" type="search" placeholder="Buscar produto por nome ou SKU…"><div class="seg"><button class="on" data-m="todos">Todos</button><button data-m="baixo">Estoque baixo</button></div></div><div id="list"></div><div id="pgr"></div>';
    function cols(rows) {
      return A.table([
        { h: 'Produto', f: function (p) { return '<span class="nm">' + esc(p.nome) + '</span><br><small>' + esc(p.sku) + '</small>'; } },
        { h: 'Em estoque', cls: 'num', f: function (p) { var q = p.estoque != null ? p.estoque : p.quantidade, c = +q === 0 ? 'stk-zero' : (+q <= +p.estoque_minimo ? 'stk-low' : ''); return '<b class="' + c + '">' + q + '</b>'; } },
        { h: 'Mínimo', cls: 'num', f: function (p) { return p.estoque_minimo; } },
        { h: '', cls: 'r', f: function (p) { return '<div class="acts">' + (E.Session.can('estoque.ajustar') ? '<button class="btn btn-dark btn-sm" data-aj="' + p.id + '" data-nm="' + esc(p.nome) + '">Ajustar</button>' : '') + '<button class="btn btn-ghost btn-sm" data-hi="' + p.id + '" data-nm="' + esc(p.nome) + '">Histórico</button></div>'; } }], rows, 'Nenhum produto encontrado.');
    }
    function load() {
      if (S.baixo) return E.api('/admin/estoque/baixo').then(function (d) { $('#list').innerHTML = cols(d.produtos); $('#pgr').innerHTML = ''; });
      var q = new URLSearchParams({ page: S.page, limit: 20 }); if (S.busca) q.set('busca', S.busca);
      return E.api('/admin/produtos?' + q).then(function (d) { $('#list').innerHTML = cols(d.produtos); A.pager(d.paginacao, $('#pgr'), function (p) { S.page = p; load(); }); });
    }
    $('#q').oninput = A.debounce(function () { S.busca = this.value.trim(); S.page = 1; S.baixo = false; E.$$('.seg button').forEach(function (b) { b.classList.toggle('on', b.dataset.m === 'todos'); }); load(); });
    v.querySelector('.seg').onclick = function (e) { var b = e.target.closest('button'); if (!b) return; S.baixo = b.dataset.m === 'baixo'; E.$$('.seg button').forEach(function (x) { x.classList.toggle('on', x === b); }); load(); };
    v.onclick = function (e) {
      var aj = e.target.closest('[data-aj]'), hi = e.target.closest('[data-hi]');
      if (aj) A.form({ title: 'Ajustar estoque — ' + aj.dataset.nm, fields: [
        { n: 'tipo', l: 'Tipo de movimentação', t: 'select', o: [['entrada', 'Entrada (somar)'], ['saida', 'Saída (subtrair)'], ['ajuste', 'Ajuste (definir quantidade exata)']], full: true },
        { n: 'quantidade', l: 'Quantidade', t: 'number', min: 0, step: '1', req: true }, { n: 'motivo', l: 'Motivo', max: 255, ph: 'Ex.: compra de fornecedor, inventário…' }],
        onSubmit: function (val, m) { return E.api('/admin/estoque/' + aj.dataset.aj + '/ajuste', { method: 'POST', body: val }).then(function () { m.close(); E.toast('Estoque atualizado'); load(); }); } });
      if (hi) E.api('/admin/estoque/' + hi.dataset.hi + '/movimentacoes').then(function (d) {
        E.modal({ title: 'Histórico — ' + hi.dataset.nm, size: 'lg', body: A.table([
          { h: 'Data', f: function (r) { return E.dt(r.criado_em); } }, { h: 'Tipo', f: function (r) { return esc(r.tipo); } },
          { h: 'Qtd', cls: 'num', f: function (r) { return r.quantidade; } }, { h: 'Saldo', cls: 'num', f: function (r) { return r.saldo_anterior + ' → <b>' + r.saldo_posterior + '</b>'; } },
          { h: 'Motivo / Usuário', f: function (r) { return esc(r.motivo || '—') + (r.usuario ? '<br><small>' + esc(r.usuario) + '</small>' : ''); } }], d.movimentacoes, 'Sem movimentações.') });
      }).catch(function (er) { E.toast(er.message, 'err'); });
    };
    return load();
  } });

  /* ================= SERVIÇOS ================= */
  function openSolic(id, after) {
    E.api('/admin/solicitacoes/' + id).then(function (d) {
      var s = d.solicitacao, canEdit = E.Session.can('servicos.gerenciar');
      var m = E.modal({ title: 'Solicitação ' + s.protocolo, size: 'lg', body:
        '<div style="margin-bottom:14px">' + E.badge(s.status) + ' <span class="muted">' + E.dt(s.criado_em) + '</span></div>' +
        '<dl class="kv"><dt>Serviço</dt><dd><b>' + esc(s.servico_nome) + '</b></dd><dt>Cliente</dt><dd>' + esc(s.cliente_nome) + ' · ' + esc(s.cliente_email) + (s.cliente_telefone ? ' · ' + esc(s.cliente_telefone) : '') + '</dd>' +
        '<dt>Local</dt><dd>' + (s.logradouro ? esc(s.logradouro) + ', ' + esc(s.numero) + ' · ' + esc(s.bairro) + ' · ' + esc(s.cidade) + '/' + esc(s.uf) : '—') + '</dd>' +
        '<dt>Preferência</dt><dd>' + (s.data_preferida ? E.dtd(s.data_preferida) : 'Sem data') + ' · ' + esc(s.periodo_preferido) + '</dd><dt>Descrição</dt><dd style="white-space:pre-wrap">' + esc(s.descricao_cliente) + '</dd></dl>' +
        (canEdit ? '<div class="card" style="margin-bottom:16px"><h3>Atualizar</h3><div class="sub">O cliente vê o status, o valor do orçamento e a observação pública.</div><div class="fgrid">' +
          '<div class="field"><label class="f">Status</label><select class="input" id="s_st">' + SOL_ST.map(function (x) { return '<option value="' + x + '"' + (x === s.status ? ' selected' : '') + '>' + E.label(x) + '</option>'; }).join('') + '</select></div>' +
          '<div class="field"><label class="f">Valor do orçamento (R$)</label><input class="input" id="s_v" type="number" step="0.01" min="0" value="' + esc(s.valor_orcado || '') + '"></div>' +
          '<div class="field"><label class="f">Orçamento válido até</label><input class="input" id="s_p" type="date" value="' + esc(s.prazo_validade_orcamento || '') + '"></div>' +
          '<div class="field"><label class="f">Observação para o cliente</label><input class="input" id="s_o" maxlength="255"></div>' +
          '<div class="field full"><label class="f">Anotações internas (o cliente não vê)</label><textarea class="input" id="s_i" rows="3">' + esc(s.observacoes_internas || '') + '</textarea></div></div><div id="serr"></div><button class="btn btn-primary btn-sm" id="s_go">Salvar</button></div>' : '') +
        '<h4 style="margin:16px 0 12px">Histórico</h4><div class="tl">' + d.historico.map(function (h) { return '<div>' + E.badge(h.status_novo) + ' <span class="muted">' + E.dt(h.criado_em) + (h.usuario ? ' · ' + esc(h.usuario) : '') + (h.observacao ? ' · ' + esc(h.observacao) : '') + '</span></div>'; }).join('') + '</div>' });
      var go = m.$('#s_go');
      if (go) go.onclick = function () {
        var body = { status: m.$('#s_st').value, observacao: m.$('#s_o').value.trim(), observacoes_internas: m.$('#s_i').value };
        if (m.$('#s_v').value !== '') body.valor_orcado = m.$('#s_v').value; if (m.$('#s_p').value) body.prazo_validade_orcamento = m.$('#s_p').value;
        go.disabled = true;
        A.save(E.api('/admin/solicitacoes/' + id, { method: 'PATCH', body: body }), 'Solicitação atualizada', function () { m.close(); after(); }).catch(function (e) { m.$('#serr').innerHTML = A.alertErr(e.message); go.disabled = false; });
      };
    }).catch(function (e) { E.toast(e.message, 'err'); });
  }

  function servicoForm(s, cats, after) {
    A.form({ title: s ? 'Editar serviço' : 'Novo serviço', size: 'lg', values: s || { disponivel: 1, tipo_preco: 'sob_orcamento' }, fields: [
      { n: 'nome', l: 'Nome do serviço', req: true, full: true, max: 160 },
      { n: 'categoria_id', l: 'Categoria', t: 'select', req: true, o: [['', 'Selecione…']].concat(cats.map(function (c) { return [c.id, c.nome]; })) },
      { n: 'tipo_preco', l: 'Tipo de preço', t: 'select', o: [['sob_orcamento', 'Sob orçamento'], ['preco_inicial', 'A partir de (preço inicial)']] },
      { n: 'preco_inicial', l: 'Preço inicial (R$)', t: 'number', step: '0.01', min: 0, hint: 'Obrigatório se o tipo for “A partir de”.' },
      { n: 'tempo_estimado_min', l: 'Tempo médio (minutos)', t: 'number', min: 0, step: '1' },
      { n: 'descricao', l: 'Descrição', t: 'textarea', full: true, rows: 5 }, { n: 'disponivel', l: 'Disponível para solicitação no site', t: 'checkbox', def: true }],
      onSubmit: function (val, m) { return E.api(s ? '/admin/servicos/' + s.id : '/admin/servicos', { method: s ? 'PUT' : 'POST', body: val }).then(function () { m.close(); E.toast('Serviço salvo'); after(); }); } });
  }

  A.add({ id: 'servicos', label: 'Serviços', icon: 'wrench', perm: 'servicos.ver', render: function (v) {
    var tab = 'sol', S = { status: '', page: 1 };
    function shell() {
      v.innerHTML = '<div class="bar2"><div class="seg"><button data-t="sol" class="' + (tab === 'sol' ? 'on' : '') + '">Solicitações</button><button data-t="cat" class="' + (tab === 'cat' ? 'on' : '') + '">Catálogo de serviços</button></div>' +
        (tab === 'sol' ? '<select class="input" id="st"><option value="">Todos os status</option>' + SOL_ST.map(function (s) { return '<option value="' + s + '"' + (S.status === s ? ' selected' : '') + '>' + E.label(s) + '</option>'; }).join('') + '</select>' :
          (E.Session.can('servicos.gerenciar') ? '<button class="btn btn-primary btn-sm sp" id="new">' + E.ic('plus') + ' Novo serviço</button>' : '')) + '</div><div id="list"></div><div id="pgr"></div>';
      v.querySelector('.seg').onclick = function (e) { var b = e.target.closest('button'); if (b) { tab = b.dataset.t; S.page = 1; shell(); load(); } };
    }
    function load() {
      if (tab === 'sol') {
        var q = new URLSearchParams({ page: S.page, limit: 15 }); if (S.status) q.set('status', S.status);
        $('#st').onchange = function () { S.status = this.value; S.page = 1; load(); };
        return E.api('/admin/solicitacoes?' + q).then(function (d) {
          $('#list').innerHTML = A.table([{ h: 'Protocolo', f: function (r) { return '<b>' + esc(r.protocolo) + '</b>'; } }, { h: 'Serviço', f: function (r) { return esc(r.servico_nome); } }, { h: 'Cliente', f: function (r) { return esc(r.cliente_nome); } },
            { h: 'Data', f: function (r) { return E.dt(r.criado_em); } }, { h: 'Orçamento', cls: 'num', f: function (r) { return r.valor_orcado !== null ? money(r.valor_orcado) : '—'; } }, { h: 'Status', f: function (r) { return E.badge(r.status); } },
            { h: '', cls: 'r', f: function (r) { return '<div class="acts">' + A.iconBtn('eye', 'data-open="' + r.id + '"', 'Abrir') + '</div>'; } }], d.solicitacoes, 'Nenhuma solicitação.');
          A.pager(d.paginacao, $('#pgr'), function (p) { S.page = p; load(); });
        });
      }
      return E.api('/admin/servicos').then(function (d) {
        $('#pgr').innerHTML = '';
        $('#list').innerHTML = A.table([{ h: 'Serviço', f: function (s) { return '<span class="nm">' + esc(s.nome) + '</span><br><small>' + esc(s.categoria_nome) + '</small>'; } },
          { h: 'Preço', cls: 'num', f: function (s) { return s.tipo_preco === 'preco_inicial' ? 'a partir de ' + money(s.preco_inicial) : 'Sob orçamento'; } }, { h: 'Disponível', f: function (s) { return yesNo(s.disponivel); } },
          { h: '', cls: 'r', f: function (s) { return E.Session.can('servicos.gerenciar') ? '<div class="acts">' + A.iconBtn('edit', 'data-ed="' + s.id + '"', 'Editar') + A.iconBtn('trash', 'data-del="' + s.id + '" data-nm="' + esc(s.nome) + '"', 'Excluir', 'del') + '</div>' : ''; } }], d.servicos, 'Nenhum serviço cadastrado.');
        var nb = $('#new'); if (nb) nb.onclick = function () { E.api('/servicos/categorias').then(function (c) { servicoForm(null, c.categorias, load); }); };
        v.onclick = function (e) {
          var ed = e.target.closest('[data-ed]'), del = e.target.closest('[data-del]'), op = e.target.closest('[data-open]');
          if (ed) E.api('/servicos/categorias').then(function (c) { servicoForm(d.servicos.filter(function (x) { return x.id === +ed.dataset.ed; })[0], c.categorias, load); });
          if (del) A.confirm('Excluir o serviço "' + del.dataset.nm + '"?', 'Excluir').then(function (ok) { if (ok) A.save(E.api('/admin/servicos/' + del.dataset.del, { method: 'DELETE' }), 'Serviço excluído', load).catch(function (er) { E.toast(er.message, 'err'); }); });
          if (op) openSolic(op.dataset.open, load);
        };
      });
    }
    v.onclick = function (e) { var op = e.target.closest('[data-open]'); if (op) openSolic(op.dataset.open, load); };
    shell(); return load();
  } });

  /* ================= CLIENTES ================= */
  A.add({ id: 'clientes', label: 'Clientes', icon: 'users', perm: 'clientes.ver', render: function (v) {
    var S = { busca: '', page: 1 };
    v.innerHTML = '<div class="bar2"><input class="input" id="q" type="search" placeholder="Buscar por nome, e-mail ou telefone…"></div><div id="list"></div><div id="pgr"></div>';
    function load() {
      var q = new URLSearchParams({ page: S.page, limit: 20 }); if (S.busca) q.set('busca', S.busca);
      return E.api('/admin/clientes?' + q).then(function (d) {
        $('#list').innerHTML = A.table([{ h: 'Cliente', f: function (c) { return '<span class="nm">' + esc(c.nome) + '</span><br><small>' + esc(c.email) + '</small>'; } }, { h: 'Telefone', f: function (c) { return esc(c.telefone || '—'); } },
          { h: 'Pedidos', cls: 'num', f: function (c) { return c.pedidos; } }, { h: 'Total gasto', cls: 'num', f: function (c) { return money(c.total_gasto); } },
          { h: 'Cadastro', f: function (c) { return E.dtd(c.criado_em); } }, { h: 'Status', f: function (c) { return c.status === 'ativo' ? '<span class="badge b-ok">Ativo</span>' : '<span class="badge b-mut">' + esc(c.status) + '</span>'; } }], d.clientes, 'Nenhum cliente encontrado.');
        A.pager(d.paginacao, $('#pgr'), function (p) { S.page = p; load(); });
      });
    }
    $('#q').oninput = A.debounce(function () { S.busca = this.value.trim(); S.page = 1; load(); });
    return load();
  } });

  /* ================= CUPONS ================= */
  A.add({ id: 'cupons', label: 'Cupons', icon: 'tag', perm: 'cupons.gerenciar', render: function (v) {
    function form(c, after) {
      A.form({ title: c ? 'Editar cupom ' + c.codigo : 'Novo cupom', size: 'lg', values: c ? Object.assign({}, c, { inicio_em: A.dtLocal(c.inicio_em), fim_em: A.dtLocal(c.fim_em), limite_total: c.limite_total || '' }) : { tipo: 'percentual', limite_por_cliente: 1, valor_minimo_pedido: 0, ativo: 1 }, fields: [
        { n: 'codigo', l: 'Código', req: !c, max: 40, ro: !!c, hint: c ? 'O código não pode ser alterado.' : 'Ex.: BEMVINDO10 (será salvo em maiúsculas).' },
        { n: 'tipo', l: 'Tipo', t: 'select', o: [['percentual', 'Percentual (%)'], ['valor_fixo', 'Valor fixo (R$)'], ['frete_gratis', 'Frete grátis']] },
        { n: 'valor', l: 'Valor (% ou R$)', t: 'number', step: '0.01', min: 0, hint: 'Ignorado em frete grátis.' }, { n: 'valor_minimo_pedido', l: 'Pedido mínimo (R$)', t: 'number', step: '0.01', min: 0 },
        { n: 'limite_total', l: 'Limite total de usos', t: 'number', min: 0, step: '1', hint: 'Vazio = ilimitado.' }, { n: 'limite_por_cliente', l: 'Usos por cliente', t: 'number', min: 1, step: '1' },
        { n: 'inicio_em', l: 'Válido a partir de', t: 'datetime-local' }, { n: 'fim_em', l: 'Válido até', t: 'datetime-local' },
        { n: 'descricao', l: 'Descrição', full: true, max: 160 }, { n: 'ativo', l: 'Cupom ativo', t: 'checkbox', def: true }],
        onSubmit: function (val, m) { return E.api(c ? '/admin/cupons/' + c.id : '/admin/cupons', { method: c ? 'PUT' : 'POST', body: val }).then(function () { m.close(); E.toast('Cupom salvo'); after(); }); } });
    }
    function load() {
      return E.api('/admin/cupons').then(function (d) {
        v.innerHTML = '<div class="bar2"><button class="btn btn-primary btn-sm sp" id="new">' + E.ic('plus') + ' Novo cupom</button></div>' +
          A.table([{ h: 'Código', f: function (c) { return '<b>' + esc(c.codigo) + '</b><br><small>' + esc(c.descricao || '') + '</small>'; } },
            { h: 'Desconto', f: function (c) { return c.tipo === 'percentual' ? c.valor + '%' : (c.tipo === 'valor_fixo' ? money(c.valor) : 'Frete grátis'); } }, { h: 'Mínimo', cls: 'num', f: function (c) { return money(c.valor_minimo_pedido); } },
            { h: 'Usos', cls: 'num', f: function (c) { return c.total_usos + (c.limite_total ? ' / ' + c.limite_total : ''); } }, { h: 'Validade', f: function (c) { return c.fim_em ? 'até ' + E.dtd(c.fim_em) : 'Sem prazo'; } },
            { h: 'Ativo', f: function (c) { return yesNo(c.ativo); } }, { h: '', cls: 'r', f: function (c) { return '<div class="acts">' + A.iconBtn('edit', 'data-ed="' + c.id + '"', 'Editar') + '</div>'; } }], d.cupons, 'Nenhum cupom cadastrado.');
        $('#new').onclick = function () { form(null, load); };
        v.onclick = function (e) { var ed = e.target.closest('[data-ed]'); if (ed) form(d.cupons.filter(function (x) { return x.id === +ed.dataset.ed; })[0], load); };
      });
    }
    return load();
  } });

  /* ================= FINANCEIRO ================= */
  A.add({ id: 'financeiro', label: 'Financeiro', icon: 'wallet', perm: 'financeiro.ver', render: function (v) {
    var mes = new Date().toISOString().slice(0, 7), data = null;
    function lanc(tipo) {
      var isR = tipo === 'receitas';
      A.form({ title: isR ? 'Nova receita' : 'Nova despesa', values: {}, fields: [
        { n: 'descricao', l: 'Descrição', req: true, full: true, max: 200 }, { n: 'valor', l: 'Valor (R$)', t: 'number', step: '0.01', min: 0, req: true },
        { n: 'categoria_id', l: 'Categoria', t: 'select', o: [['', 'Sem categoria']].concat(data.categorias.filter(function (c) { return c.tipo === (isR ? 'receita' : 'despesa'); }).map(function (c) { return [c.id, c.nome]; })) },
        { n: 'vencimento', l: 'Vencimento', t: 'date' }].concat(isR ? [] : [{ n: 'fornecedor', l: 'Fornecedor', max: 120 }]).concat([{ n: 'quitado', l: isR ? 'Já foi recebida' : 'Já foi paga', t: 'checkbox' }]),
        onSubmit: function (val, m) { return E.api('/admin/' + tipo, { method: 'POST', body: val }).then(function () { m.close(); E.toast('Lançamento criado'); load(); }); } });
    }
    function tbl(tipo, rows) {
      var isR = tipo === 'receitas', ok = isR ? 'recebida' : 'paga', can = E.Session.can('financeiro.gerenciar');
      return A.table([{ h: 'Descrição', f: function (r) { return '<span class="nm">' + esc(r.descricao) + '</span><br><small>' + esc(r.categoria || (isR ? '' : r.fornecedor || '')) + '</small>'; } },
        { h: 'Data', f: function (r) { return E.dtd(isR ? (r.recebida_em || r.vencimento) : (r.paga_em || r.vencimento)); } }, { h: 'Valor', cls: 'num', f: function (r) { return money(r.valor); } }, { h: 'Status', f: function (r) { return E.badge(r.status); } },
        { h: '', cls: 'r', f: function (r) { return can && r.status !== ok && r.status !== 'cancelada' ? '<div class="acts">' + A.iconBtn('check', 'data-q="' + tipo + ':' + r.id + '"', isR ? 'Marcar como recebida' : 'Marcar como paga') + A.iconBtn('x', 'data-c="' + tipo + ':' + r.id + '"', 'Cancelar', 'del') + '</div>' : ''; } }], rows, isR ? 'Sem receitas no período.' : 'Sem despesas no período.');
    }
    function load() {
      return E.api('/admin/financeiro?mes=' + mes).then(function (d) {
        data = d; var t = d.totais, can = E.Session.can('financeiro.gerenciar');
        v.innerHTML = '<div class="bar2"><input class="input" type="month" id="mes" value="' + mes + '">' + (can ? '<button class="btn btn-ghost btn-sm sp" id="nr">' + E.ic('plus') + ' Receita</button><button class="btn btn-primary btn-sm" id="nd">' + E.ic('plus') + ' Despesa</button>' : '') + '</div>' +
          '<div class="mini" style="grid-template-columns:repeat(5,1fr)"><div class="card"><span>Recebido</span><b style="color:var(--ok)">' + money(t.recebido) + '</b></div><div class="card"><span>Pago</span><b style="color:var(--danger)">' + money(t.pago) + '</b></div>' +
          '<div class="card"><span>Resultado</span><b>' + money(t.resultado) + '</b></div><div class="card"><span>A receber</span><b>' + money(t.a_receber) + '</b></div><div class="card"><span>A pagar</span><b>' + money(t.a_pagar) + '</b></div></div>' +
          '<div class="cols2"><div><h3 style="margin:0 0 10px">Receitas</h3>' + tbl('receitas', d.receitas) + '</div><div><h3 style="margin:0 0 10px">Despesas</h3>' + tbl('despesas', d.despesas) + '</div></div>';
        $('#mes').onchange = function () { if (this.value) { mes = this.value; load(); } };
        if (can) { $('#nr').onclick = function () { lanc('receitas'); }; $('#nd').onclick = function () { lanc('despesas'); }; }
        v.onclick = function (e) {
          var q = e.target.closest('[data-q]'), c = e.target.closest('[data-c]'), x = q || c; if (!x) return;
          var p = (q ? q.dataset.q : c.dataset.c).split(':'), run = function () { A.save(E.api('/admin/' + p[0] + '/' + p[1], { method: 'PATCH', body: { acao: q ? 'quitar' : 'cancelar' } }), 'Lançamento atualizado', load).catch(function (er) { E.toast(er.message, 'err'); }); };
          if (c) A.confirm('Cancelar este lançamento?', 'Cancelar lançamento').then(function (ok) { if (ok) run(); }); else run();
        };
      });
    }
    return load();
  } });

  /* ================= CATEGORIAS ================= */
  A.add({ id: 'categorias', label: 'Categorias', icon: 'grid', perm: 'categorias.gerenciar', render: function (v) {
    function form(c, cats, after) {
      A.form({ title: c ? 'Editar categoria' : 'Nova categoria', values: c || { ativo: 1, ordem: 0 }, fields: [
        { n: 'nome', l: 'Nome', req: true, full: true, max: 100 },
        { n: 'pai_id', l: 'Categoria pai', t: 'select', o: [['', '— Nenhuma (principal) —']].concat(cats.filter(function (x) { return !x.pai_id && (!c || x.id !== c.id); }).map(function (x) { return [x.id, x.nome]; })) },
        { n: 'ordem', l: 'Ordem de exibição', t: 'number', step: '1' }, { n: 'descricao', l: 'Descrição', full: true, max: 255 }, { n: 'ativo', l: 'Categoria ativa', t: 'checkbox', def: true }],
        onSubmit: function (val, m) { return E.api(c ? '/admin/categorias/' + c.id : '/admin/categorias', { method: c ? 'PUT' : 'POST', body: val }).then(function () { A.cats = null; m.close(); E.toast('Categoria salva'); after(); }); } });
    }
    function load() {
      A.cats = null;
      return A.loadCats().then(function (cats) {
        v.innerHTML = '<div class="bar2"><button class="btn btn-primary btn-sm sp" id="new">' + E.ic('plus') + ' Nova categoria</button></div>' +
          A.table([{ h: 'Categoria', f: function (c) { return (c.pai_id ? '<span class="muted">↳ </span>' : '') + '<span class="nm">' + esc(c.nome) + '</span><br><small>/' + esc(c.slug) + '</small>'; } }, { h: 'Produtos', cls: 'num', f: function (c) { return c.total_produtos; } },
            { h: 'Ordem', cls: 'num', f: function (c) { return c.ordem; } }, { h: 'Ativa', f: function (c) { return yesNo(c.ativo); } }, { h: '', cls: 'r', f: function (c) { return '<div class="acts">' + A.iconBtn('edit', 'data-ed="' + c.id + '"', 'Editar') + '</div>'; } }], cats);
        $('#new').onclick = function () { form(null, cats, load); };
        v.onclick = function (e) { var ed = e.target.closest('[data-ed]'); if (ed) form(cats.filter(function (x) { return x.id === +ed.dataset.ed; })[0], cats, load); };
      });
    }
    return load();
  } });

  /* ================= CONFIGURAÇÕES ================= */
  A.add({ id: 'config', label: 'Configurações', icon: 'cog', perm: 'configuracoes.editar', render: function (v) {
    return E.api('/admin/configuracoes').then(function (d) {
      v.innerHTML = '<div class="card" style="max-width:640px"><h3>Dados da loja</h3><div class="sub">O WhatsApp aparece no rodapé e após pedidos e solicitações. O frete é somado ao total do carrinho.</div><div id="cf">' +
        d.configuracoes.map(function (c) { return '<div class="field"><label class="f" for="c_' + c.chave + '">' + esc(c.descricao) + '</label><input class="input" id="c_' + c.chave + '" data-k="' + c.chave + '" value="' + esc(c.valor) + '"></div>'; }).join('') +
        '</div><div id="cerr"></div><button class="btn btn-primary btn-sm" id="cs">Salvar configurações</button></div>';
      $('#cs').onclick = function () {
        var body = {}; E.$$('#cf [data-k]').forEach(function (i) { body[i.dataset.k] = i.value.trim(); }); var b = this; b.disabled = true;
        A.save(E.api('/admin/configuracoes', { method: 'PUT', body: body }), 'Configurações salvas').catch(function (e) { $('#cerr').innerHTML = A.alertErr(e.message); }).then(function () { b.disabled = false; }, function () { b.disabled = false; });
      };
    });
  } });
})();
