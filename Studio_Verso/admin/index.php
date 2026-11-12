<?php
require __DIR__ . '/_boot.php';
require __DIR__ . '/_faturamento.php';

$hoje = date('Y-m-d');
$base = ['de' => null, 'ate' => null, 'pagamento' => 'aprovado', 'status' => '', 'categoria' => 0, 'produto' => 0, 'agrupar' => 'dia'];

$kHoje = kpis($base, ['de' => $hoje, 'ate' => $hoje]);
$kMes  = kpis($base, ['de' => date('Y-m-01'), 'ate' => $hoje]);
$serie = serieTemporal(['de' => date('Y-m-d', strtotime('-29 days')), 'ate' => $hoje] + $base);

$cont = [
    'novos'    => (int)$conn->query("SELECT COUNT(*) FROM pedidos WHERE status = 'novo'")->fetch_row()[0],
    'pendPag'  => (int)$conn->query("SELECT COUNT(*) FROM pedidos WHERE status_pagamento = 'pendente'")->fetch_row()[0],
    'produtos' => (int)$conn->query('SELECT COUNT(*) FROM produtos')->fetch_row()[0],
    'clientes' => (int)$conn->query('SELECT COUNT(*) FROM usuarios WHERE is_admin = 0')->fetch_row()[0],
    'msgs'     => (int)$conn->query('SELECT COUNT(*) FROM mensagens WHERE lida = 0')->fetch_row()[0],
];
$recentes = consultar('SELECT id, nome_cliente, total, status, status_pagamento, criado_em FROM pedidos ORDER BY criado_em DESC, id DESC LIMIT 8');
$estoqueBaixo = consultar('SELECT id, nome, estoque FROM produtos WHERE sob_encomenda = 0 AND estoque <= 2 ORDER BY estoque, nome LIMIT 8');

adminHeader('Visão geral', 'dashboard');
?>
<div class="grade-kpi">
  <div class="kpi destaque"><div class="rotulo">Faturamento hoje</div><div class="valor"><?= brl($kHoje['faturamento']) ?></div><div class="sub"><?= $kHoje['pedidos'] ?> pedido(s) aprovado(s)</div></div>
  <div class="kpi"><div class="rotulo">Faturamento do mês</div><div class="valor"><?= brl($kMes['faturamento']) ?></div><div class="sub"><?= $kMes['pedidos'] ?> pedido(s) · ticket <?= brl($kMes['ticket']) ?></div></div>
  <div class="kpi"><div class="rotulo">Pedidos novos</div><div class="valor"><?= $cont['novos'] ?></div><div class="sub"><a href="<?= BASE_PATH ?>/admin/pedidos.php?status=novo" style="color:var(--roxo)">ver pedidos →</a></div></div>
  <div class="kpi"><div class="rotulo">Aguardando pagamento</div><div class="valor"><?= $cont['pendPag'] ?></div><div class="sub"><a href="<?= BASE_PATH ?>/admin/pedidos.php?pagamento=pendente" style="color:var(--roxo)">ver →</a></div></div>
  <div class="kpi"><div class="rotulo">Mensagens não lidas</div><div class="valor"><?= $cont['msgs'] ?></div><div class="sub"><a href="<?= BASE_PATH ?>/admin/mensagens.php" style="color:var(--roxo)">abrir →</a></div></div>
</div>

<div class="cartao">
  <h2>Faturamento — últimos 30 dias <a href="<?= BASE_PATH ?>/admin/faturamento.php" style="font-size:.8rem;color:var(--roxo);margin-left:8px;">detalhes e exportação →</a></h2>
  <div class="caixa-grafico" style="height:240px"><canvas id="gSerie"></canvas></div>
</div>

<div class="grade-2">
  <div class="cartao">
    <h2>Últimos pedidos</h2>
    <?php if (!$recentes): ?><p class="vazio">Nenhum pedido ainda.</p><?php else: ?>
    <div class="tabela-wrap"><table class="tabela">
      <tr><th>#</th><th>Cliente</th><th class="num">Total</th><th>Pedido</th><th>Pagamento</th></tr>
      <?php foreach ($recentes as $p): ?>
        <tr>
          <td><a href="<?= BASE_PATH ?>/admin/pedidos.php?id=<?= (int)$p['id'] ?>" style="color:var(--roxo);font-weight:700">#<?= (int)$p['id'] ?></a></td>
          <td><?= e($p['nome_cliente']) ?><br><small style="color:var(--tinta-suave)"><?= dataBr($p['criado_em']) ?></small></td>
          <td class="num"><?= brl($p['total']) ?></td>
          <td><?= badgeStatus($p['status']) ?></td>
          <td><?= badgePagamento($p['status_pagamento']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </div>
  <div class="cartao">
    <h2>Estoque baixo</h2>
    <?php if (!$estoqueBaixo): ?><p class="vazio">Tudo certo com o estoque 🎉</p><?php else: ?>
    <div class="tabela-wrap"><table class="tabela">
      <tr><th>Produto</th><th class="num">Estoque</th><th></th></tr>
      <?php foreach ($estoqueBaixo as $p): ?>
        <tr><td><?= e($p['nome']) ?></td><td class="num"><?= badge((string)(int)$p['estoque'], (int)$p['estoque'] === 0 ? 'ruim' : 'aviso') ?></td>
        <td class="num"><a href="<?= BASE_PATH ?>/admin/produtos.php?editar=<?= (int)$p['id'] ?>" style="color:var(--roxo);font-weight:700">ajustar</a></td></tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
    <p style="font-size:.85rem;color:var(--tinta-suave);margin-top:14px;"><?= $cont['produtos'] ?> produtos · <?= $cont['clientes'] ?> clientes cadastrados</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const serie = <?= json_encode($serie, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
Chart.defaults.font.family = "'Quicksand', sans-serif";
const cssVar = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
Chart.defaults.color = cssVar('--tinta-suave');
Chart.defaults.borderColor = cssVar('--linha');
window.addEventListener('tema-alterado', () => location.reload());
new Chart(document.getElementById('gSerie'), {
  type: 'line',
  data: { labels: serie.map(s => s.rotulo), datasets: [{ label: 'Faturamento', data: serie.map(s => s.fat), borderColor: '#7B4FBF', backgroundColor: 'rgba(123,79,191,.15)', fill: true, tension: .3, pointRadius: 2 }] },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' R$ ' + Number(c.parsed.y).toLocaleString('pt-BR', {minimumFractionDigits: 2}) } } },
             scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, x: { ticks: { maxTicksLimit: 10 } } } }
});
</script>
<?php adminFooter(); ?>
