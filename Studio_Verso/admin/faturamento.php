<?php
require __DIR__ . '/_boot.php';
require __DIR__ . '/_faturamento.php';
require __DIR__ . '/_export.php';

$f = lerFiltros();

/* ---------- Exportação (CSV / XLSX) com os MESMOS filtros da tela ---------- */
if (isset($_GET['export'])) {
    $formato = $_GET['export'];
    $nomeArq = 'faturamento_' . ($f['de'] ?: 'inicio') . '_a_' . ($f['ate'] ?: date('Y-m-d'));

    $detalhe = [
        'nome' => 'Pedidos detalhados',
        'cabecalho' => ['Pedido', 'Data', 'Cliente', 'E-mail', 'WhatsApp', 'Produto', 'Categoria', 'Qtd', 'Preço unitário', 'Subtotal', 'Status do pedido', 'Pagamento'],
        'tipos' => ['int', 'data', 'texto', 'texto', 'texto', 'texto', 'texto', 'int', 'dinheiro', 'dinheiro', 'texto', 'texto'],
        'linhas' => array_map(fn($l) => [
            (int)$l['pedido'], $l['criado_em'], $l['nome_cliente'], $l['email_cliente'], $l['whatsapp_cliente'],
            $l['produto'], $l['categoria'], (int)$l['quantidade'], (float)$l['preco_unitario'], (float)$l['subtotal'],
            STATUS_PEDIDO[$l['status']] ?? $l['status'], STATUS_PAGAMENTO[$l['status_pagamento']] ?? $l['status_pagamento'],
        ], linhasDetalhadas($f)),
    ];

    $serie = serieTemporal($f);
    $resumo = [
        'nome' => 'Resumo por ' . ($f['agrupar'] === 'mes' ? 'mês' : 'dia'),
        'cabecalho' => [$f['agrupar'] === 'mes' ? 'Mês' : 'Dia', 'Pedidos', 'Faturamento'],
        'tipos' => ['texto', 'int', 'dinheiro'],
        'linhas' => array_map(fn($s) => [$s['rotulo'] . ($f['agrupar'] === 'dia' ? '/' . substr($s['periodo'], 0, 4) : ''), $s['pedidos'], $s['fat']], $serie),
    ];
    $totalLinha = count($resumo['linhas']) + 1;
    $resumoXlsx = $resumo;
    if ($resumo['linhas']) {
        $resumoXlsx['linhas'][] = ['TOTAL', ['f' => "SUM(B2:B$totalLinha)"], ['f' => "SUM(C2:C$totalLinha)"]];
    }

    $porCat = [
        'nome' => 'Por categoria',
        'cabecalho' => ['Categoria', 'Itens vendidos', 'Faturamento'],
        'tipos' => ['texto', 'int', 'dinheiro'],
        'linhas' => array_map(fn($c) => [$c['nome'], (int)$c['itens'], (float)$c['fat']], porCategoria($f)),
    ];
    $porProd = [
        'nome' => 'Por produto',
        'cabecalho' => ['Produto', 'Categoria', 'Itens vendidos', 'Faturamento'],
        'tipos' => ['texto', 'texto', 'int', 'dinheiro'],
        'linhas' => array_map(fn($c) => [$c['nome'], $c['categoria'], (int)$c['itens'], (float)$c['fat']], topProdutos($f, 1000)),
    ];

    if ($formato === 'xlsx') {
        exportarXlsx($nomeArq, [$detalhe, $resumoXlsx, $porCat, $porProd]);
    }
    if ($formato === 'csv') {
        $tipo = $_GET['tipo'] ?? 'detalhado';
        if ($tipo === 'resumo') exportarCsv($nomeArq . '_resumo', $resumo);
        exportarCsv($nomeArq, $detalhe);
    }
    http_response_code(400);
    die('Formato inválido.');
}

/* ---------- Dados da tela ---------- */
$atual = kpis($f);
$ant = ($pa = periodoAnterior($f)) ? kpis($f, $pa) : null;
$serie = serieTemporal($f);
$cats = porCategoria($f);
$tops = topProdutos($f, 10);
$pags = porPagamento($f);

$listaCats = consultar('SELECT id, nome FROM categorias ORDER BY nome');
$listaProds = consultar('SELECT id, nome FROM produtos ORDER BY nome');

function delta(float $atual, ?float $ant): string {
    if ($ant === null) return '';
    if ($ant == 0) return $atual > 0 ? '<span class="sub">novo período com vendas</span>' : '';
    $pct = ($atual - $ant) / $ant * 100;
    $cor = $pct >= 0 ? '#2c6b4a' : '#9c3b3b';
    return '<span class="sub" style="color:' . $cor . ';font-weight:700;">' . ($pct >= 0 ? '▲ ' : '▼ ') . number_format(abs($pct), 1, ',', '.') . '% vs período anterior</span>';
}

