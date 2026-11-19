(function () {
  var E = window.EI, $ = E.$, all = [], cat = '';

  function draw() {
    var list = all.filter(function (s) { return !cat || s.categoria_slug === cat; });
    $('#list').innerHTML = list.length ? list.map(function (s) {
      return '<article class="svc"><span class="cat-ic">' + E.ic('bolt') + '</span><span class="pbrand">' + E.esc(s.categoria_nome) + '</span><h3>' + E.esc(s.nome) + '</h3><p>' + E.esc(s.descricao || '') + '</p>' +
        (s.tempo_estimado_min ? '<div class="muted" style="font-size:.84rem">' + E.ic('calendar') + ' Tempo médio: ' + (s.tempo_estimado_min >= 60 ? Math.round(s.tempo_estimado_min / 60 * 10) / 10 + ' h' : s.tempo_estimado_min + ' min') + '</div>' : '') +
        '<div class="from">' + (s.tipo_preco === 'preco_inicial' ? '<small>a partir de</small>' + E.money(s.preco_inicial) : 'Sob orçamento') + '</div>' +
        '<button class="btn btn-primary btn-sm" data-s="' + s.id + '">Solicitar orçamento</button></article>';
    }).join('') : '<div class="empty" style="grid-column:1/-1">Nenhum serviço nesta categoria.</div>';
  }

  function ask(s) {
    if (!E.requireLogin()) return;
    var today = new Date().toISOString().slice(0, 10);
    var m = E.modal({ title: 'Solicitar: ' + s.nome, size: 'lg', noFocus: true,
      body: '<div id="form"><div class="field"><label class="f">Descreva o que você precisa *</label><textarea class="input" id="desc" maxlength="2000" placeholder="Ex.: preciso trocar 3 tomadas na cozinha e instalar um ponto para o forno elétrico."></textarea></div>' +
        '<div class="row2"><div class="field"><label class="f">Data preferida</label><input class="input" type="date" id="data" min="' + today + '"></div><div class="field"><label class="f">Período</label><select class="input" id="per"><option value="qualquer">Qualquer horário</option><option value="manha">Manhã</option><option value="tarde">Tarde</option><option value="noite">Noite</option></select></div></div>' +
        '<h4 style="margin:6px 0 12px">Local do serviço</h4><div id="addr"></div><div id="err"></div></div>',
      footer: '<button class="btn btn-ghost btn-sm" data-c>Cancelar</button><button class="btn btn-primary btn-sm" id="send">Enviar solicitação</button>' });
    var picker; E.AddressPicker(m.$('#addr')).then(function (p) { picker = p; });
    m.$('[data-c]').onclick = m.close;
    m.$('#send').onclick = function () {
      var err = m.$('#err'), b = this; err.innerHTML = '';
      var desc = m.$('#desc').value.trim(); if (desc.length < 10) { err.innerHTML = '<div class="alert err">Descreva o serviço com pelo menos 10 caracteres.</div>'; return; }
      var addr; try { addr = picker.value(); } catch (e) { err.innerHTML = '<div class="alert err">' + E.esc(e.message) + '</div>'; return; }
      b.disabled = true; b.textContent = 'Enviando…';
      E.api('/servicos/solicitacoes', { method: 'POST', body: Object.assign({ servico_id: s.id, descricao_cliente: desc, data_preferida: m.$('#data').value || null, periodo_preferido: m.$('#per').value }, addr) })
        .then(function (d) {
          var wa = d.whatsapp ? E.waLink(d.whatsapp, 'Olá! Solicitei o serviço "' + s.nome + '" (protocolo ' + d.solicitacao.protocolo + ').') : null;
          m.$('.modal-b').innerHTML = '<div class="success"><div class="ok-ic">' + E.ic('check') + '</div><h2>Solicitação enviada!</h2><p class="muted">Seu protocolo:</p><div class="num">' + E.esc(d.solicitacao.protocolo) + '</div><p class="muted">Nossa equipe vai analisar e enviar o orçamento. Acompanhe em “Minha conta”.</p></div>';
          m.$('.modal-f').innerHTML = (wa ? '<a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="' + E.esc(wa) + '">' + E.ic('whatsapp') + ' Falar no WhatsApp</a>' : '') + '<a class="btn btn-primary btn-sm" href="conta.php#servicos">Ver minhas solicitações</a>';
        }).catch(function (e) { err.innerHTML = '<div class="alert err">' + E.esc(e.message) + '</div>'; b.disabled = false; b.textContent = 'Enviar solicitação'; });
    };
  }

  $('#list').addEventListener('click', function (e) { var b = e.target.closest('[data-s]'); if (b) ask(all.filter(function (s) { return s.id === +b.dataset.s; })[0]); });
  $('#catChips').addEventListener('click', function (e) { var c = e.target.closest('[data-c]'); if (!c) return; cat = c.dataset.c; E.$$('#catChips .chip').forEach(function (x) { x.classList.toggle('on', x === c); }); draw(); });

  $('#list').innerHTML = '<div class="skel" style="height:240px"></div><div class="skel" style="height:240px"></div><div class="skel" style="height:240px"></div>';
  E.api('/servicos').then(function (d) {
    all = d.servicos || [];
    var seen = {}, chips = '<button class="chip on" data-c="">Todos</button>';
    all.forEach(function (s) { if (!seen[s.categoria_slug]) { seen[s.categoria_slug] = 1; chips += '<button class="chip" data-c="' + E.esc(s.categoria_slug) + '">' + E.esc(s.categoria_nome) + '</button>'; } });
    $('#catChips').innerHTML = chips; draw();
  }).catch(function (e) { $('#list').innerHTML = '<div class="empty" style="grid-column:1/-1">' + E.esc(e.message) + '</div>'; });
})();
