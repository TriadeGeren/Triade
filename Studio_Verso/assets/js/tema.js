// Alternar modo claro/escuro. A escolha fica salva no navegador do visitante.
// Sem escolha salva, segue o tema do aparelho (e acompanha se ele mudar).
(function () {
  var CHAVE = 'sv_tema';
  var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

  function salvo() { try { return localStorage.getItem(CHAVE); } catch (e) { return null; } }
  function atual() { return document.documentElement.getAttribute('data-tema') === 'escuro' ? 'escuro' : 'claro'; }

  function aplicar(t) {
    var raiz = document.documentElement;
    if (t === 'escuro') raiz.setAttribute('data-tema', 'escuro');
    else raiz.removeAttribute('data-tema');
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', t === 'escuro' ? '#16121f' : '#FFF8EE');
    document.querySelectorAll('[data-alternar-tema]').forEach(function (b) {
      b.textContent = t === 'escuro' ? '☀️' : '🌙';
      b.setAttribute('title', t === 'escuro' ? 'Mudar para o modo claro' : 'Mudar para o modo escuro');
      b.setAttribute('aria-label', b.getAttribute('title'));
      b.setAttribute('aria-pressed', t === 'escuro' ? 'true' : 'false');
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

  // Mudou o tema do aparelho e o visitante nunca escolheu: acompanha.
  if (mq && mq.addEventListener) {
    mq.addEventListener('change', function (ev) {
      if (salvo()) return;
      aplicar(ev.matches ? 'escuro' : 'claro');
      window.dispatchEvent(new Event('tema-alterado'));
    });
  }
})();