// Query string atual (sem preset/de/ate) para montar os botões
$qBase = $_GET; unset($qBase['preset'], $qBase['de'], $qBase['ate'], $qBase['export'], $qBase['filtrar'], $qBase['p']);
function linkPreset(array $qBase, string $preset): string { return '?' . http_build_query($qBase + ['preset' => $preset]); }
$qExport = $_GET; unset($qExport['export'], $qExport['tipo'], $qExport['preset']);
$qExport['de'] = $f['de'] ?? ''; $qExport['ate'] = $f['ate'] ?? ''; $qExport['filtrar'] = 1;
$qExport['pagamento'] = $f['pagamento']; $qExport['agrupar'] = $f['agrupar'];

$presets = ['7d' => '7 dias', '30d' => '30 dias', '90d' => '90 dias', 'mes' => 'Este mês', 'mes_passado' => 'Mês passado', 'ano' => 'Este ano', 'tudo' => 'Tudo'];

adminHeader('Faturamento', 'faturamento');
?>
<form method="GET" class="cartao filtros-fat">
  <input type="hidden" name="filtrar" value="1">
  <div class="presets">
    <?php foreach ($presets as $k => $rot): ?>
      <a href="<?= e(linkPreset($qBase, $k)) ?>" class="<?= $f['preset'] === $k ? 'ativo' : '' ?>"><?= e($rot) ?></a>
    <?php endforeach; ?>
  </div>
  <div class="form-grade">
    <div class="campo"><label>De</label><input type="date" name="de" value="<?= e($f['de'] ?? '') ?>"></div>
    <div class="campo"><label>Até</label><input type="date" name="ate" value="<?= e($f['ate'] ?? '') ?>"></div>
    <div class="campo"><label>Pagamento</label>
      <select name="pagamento">
        <option value="">Todos</option>
        <?php foreach (STATUS_PAGAMENTO as $k => $rot): ?>
          <option value="<?= $k ?>" <?= $f['pagamento'] === $k ? 'selected' : '' ?>><?= e($rot) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo"><label>Status do pedido</label>
      <select name="status">
        <option value="">Todos</option>
        <?php foreach (STATUS_PEDIDO as $k => $rot): ?>
          <option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= e($rot) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo"><label>Categoria</label>
      <select name="categoria">
        <option value="0">Todas</option>
        <?php foreach ($listaCats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $f['categoria'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo"><label>Produto</label>
      <select name="produto">
        <option value="0">Todos</option>
        <?php foreach ($listaProds as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $f['produto'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo"><label>Agrupar por</label>
      <select name="agrupar">
        <option value="dia" <?= $f['agrupar'] === 'dia' ? 'selected' : '' ?>>Dia</option>
        <option value="mes" <?= $f['agrupar'] === 'mes' ? 'selected' : '' ?>>Mês</option>
      </select>
    </div>
    <div class="acoes-linha">
      <button class="btn btn-primario btn-peq" type="submit">Aplicar filtros</button>
      <a class="btn btn-claro btn-peq" href="<?= BASE_PATH ?>/admin/faturamento.php">Limpar</a>
    </div>
  </div>
  <div class="exportar">
    <strong style="font-size:.85rem;">Exportar o que está filtrado:</strong>
    <a class="btn btn-verde btn-peq" href="?<?= e(http_build_query($qExport + ['export' => 'xlsx'])) ?>">⬇ Excel (.xlsx)</a>
    <a class="btn btn-claro btn-peq" href="?<?= e(http_build_query($qExport + ['export' => 'csv', 'tipo' => 'detalhado'])) ?>">⬇ CSV detalhado</a>
    <a class="btn btn-claro btn-peq" href="?<?= e(http_build_query($qExport + ['export' => 'csv', 'tipo' => 'resumo'])) ?>">⬇ CSV resumo</a>
  </div>
</form>

<p style="color:var(--tinta-suave);font-size:.85rem;margin-top:-8px;"><?= e(descricaoFiltros($f)) ?></p>

<div class="grade-kpi">
  <div class="kpi destaque"><div class="rotulo">Faturamento</div><div class="valor"><?= brl($atual['faturamento']) ?></div><?= delta($atual['faturamento'], $ant['faturamento'] ?? null) ?></div>
  <div class="kpi"><div class="rotulo">Pedidos</div><div class="valor"><?= $atual['pedidos'] ?></div><?= delta($atual['pedidos'], isset($ant) ? $ant['pedidos'] : null) ?></div>
  <div class="kpi"><div class="rotulo">Ticket médio</div><div class="valor"><?= brl($atual['ticket']) ?></div><?= delta($atual['ticket'], $ant['ticket'] ?? null) ?></div>
  <div class="kpi"><div class="rotulo">Itens vendidos</div><div class="valor"><?= $atual['itens'] ?></div><?= delta($atual['itens'], isset($ant) ? $ant['itens'] : null) ?></div>
</div>

<?php if ($atual['pedidos'] === 0): ?>
  <div class="cartao vazio">Nenhum pedido encontrado com esses filtros. Tente ampliar o período ou mudar o status de pagamento.</div>
<?php else: ?>
<div class="grade-graficos">
  <div class="cartao"><h2>Faturamento por <?= $f['agrupar'] === 'mes' ? 'mês' : 'dia' ?></h2><div class="caixa-grafico"><canvas id="gSerie"></canvas></div></div>
  <div class="cartao"><h2>Por categoria</h2><div class="caixa-grafico"><canvas id="gCategoria"></canvas></div></div>
</div>
<div class="grade-2">
  <div class="cartao"><h2>Produtos que mais faturam</h2><div class="caixa-grafico"><canvas id="gProdutos"></canvas></div></div>
  <div class="cartao"><h2>Pedidos por situação de pagamento</h2><div class="caixa-grafico"><canvas id="gPagamento"></canvas></div></div>
</div>

<div class="cartao">
  <h2>Ranking de produtos</h2>
  <div class="tabela-wrap">
  <table class="tabela">
    <tr><th>#</th><th>Produto</th><th>Categoria</th><th class="num">Itens</th><th class="num">Faturamento</th></tr>
    <?php foreach ($tops as $i => $t): ?>
      <tr><td><?= $i + 1 ?></td><td><?= e($t['nome']) ?></td><td><?= e($t['categoria']) ?></td><td class="num"><?= (int)$t['itens'] ?></td><td class="num"><?= brl($t['fat']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const serie = <?= json_encode($serie, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
const cats = <?= json_encode($cats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
const tops = <?= json_encode($tops, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
const pags = <?= json_encode($pags, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
const nomesPag = <?= json_encode(STATUS_PAGAMENTO, JSON_UNESCAPED_UNICODE) ?>;

const cores = ['#7B4FBF', '#F0923B', '#4FB4C4', '#F4A6C1', '#4B2E7A', '#2c8a5e', '#B89AE0', '#d97e2b'];
const brl = v => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
Chart.defaults.font.family = "'Quicksand', sans-serif";
const cssVar = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
Chart.defaults.color = cssVar('--tinta-suave');
Chart.defaults.borderColor = cssVar('--linha');
window.addEventListener('tema-alterado', () => location.reload());
const tooltipBRL = { callbacks: { label: c => ' ' + (c.dataset.label ? c.dataset.label + ': ' : '') + brl(c.parsed.y ?? c.parsed.x ?? c.parsed) } };

new Chart(document.getElementById('gSerie'), {
  data: {
    labels: serie.map(s => s.rotulo),
    datasets: [
      { type: 'bar', label: 'Faturamento', data: serie.map(s => s.fat), backgroundColor: '#7B4FBF', borderRadius: 6, yAxisID: 'y' },
      { type: 'line', label: 'Pedidos', data: serie.map(s => s.pedidos), borderColor: '#F0923B', backgroundColor: '#F0923B', tension: .3, yAxisID: 'y2' }
    ]
  },
  options: {
    maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
    plugins: { tooltip: { callbacks: { label: c => c.dataset.type === 'line' ? ' Pedidos: ' + c.parsed.y : ' Faturamento: ' + brl(c.parsed.y) } } },
    scales: {
      y: { beginAtZero: true, ticks: { callback: v => brl(v) } },
      y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
      x: { ticks: { maxTicksLimit: 14 } }
    }
  }
});

new Chart(document.getElementById('gCategoria'), {
  type: 'doughnut',
  data: { labels: cats.map(c => c.nome), datasets: [{ data: cats.map(c => +c.fat), backgroundColor: cores, borderWidth: 2, borderColor: cssVar('--creme-alto') }] },
  options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + brl(c.parsed) } } } }
});

new Chart(document.getElementById('gProdutos'), {
  type: 'bar',
  data: { labels: tops.map(t => t.nome), datasets: [{ label: 'Faturamento', data: tops.map(t => +t.fat), backgroundColor: '#4FB4C4', borderRadius: 6 }] },
  options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + brl(c.parsed.x) } } }, scales: { x: { beginAtZero: true, ticks: { callback: v => brl(v) } } } }
});

const corPag = { aprovado: '#2c8a5e', pendente: '#F0923B', recusado: '#c0504d', estornado: '#999' };
new Chart(document.getElementById('gPagamento'), {
  type: 'bar',
  data: { labels: pags.map(p => nomesPag[p.status] || p.status), datasets: [{ label: 'Pedidos', data: pags.map(p => +p.pedidos), backgroundColor: pags.map(p => corPag[p.status] || '#7B4FBF'), borderRadius: 6 }] },
  options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.parsed.y + ' pedido(s) — ' + brl(pags[c.dataIndex].fat) } } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php endif; ?>
<?php adminFooter(); ?>
