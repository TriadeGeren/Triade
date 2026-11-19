<?php
/* Página de produto: o conteúdo é carregado via API, mas título/descrição/Open Graph/JSON-LD
   são gerados aqui no servidor para SEO e para pré-visualização de links (WhatsApp, Google). */
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_GET['slug'] ?? '')));
$title = 'Produto — Eletro Instal';
$desc  = 'Veja detalhes, preço e disponibilidade deste produto na Eletro Instal.';
$extraHead = '';
if ($slug !== '') {
    try {
        require_once __DIR__ . '/api/src/bootstrap.php';
        $p = q_one('SELECT p.nome, p.descricao_curta, p.sku, p.marca, p.meta_titulo, p.meta_descricao,
                           (' . sql_preco('p') . ') AS preco, COALESCE(e.quantidade, 0) AS estoque,
                           (SELECT ip.caminho FROM imagens_produtos ip WHERE ip.produto_id = p.id ORDER BY ip.principal DESC, ip.ordem, ip.id LIMIT 1) AS img
                    FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id
                    WHERE p.slug = ? AND p.ativo = 1 AND p.excluido_em IS NULL', [$slug]);
        if ($p) {
            $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
            $title = ($p['meta_titulo'] ?: $p['nome']) . ' — Eletro Instal';
            $desc  = $p['meta_descricao'] ?: ($p['descricao_curta'] ?: $title);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
            $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $imgUrl = $p['img'] ? $base . '/' . $p['img'] : '';
            $ld = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['nome'], 'sku' => $p['sku'], 'description' => $desc,
                   'offers' => ['@type' => 'Offer', 'priceCurrency' => 'BRL', 'price' => number_format((float) $p['preco'], 2, '.', ''),
                                'availability' => $p['estoque'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']];
            if ($p['marca']) $ld['brand'] = ['@type' => 'Brand', 'name' => $p['marca']];
            if ($imgUrl) $ld['image'] = $imgUrl;
            $extraHead = '  <meta property="og:type" content="product">' . "\n"
                . '  <meta property="og:title" content="' . $h($title) . '">' . "\n"
                . '  <meta property="og:description" content="' . $h($desc) . '">' . "\n"
                . ($imgUrl ? '  <meta property="og:image" content="' . $h($imgUrl) . '">' . "\n" : '')
                . '  <script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>' . "\n";
        } else {
            http_response_code(404);
        }
    } catch (Throwable $e) { /* sem banco: a página ainda funciona via API */ }
}
$active = 'catalogo';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <div class="page-head"><div class="crumbs"><a href="index.php">Início</a> / <a href="catalogo.php">Catálogo</a> <span id="crumbCat"></span></div></div>
  <div id="pd"><div class="pd"><div class="skel" style="aspect-ratio:1/1"></div><div><div class="skel" style="height:40px;margin-bottom:14px"></div><div class="skel" style="height:120px"></div></div></div></div>
  <section class="section" id="relSec" hidden style="padding-top:20px"><div class="sec-head"><h2>Produtos relacionados</h2></div><div class="grid g4" id="rel"></div></section>
</div>
<script src="assets/js/produto.js?v=1" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
