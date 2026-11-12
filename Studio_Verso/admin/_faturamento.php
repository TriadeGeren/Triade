<?php
/**
 * Regras de cálculo do faturamento (compartilhadas pela tela, pelo dashboard e pelas exportações).
 *
 * Faturamento = soma de (quantidade × preço unitário) dos itens dos pedidos.
 * Por padrão só entram pedidos com pagamento APROVADO; dá pra mudar no filtro.
 */

const BASE_FROM = "FROM pedido_itens pi
    JOIN pedidos pd ON pd.id = pi.pedido_id
    JOIN produtos pr ON pr.id = pi.produto_id
    JOIN categorias c ON c.id = pr.categoria_id";

function dataValida(?string $d): ?string {
    if (!$d) return null;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
}

/** Lê e normaliza os filtros vindos da URL (GET). */
function lerFiltros(): array {
    $hoje = new DateTime('today');
    $preset = $_GET['preset'] ?? '';
    $de = dataValida($_GET['de'] ?? null);
    $ate = dataValida($_GET['ate'] ?? null);

    if ($preset !== '' || (!$de && !$ate && !isset($_GET['filtrar']))) {
        $preset = $preset ?: '30d';
        switch ($preset) {
            case '7d':  $de = (clone $hoje)->modify('-6 days')->format('Y-m-d'); $ate = $hoje->format('Y-m-d'); break;
            case '90d': $de = (clone $hoje)->modify('-89 days')->format('Y-m-d'); $ate = $hoje->format('Y-m-d'); break;
            case 'mes': $de = $hoje->format('Y-m-01'); $ate = $hoje->format('Y-m-d'); break;
            case 'mes_passado':
                $de = (clone $hoje)->modify('first day of last month')->format('Y-m-d');
                $ate = (clone $hoje)->modify('last day of last month')->format('Y-m-d');
                break;
            case 'ano': $de = $hoje->format('Y-01-01'); $ate = $hoje->format('Y-m-d'); break;
            case 'tudo': $de = null; $ate = null; break;
            case '30d':
            default:    $preset = '30d'; $de = (clone $hoje)->modify('-29 days')->format('Y-m-d'); $ate = $hoje->format('Y-m-d');
        }
    } else {
        $preset = '';
    }
    if ($de && $ate && $de > $ate) { [$de, $ate] = [$ate, $de]; }

    $pagamento = $_GET['pagamento'] ?? 'aprovado';
    if ($pagamento !== '' && !isset(STATUS_PAGAMENTO[$pagamento])) $pagamento = 'aprovado';
    $status = $_GET['status'] ?? '';
    if ($status !== '' && !isset(STATUS_PEDIDO[$status])) $status = '';

    // Agrupamento: por padrão "dia" (ou "mês" quando o período é longo)
    $agrupar = $_GET['agrupar'] ?? '';
    if (!in_array($agrupar, ['dia', 'mes'], true)) {
        $dias = ($de && $ate) ? (new DateTime($de))->diff(new DateTime($ate))->days : 9999;
        $agrupar = $dias > 92 ? 'mes' : 'dia';
    }

    return [
        'preset' => $preset, 'de' => $de, 'ate' => $ate,
        'pagamento' => $pagamento, 'status' => $status,
        'categoria' => (int)($_GET['categoria'] ?? 0),
        'produto' => (int)($_GET['produto'] ?? 0),
        'agrupar' => $agrupar,
    ];
}

/** Monta o WHERE (com parâmetros preparados) a partir dos filtros. */
function montarWhere(array $f, bool $comPagamento = true, ?array $periodo = null): array {
    $w = []; $tipos = ''; $p = [];
    $de = $periodo['de'] ?? $f['de'];
    $ate = $periodo['ate'] ?? $f['ate'];
    if ($de)  { $w[] = 'pd.criado_em >= ?'; $tipos .= 's'; $p[] = $de . ' 00:00:00'; }
    if ($ate) { $w[] = 'pd.criado_em < DATE_ADD(?, INTERVAL 1 DAY)'; $tipos .= 's'; $p[] = $ate . ' 00:00:00'; }
    if ($comPagamento && $f['pagamento'] !== '') { $w[] = 'pd.status_pagamento = ?'; $tipos .= 's'; $p[] = $f['pagamento']; }
    if ($f['status'] !== '') { $w[] = 'pd.status = ?'; $tipos .= 's'; $p[] = $f['status']; }
    if ($f['categoria'] > 0) { $w[] = 'c.id = ?'; $tipos .= 'i'; $p[] = $f['categoria']; }
    if ($f['produto'] > 0)   { $w[] = 'pr.id = ?'; $tipos .= 'i'; $p[] = $f['produto']; }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $tipos, $p];
}

const EXPR_VALOR = 'pi.quantidade * pi.preco_unitario';

