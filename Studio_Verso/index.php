<?php
require_once __DIR__ . '/config.php';

$categorias = $conn->query("SELECT * FROM categorias ORDER BY id");
$catFiltro = $_GET['cat'] ?? 'todos';

$sql = "SELECT p.*, c.slug AS cat_slug, c.nome AS cat_nome
        FROM produtos p JOIN categorias c ON c.id = p.categoria_id";
if ($catFiltro !== 'todos') {
    $stmt = $conn->prepare($sql . " WHERE c.slug = ? ORDER BY p.criado_em DESC");
    $stmt->bind_param('s', $catFiltro);
    $stmt->execute();
    $produtos = $stmt->get_result();
} else {
    $produtos = $conn->query($sql . " ORDER BY p.criado_em DESC");
}

$tituloPagina = 'Início';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <span class="tag-script">feito à mão, fio por fio, traço por traço</span>
    <h1>Cada peça carrega <em>o verso</em> de uma história.</h1>
    <p>Sketchbooks encadernados à mão, amigurumis de fio a fio e ilustrações feitas sob encomenda — para quem gosta de guardar histórias em forma de objeto.</p>
    <div class="btns">
      <a href="#catalogo" class="btn btn-primario">Ver catálogo</a>
      <a href="#encomenda" class="btn btn-fio">Encomendar um desenho</a>
    </div>
  </div>
</section>

<hr class="ponto">

<section class="secao">
  <div class="container">
    <div class="secao-titulo">
      <h2>O que fazemos</h2>
      <p>Três formas de dar vida ao que você imagina.</p>
    </div>
    <div class="grade-servicos">
      <div class="card-servico">
        <span class="icone">📓</span>
        <h3>Sketchbooks</h3>
        <p>Cadernos encadernados à mão, capa personalizada com nome, tema ou ilustração exclusiva.</p>
      </div>
      <div class="card-servico">
        <span class="icone">🧶</span>
        <h3>Amigurumis</h3>
        <p>Bonecos de crochê feitos fio a fio, do seu personagem favorito ou de um pet de estimação.</p>
      </div>
      <div class="card-servico">
        <span class="icone">🖊️</span>
        <h3>Desenhos por encomenda</h3>
        <p>Retratos e ilustrações autorais, digitais ou tradicionais, sob medida para o seu pedido.</p>
      </div>
    </div>
  </div>
</section>

<hr class="ponto">

<section class="secao" id="catalogo">
  <div class="container">
    <div class="secao-titulo">
      <h2>Catálogo</h2>
    </div>

    <div class="filtros">
      <a href="<?= BASE_PATH ?>/index.php#catalogo" class="filtro-btn <?= $catFiltro==='todos'?'ativo':'' ?>">Todos</a>
      <?php $categorias->data_seek(0); while ($c = $categorias->fetch_assoc()): ?>
        <a href="<?= BASE_PATH ?>/index.php?cat=<?= e($c['slug']) ?>#catalogo" class="filtro-btn <?= $catFiltro===$c['slug']?'ativo':'' ?>"><?= e($c['nome']) ?></a>
      <?php endwhile; ?>
    </div>

    <div class="grade-produtos">
      <?php if ($produtos->num_rows === 0): ?>
        <p style="grid-column:1/-1;text-align:center;color:var(--tinta-suave)">Nenhum produto nessa categoria ainda.</p>
      <?php endif; ?>
      <?php while ($p = $produtos->fetch_assoc()): ?>
        <div class="card-produto">
          <a href="<?= BASE_PATH ?>/produto.php?id=<?= (int)$p['id'] ?>" class="thumb">
            <img src="<?= BASE_PATH ?>/<?= e($p['imagem']) ?>" alt="<?= e($p['nome']) ?>" onerror="this.src='<?= BASE_PATH ?>/assets/img/sem-foto.jpg'">
          </a>
          <div class="info">
            <span class="categoria"><?= e($p['cat_nome']) ?></span>
            <h3><a href="<?= BASE_PATH ?>/produto.php?id=<?= (int)$p['id'] ?>"><?= e($p['nome']) ?></a></h3>
            <span class="preco">R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></span>
            <?php if ($p['sob_encomenda']): ?>
              <div class="sob-encomenda">feito sob encomenda</div>
            <?php endif; ?>
            <?php if ($p['sob_encomenda']): ?>
              <a class="btn btn-primario" href="<?= BASE_PATH ?>/produto.php?id=<?= (int)$p['id'] ?>">✨ Personalizar e pedir</a>
            <?php else: ?>
            <button class="btn btn-primario btn-add-carrinho"
              data-id="<?= (int)$p['id'] ?>"
              data-nome="<?= e($p['nome']) ?>"
              data-preco="<?= (float)$p['preco'] ?>"
              data-imagem="<?= BASE_PATH ?>/<?= e($p['imagem']) ?>">
              Adicionar ao carrinho
            </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<section class="faixa-encomenda" id="encomenda">
  <div class="container">
    <div>
      <h2>Quer algo só seu?</h2>
      <p>Contamos a sua história em forma de ilustração ou boneco. Envie referências e a gente cuida do resto — do rascunho ao acabamento final.</p>
      <a href="<?= BASE_PATH ?>/contato.php" class="btn btn-primario">Solicitar orçamento</a>
    </div>
    <ol class="passos">
      <li><span class="num">01</span><span>Você envia a ideia e referências pelo formulário ou WhatsApp</span></li>
      <li><span class="num">02</span><span>Enviamos um rascunho e ajustamos junto com você</span></li>
      <li><span class="num">03</span><span>Produção artesanal e envio com acompanhamento</span></li>
    </ol>
  </div>
</section>

<section class="secao sobre" id="sobre">
  <div class="container">
    <h2>Sobre o Studio Verso</h2>
    <p>Somos um ateliê artesanal dedicado a transformar histórias, memórias e referências em objetos únicos — sketchbooks encadernados à mão, amigurumis de crochê e ilustrações autorais. Cada encomenda é feita uma de cada vez, com atenção aos detalhes que fazem um item deixar de ser "produto" e virar afeto.</p>
    <div class="redes">
      <a href="#">Instagram</a>
      <a href="#">Pinterest</a>
      <a href="#">WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
