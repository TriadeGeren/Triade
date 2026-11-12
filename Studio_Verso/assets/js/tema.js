// Alternar modo claro/escuro. A escolha fica salva no navegador do visitante.
(function () {
  var CHAVE = 'sv_tema';
  function atual() { return document.documentElement.getAttribute('data-tema') === 'escuro' ? 'escuro' : 'claro'; }
  function aplicar(t) {
    if (t === 'escuro') document.documentElement.setAttribute('data-tema', 'escuro');
    else document.documentElement.removeAttribute('data-tema');
    document.querySelectorAll('[data-alternar-tema]').forEach(function (b) {
      b.textContent = t === 'escuro' ? '☀️' : '🌙';
      b.setAttribute('title', t === 'escuro' ? 'Mudar para o modo claro' : 'Mudar para o modo escuro');
      b.setAttribute('aria-label', b.getAttribute('title'));
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    aplicar(atual());
    document.querySelectorAll('[data-alternar-tema]').forEach(function (b) {
      b.addEventListener('click', function () {
        var novo = atual() === 'escuro' ? 'claro' : 'escuro';
        try { localStorage.setItem(CHAVE, novo); } catch (e) {}
        aplicar(novo);
        window.dispatchEvent(new Event('tema-alterado'));
      });
    });
  });
})();
