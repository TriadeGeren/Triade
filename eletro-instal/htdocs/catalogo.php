<?php
$title = 'Catálogo de produtos — Eletro Instal';
$desc  = 'Cabos, disjuntores, quadros de distribuição, iluminação LED, tomadas, ferramentas e energia solar. Filtre por categoria, marca e preço.';
$active = 'catalogo';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <div class="page-head">
    <div class="crumbs"><a href="index.php">Início</a> / Catálogo <span id="crumbCat"></span></div>
    <h1 id="pageTitle">Catálogo</h1>
  </div>
  <div class="cat-layout">
    <aside class="filters" id="filters" aria-label="Filtros">
      <div class="fgroup"><h3>Categorias</h3><div class="flist" id="fCats"></div></div>
      <div class="fgroup"><h3>Faixa de preço</h3>
        <div class="row2"><input class="input" id="pmin" type="number" min="0" step="1" placeholder="Mín. R$" aria-label="Preço mínimo"><input class="input" id="pmax" type="number" min="0" step="1" placeholder="Máx. R$" aria-label="Preço máximo"></div>
      </div>
      <div class="fgroup"><h3>Marca</h3><select class="input" id="fMarca"><option value="">Todas as marcas</option></select></div>
      <div class="fgroup"><label class="check"><input type="checkbox" id="fEstoque"> Somente em estoque</label></div>
      <div class="fgroup"><button class="btn btn-ghost btn-sm btn-block" id="fClear" type="button">Limpar filtros</button></div>
    </aside>
    <section>
      <div class="toolbar">
        <div style="display:flex;gap:12px;align-items:center">
          <button class="btn btn-dark btn-sm filter-toggle" id="fToggle" type="button"><svg class="ic"><use href="assets/img/icons.svg#i-filter"/></svg> Filtros</button>
          <span class="count" id="count" aria-live="polite">Carregando…</span>
        </div>
        <select class="input" id="fOrder" aria-label="Ordenar por">
          <option value="recente">Mais recentes</option><option value="popular">Mais vendidos</option>
          <option value="preco_asc">Menor preço</option><option value="preco_desc">Maior preço</option><option value="nome">Nome (A–Z)</option>
        </select>
      </div>
      <div class="grid" id="grid"></div>
      <div class="pager" id="pager"></div>
    </section>
  </div>
</div>
<script src="assets/js/catalogo.js?v=1" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
