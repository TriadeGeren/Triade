<?php
$title = 'Política de Privacidade — Eletro Instal';
$desc  = 'Como a Eletro Instal trata seus dados pessoais conforme a LGPD.';
$active = '';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap" style="max-width:820px">
  <div class="page-head"><div class="crumbs"><a href="index.php">Início</a> / Política de Privacidade</div><h1>Política de Privacidade</h1><p class="muted" style="margin-top:8px">Última atualização: <?= date('d/m/Y') ?></p></div>
  <div class="panel legal-doc" style="margin:20px 0 70px;line-height:1.75">
    <h2>1. Quem somos</h2>
    <p>A Eletro Instal é a controladora dos dados pessoais tratados neste site, nos termos da Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD).</p>
    <h2>2. Dados que coletamos</h2>
    <ul><li>Dados de cadastro: nome, e-mail, telefone e senha (armazenada de forma criptografada);</li><li>Dados de entrega e de local de serviço: endereço informado por você;</li><li>Dados de pedidos e de solicitações de serviço;</li><li>Dados técnicos de segurança: endereço IP e data/hora de acessos e tentativas de login.</li></ul>
    <h2>3. Para que usamos</h2>
    <ul><li>Processar pedidos, entregas e serviços contratados (execução de contrato);</li><li>Garantir a segurança da conta e prevenir fraudes (legítimo interesse);</li><li>Cumprir obrigações legais e fiscais;</li><li>Enviar ofertas e novidades, somente se você marcar essa opção (consentimento).</li></ul>
    <h2>4. Compartilhamento</h2>
    <p>Não vendemos seus dados. Eles podem ser compartilhados apenas com prestadores necessários à entrega e à operação da loja, e com autoridades quando exigido por lei. Ao digitar um CEP, o endereço pode ser consultado no serviço público ViaCEP.</p>
    <h2>5. Seus direitos</h2>
    <p>Você pode solicitar confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação dos dados e revogação do consentimento, entrando em contato pelos canais de atendimento do site.</p>
    <h2>6. Retenção e segurança</h2>
    <p>Mantemos os dados pelo tempo necessário às finalidades acima e a obrigações legais. Adotamos medidas técnicas como criptografia de senhas, bloqueio de tentativas excessivas de login e controle de acesso por perfil.</p>
    <h2>7. Cookies e armazenamento local</h2>
    <p>Usamos armazenamento do navegador apenas para manter sua sessão e seu carrinho. Não utilizamos cookies de publicidade.</p>
  </div>
</div>
<style>.legal-doc h2{font-size:1.1rem;margin:26px 0 8px}.legal-doc h2:first-child{margin-top:0}.legal-doc p,.legal-doc li{color:#c4cee0;font-size:.97rem}.legal-doc ul{margin:8px 0 8px 22px}</style>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