function kpis(array $f, ?array $periodo = null): array {
    [$where, $t, $p] = montarWhere($f, true, $periodo);
    $r = consultar("SELECT COALESCE(SUM(" . EXPR_VALOR . "),0) AS faturamento,
                           COUNT(DISTINCT pd.id) AS pedidos,
                           COALESCE(SUM(pi.quantidade),0) AS itens " . BASE_FROM . " $where", $t, $p)[0];
    $r['faturamento'] = (float)$r['faturamento'];
    $r['pedidos'] = (int)$r['pedidos'];
    $r['itens'] = (int)$r['itens'];
    $r['ticket'] = $r['pedidos'] > 0 ? $r['faturamento'] / $r['pedidos'] : 0.0;
    return $r;
}

/** Período imediatamente anterior, com a mesma duração (para comparar). */
function periodoAnterior(array $f): ?array {
    if (!$f['de'] || !$f['ate']) return null;
    $ini = new DateTime($f['de']); $fim = new DateTime($f['ate']);
    $dias = $ini->diff($fim)->days + 1;
    return [
        'de' => (clone $ini)->modify("-$dias days")->format('Y-m-d'),
        'ate' => (clone $ini)->modify('-1 day')->format('Y-m-d'),
    ];
}

/** Faturamento por período, com os dias/meses vazios preenchidos com zero. */
function serieTemporal(array $f): array {
    [$where, $t, $p] = montarWhere($f);
    $fmt = $f['agrupar'] === 'mes' ? "DATE_FORMAT(pd.criado_em, '%Y-%m')" : 'DATE(pd.criado_em)';
    $linhas = consultar("SELECT $fmt AS periodo, SUM(" . EXPR_VALOR . ") AS fat, COUNT(DISTINCT pd.id) AS pedidos
                         " . BASE_FROM . " $where GROUP BY periodo ORDER BY periodo", $t, $p);
    $mapa = [];
    foreach ($linhas as $l) $mapa[$l['periodo']] = ['fat' => (float)$l['fat'], 'pedidos' => (int)$l['pedidos']];

    $de = $f['de'] ?: ($linhas ? ($f['agrupar'] === 'mes' ? $linhas[0]['periodo'] . '-01' : $linhas[0]['periodo']) : null);
    $ate = $f['ate'] ?: ($linhas ? ($f['agrupar'] === 'mes' ? $linhas[count($linhas)-1]['periodo'] . '-01' : $linhas[count($linhas)-1]['periodo']) : null);
    $saida = [];
    if ($de && $ate) {
        $cur = new DateTime($de); $fim = new DateTime($ate);
        if ($f['agrupar'] === 'mes') { $cur->modify('first day of this month'); $fim->modify('first day of this month'); }
        $guarda = 0;
        while ($cur <= $fim && $guarda++ < 3700) {
            $chave = $f['agrupar'] === 'mes' ? $cur->format('Y-m') : $cur->format('Y-m-d');
            $rot = $f['agrupar'] === 'mes' ? $cur->format('m/Y') : $cur->format('d/m');
            $saida[] = ['periodo' => $chave, 'rotulo' => $rot, 'fat' => $mapa[$chave]['fat'] ?? 0.0, 'pedidos' => $mapa[$chave]['pedidos'] ?? 0];
            $cur->modify($f['agrupar'] === 'mes' ? '+1 month' : '+1 day');
        }
    }
    return $saida;
}

function porCategoria(array $f): array {
    [$where, $t, $p] = montarWhere($f);
    return consultar("SELECT c.nome AS nome, SUM(" . EXPR_VALOR . ") AS fat, SUM(pi.quantidade) AS itens
                      " . BASE_FROM . " $where GROUP BY c.id, c.nome ORDER BY fat DESC", $t, $p);
}

function topProdutos(array $f, int $limite = 10): array {
    [$where, $t, $p] = montarWhere($f);
    return consultar("SELECT pr.nome AS nome, c.nome AS categoria, SUM(pi.quantidade) AS itens, SUM(" . EXPR_VALOR . ") AS fat
                      " . BASE_FROM . " $where GROUP BY pr.id, pr.nome, c.nome ORDER BY fat DESC LIMIT " . (int)$limite, $t, $p);
}

/** Distribuição por status de pagamento (ignora o filtro de pagamento, senão seria sempre 1 barra). */
function porPagamento(array $f): array {
    [$where, $t, $p] = montarWhere($f, false);
    return consultar("SELECT pd.status_pagamento AS status, SUM(" . EXPR_VALOR . ") AS fat, COUNT(DISTINCT pd.id) AS pedidos
                      " . BASE_FROM . " $where GROUP BY pd.status_pagamento ORDER BY fat DESC", $t, $p);
}

/** Linhas detalhadas (1 por item vendido) para exportação. */
function linhasDetalhadas(array $f): array {
    [$where, $t, $p] = montarWhere($f);
    return consultar("SELECT pd.id AS pedido, pd.criado_em, pd.nome_cliente, pd.email_cliente, pd.whatsapp_cliente,
                             pr.nome AS produto, c.nome AS categoria, pi.quantidade, pi.preco_unitario,
                             (" . EXPR_VALOR . ") AS subtotal, pd.status, pd.status_pagamento
                      " . BASE_FROM . " $where ORDER BY pd.criado_em DESC, pd.id DESC, pi.id", $t, $p);
}

/** Descrição humana dos filtros (vai no cabeçalho do arquivo exportado). */
function descricaoFiltros(array $f): string {
    $partes = [];
    $partes[] = 'Período: ' . ($f['de'] ? date('d/m/Y', strtotime($f['de'])) : 'início') . ' a ' . ($f['ate'] ? date('d/m/Y', strtotime($f['ate'])) : 'hoje');
    $partes[] = 'Pagamento: ' . ($f['pagamento'] === '' ? 'todos' : STATUS_PAGAMENTO[$f['pagamento']]);
    if ($f['status'] !== '') $partes[] = 'Status do pedido: ' . STATUS_PEDIDO[$f['status']];
    return implode(' | ', $partes);
}
