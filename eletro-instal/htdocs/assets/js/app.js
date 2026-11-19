/* Eletro Instal — utilitários compartilhados (loja, conta e painel) */
(function () {
  'use strict';
  var API = 'api';

  /* ---------- helpers ---------- */
  var LS = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} },
    del: function (k) { try { localStorage.removeItem(k); } catch (e) {} }
  };
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return MAP[c]; }); }
  function money(v) { return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
  function toDate(v) { return v ? new Date(String(v).length === 10 ? v + 'T12:00:00' : String(v).replace(' ', 'T')) : null; }
  function dt(v) { var d = toDate(v); return d && !isNaN(d) ? d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—'; }
  function dtd(v) { var d = toDate(v); return d && !isNaN(d) ? d.toLocaleDateString('pt-BR') : '—'; }
  function img(p) { return p ? p : 'assets/img/no-image.svg'; }
  function ic(n, cls) { return '<svg class="ic ' + (cls || '') + '"><use href="assets/img/icons.svg#i-' + n + '"/></svg>'; }

  /* ---------- sessão ---------- */
  var Session = {
    token: function () { return LS.get('ei_token'); },
    user: function () { try { return JSON.parse(LS.get('ei_user') || 'null'); } catch (e) { return null; } },
    set: function (t, u) { LS.set('ei_token', t); LS.set('ei_user', JSON.stringify(u)); },
    clear: function () { LS.del('ei_token'); LS.del('ei_user'); },
    isStaff: function () { var u = this.user(); return !!u && (u.papel_id === 1 || u.papel_id === 2); },
    can: function (p) { var u = this.user(); return !!u && (u.papel_id === 1 || (u.permissoes || []).indexOf(p) > -1); }
  };

  function ApiError(msg, status, data) { this.message = msg; this.status = status; this.data = data || {}; }
  ApiError.prototype = Object.create(Error.prototype);

  /* ---------- API ---------- */
  function api(path, opt) {
    opt = opt || {};
    var method = (opt.method || 'GET').toUpperCase();
    var h = { 'Accept': 'application/json' };
    var body = opt.body;
    if (body && !(body instanceof FormData)) { h['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
    var t = Session.token();
    if (t) { h['Authorization'] = 'Bearer ' + t; h['X-Auth-Token'] = t; }
    var c = LS.get('ei_cart');
    if (c) h['X-Cart-Token'] = c;
    // Hospedagens gratuitas às vezes bloqueiam PUT/PATCH/DELETE: enviamos como POST + override
    var real = method;
    if (method === 'PUT' || method === 'PATCH' || method === 'DELETE') { h['X-HTTP-Method-Override'] = method; real = 'POST'; }

    return fetch(API + path, { method: real, headers: h, body: body, credentials: 'same-origin' })
      .catch(function () { throw new ApiError('Sem conexão com o servidor. Verifique sua internet e tente novamente.', 0); })
      .then(function (res) {
        return res.text().then(function (txt) {
          var data = null; try { data = JSON.parse(txt); } catch (e) {}
          if (!data) throw new ApiError(res.ok ? 'Resposta inválida do servidor.' : 'O servidor não respondeu corretamente (' + res.status + '). Tente novamente em instantes.', res.status);
          if (data.carrinho && data.carrinho.token_sessao) LS.set('ei_cart', data.carrinho.token_sessao);
          if (!res.ok) {
            if (res.status === 401 && t) { Session.clear(); renderAuth(); }
            if (res.status === 403 && data.codigo === 'TROCAR_SENHA' && !/conta\.php/.test(location.pathname)) location.href = 'conta.php#senha';
            throw new ApiError(data.error || 'Erro na requisição.', res.status, data);
          }
          return data;
        });
      });
  }

  /* ---------- UI: toast / modal ---------- */
  function toast(msg, type, link) {
    var box = $('#toasts'); if (!box) { box = document.createElement('div'); box.id = 'toasts'; box.className = 'toasts'; document.body.appendChild(box); }
    var el = document.createElement('div'); el.className = 'toast ' + (type || 'ok'); el.setAttribute('role', 'status');
    el.innerHTML = ic(type === 'err' ? 'alert' : 'check') + '<span>' + esc(msg) + '</span>' + (link ? '<a href="' + esc(link.href) + '">' + esc(link.text) + '</a>' : '');
    box.appendChild(el);
    setTimeout(function () { el.style.opacity = '0'; el.style.transition = '.3s'; setTimeout(function () { el.remove(); }, 300); }, type === 'err' ? 6000 : 3800);
  }

  function modal(o) {
    var bg = document.createElement('div'); bg.className = 'modal-bg';
    bg.innerHTML = '<div class="modal ' + (o.size || '') + '" role="dialog" aria-modal="true" aria-label="' + esc(o.title) + '"><div class="modal-h"><h3>' + esc(o.title) + '</h3><button class="x" aria-label="Fechar">' + ic('x') + '</button></div><div class="modal-b">' + (o.body || '') + '</div>' + (o.footer ? '<div class="modal-f">' + o.footer + '</div>' : '') + '</div>';
    document.body.appendChild(bg);
    document.body.style.overflow = 'hidden';
    function close() { bg.remove(); document.body.style.overflow = ''; document.removeEventListener('keydown', onKey); if (o.onClose) o.onClose(); }
    function onKey(e) { if (e.key === 'Escape') close(); }
    document.addEventListener('keydown', onKey);
    bg.addEventListener('mousedown', function (e) { if (e.target === bg) close(); });
    $('.x', bg).addEventListener('click', close);
    var first = $('input,select,textarea', bg); if (first && !o.noFocus) setTimeout(function () { first.focus(); }, 30);
    return { el: bg, close: close, $: function (s) { return $(s, bg); }, $$: function (s) { return $$(s, bg); } };
  }

  /* ---------- status ---------- */
  var ST = {
    aguardando_pagamento: ['Aguardando pagamento', 'warn'], pagamento_aprovado: ['Pagamento aprovado', 'info'], em_preparacao: ['Em preparação', 'vio'],
    enviado: ['Enviado', 'info'], em_transporte: ['Em transporte', 'info'], entregue: ['Entregue', 'ok'], cancelado: ['Cancelado', 'bad'],
    solicitado: ['Solicitado', 'warn'], em_analise: ['Em análise', 'vio'], orcamento_enviado: ['Orçamento enviado', 'info'], orcamento_aprovado: ['Orçamento aprovado', 'ok'],
    agendado: ['Agendado', 'info'], em_execucao: ['Em execução', 'vio'], concluido: ['Concluído', 'ok'], recusado: ['Recusado', 'bad'],
    pendente: ['Pendente', 'warn'], aprovado: ['Aprovado', 'ok'], estornado: ['Estornado', 'bad'], expirado: ['Expirado', 'mut'],
    a_receber: ['A receber', 'warn'], recebida: ['Recebida', 'ok'], a_pagar: ['A pagar', 'warn'], paga: ['Paga', 'ok'], cancelada: ['Cancelada', 'bad']
  };
  function badge(s) { var x = ST[s] || [String(s || '—').replace(/_/g, ' '), 'mut']; return '<span class="badge b-' + x[1] + '">' + esc(x[0]) + '</span>'; }
  function label(s) { return (ST[s] || [String(s).replace(/_/g, ' ')])[0]; }
  var CATICON = { 'cabos-e-fios': 'plug', 'disjuntores-e-protecao': 'shield', 'quadros-e-paineis': 'grid', 'iluminacao': 'bulb', 'tomadas-e-interruptores': 'toggle', 'eletrodutos-e-acessorios': 'pipe', 'ferramentas': 'wrench', 'automacao-e-energia-solar': 'sun' };
  function catIcon(slug) { return CATICON[slug] || 'box'; }
  var METODOS = { pix: 'PIX', transferencia: 'Transferência', dinheiro: 'Dinheiro', boleto: 'Boleto', cartao_credito: 'Cartão de crédito', cartao_debito: 'Cartão de débito' };

  /* ---------- carrinho ---------- */
  function setCartCount(n) { var el = $('#cartCount'); if (!el) return; el.textContent = n > 99 ? '99+' : n; el.hidden = !n; }
  function refreshCart() {
    if (!LS.get('ei_cart') && !Session.token()) { setCartCount(0); return Promise.resolve(null); }
    return api('/carrinho').then(function (d) { setCartCount(d.total_itens || 0); return d; }).catch(function () { return null; });
  }
  function addToCart(id, qty, btn) {
    if (btn) btn.disabled = true;
    return api('/carrinho/itens', { method: 'POST', body: { produto_id: Number(id), quantidade: qty || 1 } })
      .then(function (d) { setCartCount(d.total_itens); toast('Adicionado ao carrinho', 'ok', { href: 'carrinho.php', text: 'Ver carrinho' }); return d; })
      .catch(function (e) { toast(e.message, 'err'); throw e; })
      .then(function (d) { if (btn) btn.disabled = false; return d; }, function (e) { if (btn) btn.disabled = false; throw e; });
  }

  function productCard(p) {
    var out = p.estoque < 1, href = 'produto.php?slug=' + encodeURIComponent(p.slug);
    return '<article class="pcard"><a class="pimg" href="' + href + '" tabindex="-1" aria-hidden="true">' +
      (p.em_promocao ? '<span class="tag">-' + p.desconto_pct + '%</span>' : '') + (out ? '<span class="tag out">Indisponível</span>' : '') +
      '<img loading="lazy" src="' + esc(img(p.imagem_principal)) + '" alt=""></a>' +
      '<div class="pbody"><span class="pbrand">' + esc(p.marca || p.categoria_nome || '') + '</span>' +
      '<a class="pname" href="' + href + '">' + esc(p.nome) + '</a>' +
      '<div class="pprice"><span class="price">' + money(p.preco_atual) + '</span>' + (p.em_promocao ? '<span class="price-old">' + money(p.preco) + '</span>' : '') + '</div>' +
      '<button class="btn btn-primary btn-sm btn-block" data-add="' + p.id + '"' + (out ? ' disabled' : '') + '>' + ic('cart') + (out ? 'Sem estoque' : 'Adicionar') + '</button></div></article>';
  }
  function skeletons(n) { var s = ''; for (var i = 0; i < n; i++) s += '<div class="pcard skel"></div>'; return s; }

  /* ---------- config pública (WhatsApp etc.) ---------- */
  var cfgP = null;
  function config() { if (!cfgP) cfgP = api('/config').then(function (d) { return d.config; }).catch(function () { return {}; }); return cfgP; }
  function waLink(num, text) { return 'https://wa.me/' + num + (text ? '?text=' + encodeURIComponent(text) : ''); }

  /* ---------- autenticação na barra ---------- */
  function renderAuth() {
    var slot = $('#authSlot'); if (!slot) return;
    var u = Session.user();
    if (!u || !Session.token()) { slot.innerHTML = '<a class="btn btn-ghost btn-sm" href="login.php">' + ic('user') + '<span>Entrar</span></a>'; return; }
    var first = String(u.nome || 'Conta').split(' ')[0];
    slot.innerHTML = '<button class="user-btn" id="userBtn" aria-haspopup="true" aria-expanded="false"><span class="av">' + esc(first.charAt(0).toUpperCase()) + '</span><span class="nm">' + esc(first) + '</span></button>' +
      '<div class="menu" id="userMenu" hidden><a href="conta.php">' + ic('user') + ' Minha conta</a><a href="conta.php#pedidos">' + ic('box') + ' Meus pedidos</a>' +
      (Session.isStaff() ? '<a href="admin.php">' + ic('chart') + ' Painel administrativo</a>' : '') + '<hr><button id="logoutBtn">' + ic('logout') + ' Sair</button></div>';
    var b = $('#userBtn'), m = $('#userMenu');
    b.addEventListener('click', function (e) { e.stopPropagation(); m.hidden = !m.hidden; b.setAttribute('aria-expanded', String(!m.hidden)); });
    document.addEventListener('click', function () { m.hidden = true; });
    $('#logoutBtn').addEventListener('click', logout);
  }
  function logout() { Session.clear(); LS.del('ei_cart'); location.href = 'index.php'; }
  function requireLogin() {
    if (Session.token()) return true;
    var page = location.pathname.split('/').pop() + location.search + location.hash;
    location.href = 'login.php?next=' + encodeURIComponent(page || 'index.php');
    return false;
  }
  function safeNext(n) { return /^[a-z0-9_-]+\.php(\?[\w=&%.-]*)?(#[\w-]*)?$/i.test(n || '') ? n : null; }

  /* ---------- endereço (com ViaCEP opcional) ---------- */
  function fmtAddr(e) { return esc(e.logradouro) + ', ' + esc(e.numero) + (e.complemento ? ' — ' + esc(e.complemento) : '') + ' · ' + esc(e.bairro) + ' · ' + esc(e.cidade) + '/' + esc(e.uf) + ' · CEP ' + esc(String(e.cep).replace(/^(\d{5})(\d{3})$/, '$1-$2')); }
  var UFS = 'AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO'.split(' ');

  function addressForm(user) {
    return '<div class="field"><label class="f">Destinatário</label><input class="input" data-a="destinatario" value="' + esc(user ? user.nome : '') + '" autocomplete="name"></div>' +
      '<div class="row2"><div class="field"><label class="f">CEP</label><input class="input" data-a="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" autocomplete="postal-code"><div class="hint" data-a-hint></div></div><div class="field"><label class="f">UF</label><select class="input" data-a="uf"><option value="">Selecione</option>' + UFS.map(function (u) { return '<option>' + u + '</option>'; }).join('') + '</select></div></div>' +
      '<div class="row3"><div class="field"><label class="f">Rua</label><input class="input" data-a="logradouro" autocomplete="address-line1"></div><div class="field"><label class="f">Número</label><input class="input" data-a="numero"></div><div class="field"><label class="f">Compl.</label><input class="input" data-a="complemento"></div></div>' +
      '<div class="row2"><div class="field"><label class="f">Bairro</label><input class="input" data-a="bairro"></div><div class="field"><label class="f">Cidade</label><input class="input" data-a="cidade" autocomplete="address-level2"></div></div>';
  }
  function wireCep(root) {
    var cep = $('[data-a=cep]', root); if (!cep) return;
    cep.addEventListener('input', function () {
      var v = cep.value.replace(/\D/g, '').slice(0, 8); cep.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
      if (v.length === 8) {
        var hint = $('[data-a-hint]', root); hint.textContent = 'Buscando endereço…';
        fetch('https://viacep.com.br/ws/' + v + '/json/').then(function (r) { return r.json(); }).then(function (d) {
          if (d.erro) { hint.textContent = 'CEP não encontrado. Preencha manualmente.'; return; }
          hint.textContent = '';
          [['logradouro', d.logradouro], ['bairro', d.bairro], ['cidade', d.localidade], ['uf', d.uf]].forEach(function (p) { var f = $('[data-a=' + p[0] + ']', root); if (f && p[1]) f.value = p[1]; });
          var n = $('[data-a=numero]', root); if (n) n.focus();
        }).catch(function () { hint.textContent = 'Não foi possível consultar o CEP. Preencha manualmente.'; });
      }
    });
  }
  /** Mostra endereços salvos + formulário de novo. Retorna { value() } */
  function AddressPicker(container) {
    var u = Session.user();
    container.innerHTML = '<div class="skel" style="height:80px"></div>';
    return api('/conta/enderecos').catch(function () { return { enderecos: [] }; }).then(function (d) {
      var saved = d.enderecos || [], html = '';
      saved.forEach(function (a, i) {
        html += '<label class="addr-opt"><input type="radio" name="addr" value="' + a.id + '"' + (i === 0 ? ' checked' : '') + '><span><b>' + esc(a.destinatario) + '</b><br><span class="muted">' + fmtAddr(a) + '</span></span></label>';
      });
      if (saved.length) html += '<label class="addr-opt"><input type="radio" name="addr" value="new"><span><b>Usar outro endereço</b></span></label>';
      html += '<div id="newAddr"' + (saved.length ? ' hidden' : '') + '>' + addressForm(u) + '</div>';
      container.innerHTML = html;
      var form = $('#newAddr', container); wireCep(form);
      $$('input[name=addr]', container).forEach(function (r) { r.addEventListener('change', function () { form.hidden = r.value !== 'new' || !r.checked; }); });
      return {
        value: function () {
          var sel = $('input[name=addr]:checked', container);
          if (sel && sel.value !== 'new') return { endereco_id: Number(sel.value) };
          var e = {}; $$('[data-a]', form).forEach(function (f) { if (f.dataset.a) e[f.dataset.a] = f.value.trim(); });
          var req = { destinatario: 'o nome do destinatário', cep: 'o CEP', logradouro: 'a rua', numero: 'o número', bairro: 'o bairro', cidade: 'a cidade', uf: 'a UF' };
          for (var k in req) if (!e[k]) throw new Error('Informe ' + req[k] + '.');
          if (e.cep.replace(/\D/g, '').length !== 8) throw new Error('CEP inválido.');
          return { endereco: e };
        }
      };
    });
  }

  /* ---------- init ---------- */
  function init() {
    renderAuth();
    var burger = $('#burger'), nav = $('#nav');
    if (burger && nav) burger.addEventListener('click', function () { var o = nav.classList.toggle('open'); burger.setAttribute('aria-expanded', String(o)); });
    document.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-add]');
      if (b && !b.disabled) { e.preventDefault(); addToCart(b.dataset.add, 1, b).catch(function () {}); }
    });
    if ($('#cartCount')) refreshCart();
    var wa = $('#waFooter');
    if (wa) config().then(function (c) { if (c.loja_whatsapp) { wa.href = waLink(c.loja_whatsapp, 'Olá! Vim pelo site da Eletro Instal.'); wa.hidden = false; } });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

  window.EI = { api: api, ApiError: ApiError, $: $, $$: $$, esc: esc, money: money, dt: dt, dtd: dtd, img: img, ic: ic, toast: toast, modal: modal, Session: Session,
    badge: badge, label: label, ST: ST, catIcon: catIcon, METODOS: METODOS, productCard: productCard, skeletons: skeletons, config: config, waLink: waLink,
    setCartCount: setCartCount, refreshCart: refreshCart, addToCart: addToCart, requireLogin: requireLogin, safeNext: safeNext, logout: logout,
    AddressPicker: AddressPicker, addressForm: addressForm, wireCep: wireCep, fmtAddr: fmtAddr, renderAuth: renderAuth, LS: LS };
})();
