/* Eletro Instal — painel administrativo: núcleo (roteador + componentes) */
(function () {
  'use strict';
  var E = window.EI, $ = E.$, esc = E.esc;
  if (!E.Session.token() || !E.Session.user() || !E.Session.isStaff()) { location.replace('login.php?next=admin.php'); return; }
  if (E.Session.user().trocar_senha) { location.replace('conta.php#senha'); return; }

  var A = window.EIA = { sections: [], charts: [], add: function (s) { this.sections.push(s); } };
  var view = $('#view');

  /* ---------- componentes ---------- */
  A.loading = function (el) { (el || view).innerHTML = '<div class="skel" style="height:120px;margin-bottom:16px"></div><div class="skel" style="height:300px"></div>'; };
  A.err = function (e, el) { (el || view).innerHTML = '<div class="alert err">' + esc(e.message || e) + '</div>'; };
  A.alertErr = function (m) { return '<div class="alert err">' + esc(m) + '</div>'; };
  A.debounce = function (fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms || 350); }; };
  A.dtLocal = function (v) { return v ? String(v).replace(' ', 'T').slice(0, 16) : ''; };
  A.mes = function (ym) { var n = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']; var p = ym.split('-'); return n[+p[1] - 1] + '/' + p[0].slice(2); };

  A.table = function (cols, rows, empty) {
    if (!rows.length) return '<div class="tw"><div class="empty-s">' + esc(empty || 'Nenhum registro encontrado.') + '</div></div>';
    return '<div class="tw"><table class="dt"><thead><tr>' + cols.map(function (c) { return '<th class="' + (c.cls || '') + '">' + (c.h || '') + '</th>'; }).join('') + '</tr></thead><tbody>' +
      rows.map(function (r) { return '<tr>' + cols.map(function (c) { return '<td class="' + (c.cls || '') + '">' + c.f(r) + '</td>'; }).join('') + '</tr>'; }).join('') + '</tbody></table></div>';
  };
  A.iconBtn = function (icon, attr, title, cls) { return '<button class="ib ' + (cls || '') + '" ' + attr + ' title="' + esc(title) + '" aria-label="' + esc(title) + '">' + E.ic(icon) + '</button>'; };

  A.pager = function (pg, root, go) {
    var h = '<div class="pg"><span>' + pg.total + ' registro' + (pg.total === 1 ? '' : 's') + '</span><div>';
    if (pg.pages > 1) {
      h += '<button data-pg="' + (pg.page - 1) + '"' + (pg.page <= 1 ? ' disabled' : '') + '>‹</button>';
      for (var i = Math.max(1, pg.page - 2); i <= Math.min(pg.pages, pg.page + 2); i++) h += '<button data-pg="' + i + '" class="' + (i === pg.page ? 'on' : '') + '">' + i + '</button>';
      h += '<button data-pg="' + (pg.page + 1) + '"' + (pg.page >= pg.pages ? ' disabled' : '') + '>›</button>';
    }
    root.innerHTML = h + '</div></div>';
    root.onclick = function (e) { var b = e.target.closest('[data-pg]'); if (b && !b.disabled) go(+b.dataset.pg); };
  };

  function fieldHTML(f, val) {
    var id = 'f_' + f.n, req = f.req ? ' *' : '', w = f.full ? ' full' : '', v = val == null ? '' : val, h;
    if (f.t === 'checkbox') return '<div class="field' + w + '"><label class="check"><input type="checkbox" id="' + id + '"' + (val === undefined ? (f.def ? ' checked' : '') : (+val ? ' checked' : '')) + '><span>' + esc(f.l) + '</span></label></div>';
    if (f.t === 'textarea') h = '<textarea class="input" id="' + id + '" rows="' + (f.rows || 4) + '" placeholder="' + esc(f.ph || '') + '">' + esc(v) + '</textarea>';
    else if (f.t === 'select') h = '<select class="input" id="' + id + '">' + f.o.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (String(o[0]) === String(v) ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select>';
    else h = '<input class="input" id="' + id + '" type="' + (f.t || 'text') + '" value="' + esc(v) + '" placeholder="' + esc(f.ph || '') + '"' + (f.step ? ' step="' + f.step + '"' : '') + (f.min != null ? ' min="' + f.min + '"' : '') + (f.max ? ' maxlength="' + f.max + '"' : '') + (f.ro ? ' readonly' : '') + '>';
    return '<div class="field' + w + '"><label class="f" for="' + id + '">' + esc(f.l) + req + '</label>' + h + (f.hint ? '<div class="hint">' + esc(f.hint) + '</div>' : '') + '</div>';
  }
  function readForm(root, fields) {
    var o = {};
    fields.forEach(function (f) {
      var el = $('#f_' + f.n, root); if (!el) return;
      var v = f.t === 'checkbox' ? el.checked : el.value.trim();
      if (f.req && (v === '' || v === null)) throw new Error('Preencha o campo: ' + f.l);
      o[f.n] = f.t === 'number' ? (v === '' ? null : v) : v;
    });
    return o;
  }
  /** Modal de formulário genérico. onSubmit(valores, modal) deve retornar Promise. */
  A.form = function (o) {
    var body = '<div class="fgrid">' + o.fields.map(function (f) { return fieldHTML(f, (o.values || {})[f.n]); }).join('') + '</div>' + (o.extra || '') + '<div id="ferr"></div>';
    var m = E.modal({ title: o.title, size: o.size, body: body, footer: '<button class="btn btn-ghost btn-sm" data-c>Cancelar</button><button class="btn btn-primary btn-sm" id="fok">' + (o.ok || 'Salvar') + '</button>' });
    m.$('[data-c]').onclick = m.close;
    m.$('#fok').onclick = function () {
      var b = this, er = m.$('#ferr'), v; er.innerHTML = '';
      try { v = readForm(m.el, o.fields); } catch (e) { er.innerHTML = A.alertErr(e.message); return; }
      b.disabled = true;
      Promise.resolve(o.onSubmit(v, m)).catch(function (e) { er.innerHTML = A.alertErr(e.message); b.disabled = false; });
    };
    if (o.onOpen) o.onOpen(m);
    return m;
  };
  A.confirm = function (msg, okLabel) {
    return new Promise(function (res) {
      var done = false;
      var m = E.modal({ title: 'Confirmar', body: '<p>' + esc(msg) + '</p>', noFocus: true, footer: '<button class="btn btn-ghost btn-sm" data-n>Cancelar</button><button class="btn btn-danger btn-sm" data-y>' + esc(okLabel || 'Confirmar') + '</button>', onClose: function () { if (!done) res(false); } });
      m.$('[data-n]').onclick = m.close; m.$('[data-y]').onclick = function () { done = true; m.close(); res(true); };
    });
  };
  A.save = function (promise, msg, after) {
    return promise.then(function (d) { E.toast(msg || (d && d.message) || 'Salvo'); if (after) after(d); return d; });
  };
  A.cats = null;
  A.loadCats = function () { return A.cats ? Promise.resolve(A.cats) : E.api('/admin/categorias').then(function (d) { A.cats = d.categorias; return A.cats; }); };

  /* ---------- gráficos ---------- */
  A.chart = function (canvas, cfg) {
    if (!window.Chart) return null;
    Chart.defaults.color = '#8e9bb3'; Chart.defaults.borderColor = 'rgba(255,255,255,.07)'; Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    var c = new Chart(canvas, cfg); A.charts.push(c); return c;
  };

  /* ---------- roteador ---------- */
  var cur = null;
  function allowed() { return A.sections.filter(function (s) { return E.Session.can(s.perm); }); }
  function buildNav() {
    $('#snav').innerHTML = allowed().map(function (s) { return '<a class="snav-a" href="#' + s.id + '" data-id="' + s.id + '">' + E.ic(s.icon) + esc(s.label) + '</a>'; }).join('');
  }
  A.go = function (id) {
    var list = allowed(), s = list.filter(function (x) { return x.id === id; })[0] || list[0];
    if (!s) { view.innerHTML = '<div class="alert err">Seu perfil não tem acesso a nenhuma seção do painel.</div>'; return; }
    A.charts.forEach(function (c) { c.destroy(); }); A.charts = [];
    cur = s.id; $('#ttl').textContent = s.label; document.title = s.label + ' — Painel Eletro Instal';
    E.$$('.snav-a[data-id]').forEach(function (a) { a.classList.toggle('on', a.dataset.id === s.id); });
    $('#side').classList.remove('open'); $('#scrim').classList.remove('on');
    window.scrollTo(0, 0);
    A.loading();
    Promise.resolve(s.render(view)).catch(function (e) { A.err(e); });
  };
  A.reload = function () { A.go(cur); };

  function start() {
    var u = E.Session.user();
    $('#who').innerHTML = 'Olá, <b>' + esc(String(u.nome).split(' ')[0]) + '</b>';
    $('#exit').onclick = E.logout;
    $('#sideBtn').onclick = function () { $('#side').classList.add('open'); $('#scrim').classList.add('on'); };
    $('#scrim').onclick = function () { $('#side').classList.remove('open'); $('#scrim').classList.remove('on'); };
    // Atualiza permissões da sessão (podem ter mudado) e entra
    E.api('/auth/me').then(function (d) { E.Session.set(E.Session.token(), d.user); }).catch(function (e) {
      if (e.status === 401 || e.status === 403) return;
    }).then(function () {
      if (!E.Session.isStaff()) { location.replace('index.php'); return; }
      buildNav(); A.go((location.hash || '').slice(1));
    });
    window.addEventListener('hashchange', function () { A.go((location.hash || '').slice(1)); });
  }
  window.addEventListener('load', start);
})();
