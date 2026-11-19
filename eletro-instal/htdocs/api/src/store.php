<?php
/** Loja pública: categorias, marcas, produtos, configurações públicas. */

function prod_img_sql()
{
    return "(SELECT ip.caminho FROM imagens_produtos ip WHERE ip.produto_id = p.id
             ORDER BY ip.principal DESC, ip.ordem, ip.id LIMIT 1)";
}

/** Normaliza tipos numéricos e calcula campos de promoção. */
function shape_produto(array $r)
{
    foreach (['preco', 'preco_promocional', 'preco_atual', 'media_avaliacoes'] as $k) {
        if (isset($r[$k]) && $r[$k] !== null) $r[$k] = (float) $r[$k];
    }
    foreach (['id', 'estoque', 'total_vendido', 'total_avaliacoes', 'destaque'] as $k) {
        if (isset($r[$k]) && $r[$k] !== null) $r[$k] = (int) $r[$k];
    }
    if (isset($r['preco'], $r['preco_atual'])) {
        $r['em_promocao'] = $r['preco_atual'] < $r['preco'];
        $r['desconto_pct'] = $r['em_promocao'] ? (int) round((1 - $r['preco_atual'] / $r['preco']) * 100) : 0;
    }
    unset($r['preco_promocional']);
    return $r;
}

function config_publica()
{
    out(['config' => [
        'loja_nome'           => setting('loja_nome', 'Eletro Instal'),
        'loja_whatsapp'       => preg_replace('/\D/', '', (string) setting('loja_whatsapp', '')),
        'loja_email_contato'  => setting('loja_email_contato', ''),
        'frete_gratis_acima'  => (float) setting('frete_gratis_acima', 0),
        'frete_fixo'          => (float) setting('frete_fixo', 0),
    ]]);
}

function health()
{
    $dbOk = true;
    try { q_val('SELECT 1'); } catch (Throwable $e) { $dbOk = false; }
    out(['status' => $dbOk ? 'ok' : 'erro_banco', 'banco' => $dbOk, 'php' => PHP_VERSION, 'timestamp' => date('c')], $dbOk ? 200 : 503);
}

