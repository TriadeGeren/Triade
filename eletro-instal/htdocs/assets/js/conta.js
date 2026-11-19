(function () {
  var E = window.EI, $ = E.$, pane = $('#pane');
  if (!E.requireLogin()) return;
  var user = E.Session.user() || {};
  $('#hello').textContent = 'Olá, ' + String(user.nome || '').split(' ')[0];

  function table(head, rows, empty) {
    if (!rows.length) return '<div class="empty">' + E.ic('box') + '<p>' + empty + '</p></div>';
    return '<div class="tbl-wrap"><table class="tbl"><thead><tr>' + head.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>';
  }
  function loading() { pane.innerHTML = '<div class="skel" style="height:200px"></div>'; }
  function fail(e) { pane.innerHTML = '<div class="alert err">' + E.esc(e.message) + '</div>'; }

  var tabs = {
    pedidos: function () {
      loading();
      E.api('/conta/pedidos').then(function (d) {
        pane.innerHTML = table(['Pedido', 'Data', 'Itens', 'Total', 'Status', ''], d.pedidos.map(function (p) {
          return '<tr><td><b>' + E.esc(p.numero) + '</b></td><td>' + E.dt(p.criado_em) + '</td><td>' + p.itens + '</td><td>' + E.money(p.total) + '</td><td>' + E.badge(p.status) + '</td><td><button class="btn btn-dark btn-sm" data-ped="' + E.esc(p.numero) + '">Detalhes</button></td></tr>';
        }), 'Você ainda não fez nenhum pedido. <a href="catalogo.php" style="color:var(--accent)">Ver catálogo</a>');
      }).catch(fail);
    },
    servicos: function () {
      loading();
      E.api('/servicos/solicitacoes').then(function (d) {
        pane.innerHTML = table(['Protocolo', 'Serviço', 'Data', 'Orçamento', 'Status', ''], d.solicitacoes.map(function (s) {
          return '<tr><td><b>' + E.esc(s.protocolo) + '</b></td><td>' + E.esc(s.servico_nome) + '</td><td>' + E.dt(s.criado_em) + '</td><td>' + (s.valor_orcado !== null ? E.money(s.valor_orcado) : '—') + '</td><td>' + E.badge(s.status) + '</td><td><button class="btn btn-dark btn-sm" data-sol="' + s.id + '">Detalhes</button></td></tr>';
        }), 'Nenhuma solicitação. <a href="servicos.php" style="color:var(--accent)">Ver serviços</a>');
      }).catch(fail);
    },
    enderecos: function () {
      loading();
      E.api('/conta/enderecos').then(function (d) {
        pane.innerHTML = '<div style="display:flex;justify-content:flex-end;margin-bottom:14px"><button class="btn btn-primary btn-sm" id="newAddr">' + E.ic('plus') + ' Novo endereço</button></div>' +
          (d.enderecos.length ? d.enderecos.map(function (a) { return '<div class="panel" style="margin-bottom:12px"><b>' + E.esc(a.destinatario) + '</b>' + (a.padrao ? ' <span class="badge b-info">Padrão</span>' : '') + '<br><span class="muted">' + E.fmtAddr(a) + '</span></div>'; }).join('') : '<div class="empty">' + E.ic('home') + '<p>Nenhum endereço salvo. Eles são salvos automaticamente ao finalizar um pedido.</p></div>');
        $('#newAddr').onclick = function () {
          var m = E.modal({ title: 'Novo endereço', body: '<div>' + E.addressForm(user) + '<div id="err"></div></div>', footer: '<button class="btn btn-ghost btn-sm" data-c>Cancelar</button><button class="btn btn-primary btn-sm" id="save">Salvar</button>' });
          E.wireCep(m.el); m.$('[data-c]').onclick = m.close;
          m.$('#save').onclick = function () {
            var e = {}; m.$$('[data-a]').forEach(function (f) { if (f.dataset.a) e[f.dataset.a] = f.value.trim(); });
            E.api('/conta/enderecos', { method: 'POST', body: { endereco: e } }).then(function () { m.close(); E.toast('Endereço salvo'); tabs.enderecos(); })
              .catch(function (er) { m.$('#err').innerHTML = '<div class="alert err">' + E.esc(er.message) + '</div>'; });
          };
        };
      }).catch(fail);
    },
    senha: function () {
      pane.innerHTML = '<div class="panel" style="max-width:480px"><h2>Alterar senha</h2>' +
        (user.trocar_senha ? '<div class="alert info">Por segurança, defina uma nova senha para continuar usando o sistema.</div>' : '') +
        '<div id="msg"></div><div class="field"><label class="f" for="a">Senha atual</label><input class="input" id="a" type="password" autocomplete="current-password"></div>' +
        '<div class="field"><label class="f" for="n">Nova senha</label><input class="input" id="n" type="password" autocomplete="new-password"><div class="hint">Mínimo de 8 caracteres, com letras e números.</div></div>' +
        '<div class="field"><label class="f" for="c">Confirmar nova senha</label><input class="input" id="c" type="password" autocomplete="new-password"></div>' +
        '<button class="btn btn-primary" id="go">Salvar nova senha</button>' +
        '<hr style="border:0;border-top:1px solid var(--line);margin:28px 0"><button class="btn btn-ghost btn-sm" id="out">' + E.ic('logout') + ' Sair da conta</button></div>';
      $('#out').onclick = E.logout;
      $('#go').onclick = function () {
        var msg = $('#msg'), a = $('#a').value, n = $('#n').value, c = $('#c').value;
        function err(t) { msg.innerHTML = '<div class="alert err">' + E.esc(t) + '</div>'; }
        if (!a) return err('Informe a senha atual.');
        if (n.length < 8 || !/[A-Za-z]/.test(n) || !/\d/.test(n)) return err('A nova senha deve ter no mínimo 8 caracteres, com letras e números.');
        if (n !== c) return err('A confirmação não confere com a nova senha.');
        var b = this; b.disabled = true;
        E.api('/auth/change-password', { method: 'POST', body: { senha_atual: a, nova_senha: n } }).then(function (d) {
          E.Session.set(E.Session.token(), d.user); user = d.user;
          E.toast('Senha alterada com sucesso');
          if (E.Session.isStaff()) setTimeout(function () { location.href = 'admin.php'; }, 900); else { msg.innerHTML = '<div class="alert ok">Senha alterada com sucesso.</div>'; b.disabled = false; }
        }).catch(function (er) { err(er.message); b.disabled = false; });
      };
    }
  };

  function show(t) {
    if (user.trocar_senha) t = 'senha';
    if (!tabs[t]) t = 'pedidos';
    E.$$('#tabs button').forEach(function (b) { b.classList.toggle('on', b.dataset.t === t); });
    tabs[t]();
  }
  $('#tabs').addEventListener('click', function (e) { var b = e.target.closest('button'); if (b) { history.replaceState(null, '', '#' + b.dataset.t); show(b.dataset.t); } });

  pane.addEventListener('click', function (e) {
    var p = e.target.closest('[data-ped]'), s = e.target.closest('[data-sol]');
    if (p) E.api('/conta/pedidos/' + encodeURIComponent(p.dataset.ped)).then(function (d) {
      var o = d.pedido;
      E.modal({ title: 'Pedido ' + o.numero, size: 'lg', body:
        '<div style="display:flex;gap:10px;align-items:center;margin-bottom:16px">' + E.badge(o.status) + '<span class="muted">' + E.dt(o.criado_em) + '</span></div>' +
        '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Produto</th><th>Qtd</th><th>Unit.</th><th>Subtotal</th></tr></thead><tbody>' + d.itens.map(function (i) { return '<tr><td>' + E.esc(i.nome_produto) + '<br><small class="muted">' + E.esc(i.sku) + '</small></td><td>' + i.quantidade + '</td><td>' + E.money(i.preco_unitario) + '</td><td>' + E.money(i.subtotal) + '</td></tr>'; }).join('') + '</tbody></table></div>' +
        '<div style="text-align:right;margin:14px 0;line-height:1.9"><div class="muted">Subtotal ' + E.money(o.subtotal) + (+o.desconto ? ' · Desconto -' + E.money(o.desconto) : '') + ' · Frete ' + (+o.frete ? E.money(o.frete) : 'Grátis') + '</div><b style="font-size:1.2rem">Total ' + E.money(o.total) + '</b></div>' +
        (o.entrega_logradouro ? '<p class="muted"><b style="color:var(--text)">Entrega:</b> ' + E.esc(o.entrega_destinatario) + ' — ' + E.esc(o.entrega_logradouro) + ', ' + E.esc(o.entrega_numero) + ' · ' + E.esc(o.entrega_bairro) + ' · ' + E.esc(o.entrega_cidade) + '/' + E.esc(o.entrega_uf) + '</p>' : '') +
        (d.pagamento ? '<p class="muted" style="margin-top:6px"><b style="color:var(--text)">Pagamento:</b> ' + E.esc(E.METODOS[d.pagamento.metodo] || d.pagamento.metodo) + ' — ' + E.badge(d.pagamento.status) + '</p>' : '') +
        '<h4 style="margin:20px 0 10px">Histórico</h4>' + d.historico.map(function (h) { return '<div style="padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem">' + E.badge(h.status_novo) + ' <span class="muted">' + E.dt(h.criado_em) + (h.observacao ? ' · ' + E.esc(h.observacao) : '') + '</span></div>'; }).join('') });
    }).catch(function (er) { E.toast(er.message, 'err'); });
    if (s) E.api('/servicos/solicitacoes/' + s.dataset.sol).then(function (d) {
      var o = d.solicitacao;
      E.modal({ title: 'Solicitação ' + o.protocolo, body:
        '<p><b>' + E.esc(o.servico_nome) + '</b> ' + E.badge(o.status) + '</p><p class="muted" style="margin:10px 0;white-space:pre-wrap">' + E.esc(o.descricao_cliente) + '</p>' +
        (o.valor_orcado !== null ? '<div class="alert info">Orçamento: <b>' + E.money(o.valor_orcado) + '</b>' + (o.prazo_validade_orcamento ? ' · válido até ' + E.dtd(o.prazo_validade_orcamento) : '') + '. Para aprovar, fale com a equipe.</div>' : '') +
        '<h4 style="margin:16px 0 10px">Histórico</h4>' + d.historico.map(function (h) { return '<div style="padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem">' + E.badge(h.status_novo) + ' <span class="muted">' + E.dt(h.criado_em) + (h.observacao ? ' · ' + E.esc(h.observacao) : '') + '</span></div>'; }).join('') });
    }).catch(function (er) { E.toast(er.message, 'err'); });
  });

  show((location.hash || '#pedidos').slice(1));
})();
