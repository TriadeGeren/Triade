(function () {
  var E = window.EI, $ = E.$, form = $('#authForm'), mode = form.dataset.mode, msg = $('#msg');
  var next = E.safeNext(new URLSearchParams(location.search).get('next'));
  if (next) { var a = $('#altLink'); a.href = a.getAttribute('href') + '?next=' + encodeURIComponent(next); }
  if (E.Session.token() && E.Session.user()) { location.replace(next || (E.Session.isStaff() ? 'admin.php' : 'conta.php')); return; }

  function err(t) { msg.innerHTML = '<div class="alert err">' + E.esc(t) + '</div>'; }
  form.addEventListener('submit', function (e) {
    e.preventDefault(); msg.innerHTML = '';
    var btn = $('#submit'), label = btn.textContent, body, path;
    var email = $('#email').value.trim(), senha = $('#senha').value;
    if (mode === 'login') {
      if (!email || !senha) return err('Informe e-mail e senha.');
      path = '/auth/login'; body = { email: email, senha: senha };
    } else {
      var nome = $('#nome').value.trim();
      if (nome.length < 3) return err('Informe seu nome completo.');
      if (!/^\S+@\S+\.\S+$/.test(email)) return err('Informe um e-mail válido.');
      if (senha.length < 8 || !/[A-Za-z]/.test(senha) || !/\d/.test(senha)) return err('A senha deve ter no mínimo 8 caracteres, com letras e números.');
      if (!$('#aceite').checked) return err('É necessário aceitar os Termos de Uso e a Política de Privacidade.');
      path = '/auth/register'; body = { nome: nome, email: email, senha: senha, telefone: $('#telefone').value.trim(), aceite: true, marketing: $('#marketing').checked };
    }
    btn.disabled = true; btn.textContent = 'Aguarde…';
    E.api(path, { method: 'POST', body: body }).then(function (d) {
      E.Session.set(d.token, d.user);
      var dest = d.user.trocar_senha ? 'conta.php#senha' : (next || (d.user.papel_id === 1 || d.user.papel_id === 2 ? 'admin.php' : 'index.php'));
      location.href = dest;
    }).catch(function (er) { err(er.message); btn.disabled = false; btn.textContent = label; });
  });
})();
