<?php
$title = 'Criar conta — Eletro Instal';
$desc  = 'Leva menos de um minuto. Compre e acompanhe seus pedidos.';
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="auth">
  <div class="auth-form">
    <div class="auth-card">
      <a href="index.php" class="brand"><span class="brand-mark"><svg class="ic"><use href="assets/img/icons.svg#i-bolt"/></svg></span><span class="brand-name">Eletro<b>Instal</b></span></a>
      <h1>Criar sua conta</h1>
      <p>Leva menos de um minuto. Compre e acompanhe seus pedidos.</p>
      <div id="msg" role="alert"></div>
      <form id="authForm" data-mode="registro" novalidate>
      <div class="field"><label class="f" for="nome">Nome completo</label><input class="input" id="nome" autocomplete="name" required placeholder="Seu nome"></div>
      <div class="field"><label class="f" for="email">E-mail</label><input class="input" id="email" type="email" autocomplete="email" required placeholder="voce@email.com"></div>
      <div class="row2"><div class="field"><label class="f" for="senha">Senha</label><input class="input" id="senha" type="password" autocomplete="new-password" required minlength="8" placeholder="Mín. 8 caracteres"></div>
      <div class="field"><label class="f" for="telefone">Telefone</label><input class="input" id="telefone" type="tel" autocomplete="tel" placeholder="(85) 90000-0000"></div></div>
      <div class="hint" style="margin:-8px 0 16px">A senha precisa ter letras e números.</div>
      <label class="check" style="margin-bottom:10px"><input type="checkbox" id="aceite" required><span>Li e aceito os <a href="termos.php" target="_blank" rel="noopener" style="text-decoration:underline">Termos de Uso</a> e a <a href="privacidade.php" target="_blank" rel="noopener" style="text-decoration:underline">Política de Privacidade</a> (LGPD).</span></label>
      <label class="check" style="margin-bottom:20px"><input type="checkbox" id="marketing"><span>Aceito receber ofertas e novidades (opcional).</span></label>
      <button class="btn btn-primary btn-block" id="submit" type="submit">Criar conta</button>
      </form>
      <p class="alt-link">Já tem conta? <a href="login.php" id="altLink">Entrar</a></p>
    </div>
  </div>
  <div class="auth-art"><svg viewBox="0 0 560 420" aria-hidden="true">
<defs><linearGradient id="pb" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff7ab8"/><stop offset="1" stop-color="#c13d86"/></linearGradient><linearGradient id="pc" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5ea0ff"/><stop offset="1" stop-color="#2a55d6"/></linearGradient>
<filter id="sh"><feDropShadow dx="0" dy="20" stdDeviation="22" flood-color="#000" flood-opacity=".5"/></filter></defs>
<g filter="url(#sh)"><rect x="30" y="40" width="500" height="320" rx="20" fill="#0e1733" stroke="#2a3a73" stroke-width="2"/>
<rect x="30" y="40" width="500" height="44" rx="20" fill="#16224a"/><rect x="30" y="64" width="500" height="20" fill="#16224a"/>
<circle cx="56" cy="62" r="6" fill="#ff5d6c"/><circle cx="78" cy="62" r="6" fill="#ffc43d"/><circle cx="100" cy="62" r="6" fill="#2ee59d"/>
<g><rect x="56" y="108" width="140" height="110" rx="12" fill="#142052"/><rect x="74" y="168" width="18" height="36" rx="4" fill="url(#pb)"/><rect x="102" y="148" width="18" height="56" rx="4" fill="url(#pc)"/><rect x="130" y="130" width="18" height="74" rx="4" fill="url(#pb)"/><rect x="158" y="156" width="18" height="48" rx="4" fill="url(#pc)"/></g>
<g><rect x="212" y="108" width="140" height="110" rx="12" fill="#142052"/><circle cx="282" cy="163" r="34" fill="none" stroke="#243474" stroke-width="14"/><circle cx="282" cy="163" r="34" fill="none" stroke="#19d3ff" stroke-width="14" stroke-dasharray="130 214" transform="rotate(-90 282 163)" stroke-linecap="round"/><text x="282" y="169" text-anchor="middle" font-family="Arial" font-weight="700" font-size="17" fill="#fff">61%</text></g>
<g><rect x="368" y="108" width="140" height="110" rx="12" fill="#142052"/><polyline points="382,196 412,170 440,182 470,140 496,152" fill="none" stroke="#2ee59d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="470" cy="140" r="5" fill="#2ee59d"/></g>
<rect x="56" y="236" width="452" height="14" rx="7" fill="#142052"/><rect x="56" y="236" width="300" height="14" rx="7" fill="url(#pc)"/>
<rect x="56" y="266" width="452" height="14" rx="7" fill="#142052"/><rect x="56" y="266" width="210" height="14" rx="7" fill="url(#pb)"/>
<rect x="56" y="296" width="452" height="14" rx="7" fill="#142052"/><rect x="56" y="296" width="380" height="14" rx="7" fill="#19d3ff"/></g>
<g transform="translate(470 360)"><circle r="34" fill="#19d3ff" opacity=".15"/><circle r="22" fill="#0b132b" stroke="#19d3ff"/><path d="M3 -12 -8 3h7l-2 10 11-15h-7z" fill="#19d3ff"/></g></svg><div class="auth-cap"><b>Painel de gestão completo</b><span>Pedidos, estoque, serviços e financeiro em um só lugar.</span></div></div>
</div>
<script src="assets/js/auth.js?v=1" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