function categorias_list()
{
    $rows = q_all('SELECT c.id, c.pai_id, c.nome, c.slug, c.descricao, c.icone, c.ordem,
                          (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id AND p.ativo = 1 AND p.excluido_em IS NULL) AS total_produtos
                   FROM categorias c WHERE c.ativo = 1 ORDER BY c.ordem, c.nome');
    $map = [];
    foreach ($rows as $r) { $r['filhos'] = []; $r['total_produtos'] = (int) $r['total_produtos']; $map[$r['id']] = $r; }
    $roots = [];
    foreach (array_keys($map) as $id) {
        $pai = $map[$id]['pai_id'];
        if ($pai && isset($map[$pai])) $map[$pai]['filhos'][] = &$map[$id];
        else $roots[] = &$map[$id];
    }
    out(['categorias' => $roots]);
}

function categoria_get(array $p)
{
    $c = q_one('SELECT id, pai_id, nome, slug, descricao, icone FROM categorias WHERE slug = ? AND ativo = 1', [$p['slug']]);
    if (!$c) fail('Categoria não encontrada.', 404);
    out(['categoria' => $c]);
}

function marcas_list()
{
    $rows = q_all("SELECT DISTINCT marca FROM produtos WHERE ativo = 1 AND excluido_em IS NULL AND marca IS NOT NULL AND marca <> '' ORDER BY marca");
    out(['marcas' => array_column($rows, 'marca')]);
}

function produtos_list()
{
    list($page, $limit, $offset) = page_params(12, 48);
    $where = ['p.ativo = 1', 'p.excluido_em IS NULL'];
    $params = [];

    if ($cat = qs('categoria')) {
        $where[] = '(c.slug = ? OR pai.slug = ?)';
        array_push($params, $cat, $cat);
    }
    if (in_array(qs('destaque'), ['1', 'true'], true)) $where[] = 'p.destaque = 1';
    if ($marca = qs('marca')) { $where[] = 'p.marca = ?'; $params[] = $marca; }
    if ($busca = qs('busca')) {
        $busca = mb_substr(trim((string) $busca), 0, 80);
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $busca) . '%';
        $where[] = '(p.nome LIKE ? OR p.descricao_curta LIKE ? OR p.marca LIKE ? OR p.sku LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $preco = sql_preco('p');
    if (qs('preco_min') !== null) { $where[] = "($preco) >= ?"; $params[] = (float) qs('preco_min'); }
    if (qs('preco_max') !== null) { $where[] = "($preco) <= ?"; $params[] = (float) qs('preco_max'); }
    if (in_array(qs('em_estoque'), ['1', 'true'], true)) $where[] = 'COALESCE(e.quantidade, 0) > 0';

    $orders = [
        'recente'    => 'p.criado_em DESC, p.id DESC',
        'preco_asc'  => "($preco) ASC",
        'preco_desc' => "($preco) DESC",
        'popular'    => 'p.total_vendido DESC, p.id DESC',
        'nome'       => 'p.nome ASC',
    ];
    $orderBy = $orders[qs('order', 'recente')] ?? $orders['recente'];
    $whereSQL = implode(' AND ', $where);
    $from = 'FROM produtos p
             JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN categorias pai ON pai.id = c.pai_id
             LEFT JOIN estoque e ON e.produto_id = p.id';

    $total = (int) q_val("SELECT COUNT(*) $from WHERE $whereSQL", $params);
    $rows = q_all("SELECT p.id, p.sku, p.nome, p.slug, p.descricao_curta, p.marca, p.preco, p.preco_promocional,
                          ($preco) AS preco_atual, p.destaque, p.total_vendido, p.media_avaliacoes, p.total_avaliacoes,
                          c.nome AS categoria_nome, c.slug AS categoria_slug,
                          COALESCE(e.quantidade, 0) AS estoque, " . prod_img_sql() . " AS imagem_principal
                   $from WHERE $whereSQL ORDER BY $orderBy LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);

    out(['produtos' => array_map('shape_produto', $rows), 'paginacao' => paginacao($page, $limit, $total)]);
}

function produtos_destaques()
{
    $preco = sql_preco('p');
    $rows = q_all("SELECT p.id, p.sku, p.nome, p.slug, p.descricao_curta, p.marca, p.preco, p.preco_promocional,
                          ($preco) AS preco_atual, p.destaque,
                          c.nome AS categoria_nome, c.slug AS categoria_slug,
                          COALESCE(e.quantidade, 0) AS estoque, " . prod_img_sql() . " AS imagem_principal
                   FROM produtos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN estoque e ON e.produto_id = p.id
                   WHERE p.ativo = 1 AND p.excluido_em IS NULL AND p.destaque = 1
                   ORDER BY p.total_vendido DESC, p.id DESC LIMIT 8");
    out(['produtos' => array_map('shape_produto', $rows)]);
}

function produto_get(array $p)
{
    $preco = sql_preco('p');
    // Colunas explícitas: NUNCA expor custo / margem no site público.
    $r = q_one("SELECT p.id, p.sku, p.nome, p.slug, p.descricao_curta, p.descricao, p.marca, p.preco, p.preco_promocional,
                       ($preco) AS preco_atual, p.peso_kg, p.destaque, p.total_vendido, p.media_avaliacoes, p.total_avaliacoes,
                       p.meta_titulo, p.meta_descricao, c.nome AS categoria_nome, c.slug AS categoria_slug, c.id AS categoria_id,
                       COALESCE(e.quantidade, 0) AS estoque
                FROM produtos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN estoque e ON e.produto_id = p.id
                WHERE p.slug = ? AND p.ativo = 1 AND p.excluido_em IS NULL", [$p['slug']]);
    if (!$r) fail('Produto não encontrado.', 404);

    $imgs = q_all('SELECT id, caminho, alt, principal, ordem FROM imagens_produtos WHERE produto_id = ? ORDER BY principal DESC, ordem, id', [$r['id']]);
    $rel = q_all("SELECT p.id, p.nome, p.slug, p.marca, p.preco, p.preco_promocional, ($preco) AS preco_atual,
                         COALESCE(e.quantidade, 0) AS estoque, " . prod_img_sql() . " AS imagem_principal
                  FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id
                  WHERE p.categoria_id = ? AND p.id <> ? AND p.ativo = 1 AND p.excluido_em IS NULL
                  ORDER BY p.total_vendido DESC, p.id DESC LIMIT 4", [$r['categoria_id'], $r['id']]);

    $r = shape_produto($r);
    $r['peso_kg'] = $r['peso_kg'] !== null ? (float) $r['peso_kg'] : null;
    $r['imagens'] = $imgs;
    out(['produto' => $r, 'relacionados' => array_map('shape_produto', $rel)]);
}
