<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/personalizacao.php';
garantirTabelasPersonalizacao($conn);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT p.*, c.nome AS cat_nome FROM produtos p JOIN categorias c ON c.id = p.categoria_id WHERE p.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) {
    http_response_code(404);
    $tituloPagina = 'Produto não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container secao"><p>Esse produto não existe ou foi removido.</p><a href="' . BASE_PATH . '/index.php" class="btn btn-fio">Voltar ao catálogo</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Personalização: opções do produto (e da categoria dele). Todo item sob encomenda aceita referências.
$opcoes = opcoesDoProduto($conn, (int)$produto['id'], (int)$produto['categoria_id']);
$mostrarPers = (bool)$opcoes || (bool)$produto['sob_encomenda'];
if (empty($_SESSION['csrf_pub'])) $_SESSION['csrf_pub'] = bin2hex(random_bytes(32));

$tituloPagina = $produto['nome'];
require __DIR__ . '/includes/header.php';
?>

<section class="secao">
  <div class="container produto-grade" style="display:grid;grid-template-columns:1fr 1fr;gap:50px;align-items:start;">
    <div class="thumb" style="border-radius:var(--raio);overflow:hidden;border:2px dashed var(--linha);">
      <img src="<?= BASE_PATH ?>/<?= e($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>" onerror="this.src='<?= BASE_PATH ?>/assets/img/sem-foto.jpg'">
    </div>
    <div>
      <span class="categoria"><?= e($produto['cat_nome']) ?></span>
      <h1 style="margin-top:8px;"><?= e($produto['nome']) ?></h1>
      <p style="color:var(--tinta-suave);"><?= nl2br(e($produto['descricao'])) ?></p>
      <p class="preco" style="font-size:1.5rem;">R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></p>

      <?php if ($produto['sob_encomenda']): ?>
        <p class="sob-encomenda">Este item é feito sob encomenda — o prazo de produção será combinado após o pedido.</p>
      <?php elseif ($produto['estoque'] <= 0): ?>
        <p class="alerta alerta-erro">Sem estoque no momento.</p>
      <?php endif; ?>

      <?php if ($mostrarPers): ?>
      <form id="formPers" class="pers" enctype="multipart/form-data" data-base="<?= (float)$produto['preco'] ?>">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_pub']) ?>">
        <input type="hidden" name="produto_id" value="<?= (int)$produto['id'] ?>">
        <h3>✨ Personalize o seu pedido</h3>

        <?php foreach ($opcoes as $o): ?>
          <fieldset class="pers-grupo">
            <legend><?= e($o['nome']) ?> <small><?= $o['obrigatorio'] ? '(obrigatório)' : '(opcional)' ?><?= $o['tipo'] === 'multipla' ? ' · pode marcar várias' : '' ?></small></legend>
            <?php if ($o['tipo'] === 'texto'): ?>
              <input type="text" name="tx[<?= (int)$o['id'] ?>]" maxlength="200" class="pers-texto" data-acrescimo="<?= (float)$o['acrescimo'] ?>"
                     placeholder="<?= (float)$o['acrescimo'] > 0 ? 'Escreva aqui (+ R$ ' . number_format((float)$o['acrescimo'], 2, ',', '.') . ')' : 'Escreva aqui' ?>" <?= $o['obrigatorio'] ? 'required' : '' ?>>
            <?php else: ?>
              <div class="pers-chips">
              <?php foreach ($o['valores'] as $v): ?>
                <label class="chip">
                  <input type="<?= $o['tipo'] === 'multipla' ? 'checkbox' : 'radio' ?>"
                         name="op[<?= (int)$o['id'] ?>]<?= $o['tipo'] === 'multipla' ? '[]' : '' ?>"
                         value="<?= (int)$v['id'] ?>" data-acrescimo="<?= (float)$v['acrescimo'] ?>"
                         <?= ($o['obrigatorio'] && $o['tipo'] === 'escolha') ? 'required' : '' ?>>
                  <span><?= e($v['rotulo']) ?><?= (float)$v['acrescimo'] > 0 ? ' <em>+ R$ ' . number_format((float)$v['acrescimo'], 2, ',', '.') . '</em>' : '' ?></span>
                </label>
              <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </fieldset>
        <?php endforeach; ?>

        <div class="campo">
          <label>Conte como você imagina <small>(cores, personagem, frase, links de inspiração...)</small></label>
          <textarea name="descricao" rows="4" maxlength="1000" placeholder="Quanto mais detalhes, melhor!"></textarea>
        </div>
        <div class="campo">
          <label>Imagens de referência <small>(até 5 fotos, JPG/PNG/WEBP, 5 MB cada)</small></label>
          <input type="file" name="refs[]" id="persRefs" accept="image/jpeg,image/png,image/webp" multiple>
          <div id="persPrevias" class="pers-previas"></div>
        </div>

        <p class="pers-total">Preço final: <strong id="persTotal">R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></strong>
          <small>(base R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?> + personalização)</small></p>
        <button type="submit" class="btn btn-primario" id="persBtn">Adicionar ao carrinho</button>
        <p id="persMsg" class="pers-msg" role="status"></p>
      </form>
      <?php else: ?>
      <button class="btn btn-primario btn-add-carrinho" style="margin-top:14px;"
          data-id="<?= (int)$produto['id'] ?>"
          data-nome="<?= e($produto['nome']) ?>"
          data-preco="<?= (float)$produto['preco'] ?>"
          data-imagem="<?= BASE_PATH ?>/<?= e($produto['imagem']) ?>"
          <?= (!$produto['sob_encomenda'] && $produto['estoque'] <= 0) ? 'disabled' : '' ?>>
          Adicionar ao carrinho
        </button>
      <?php endif; ?>
      <a href="<?= BASE_PATH ?>/index.php#catalogo" class="btn btn-fio" style="margin-top:14px;">Voltar ao catálogo</a>
    </div>
  </div>
</section>


<?php if ($mostrarPers): ?>
<script>
(function () {
  const form = document.getElementById('formPers');
  const base = parseFloat(form.dataset.base);
  const totalEl = document.getElementById('persTotal');
  const msg = document.getElementById('persMsg');
  const btn = document.getElementById('persBtn');
  const brl = v => 'R$ ' + v.toFixed(2).replace('.', ',');

  // Preço ao vivo (só uma prévia: o valor oficial é calculado pelo servidor)
  function recalcular() {
    let total = base;
    form.querySelectorAll('input[type=radio]:checked, input[type=checkbox]:checked').forEach(i => total += parseFloat(i.dataset.acrescimo || 0));
    form.querySelectorAll('.pers-texto').forEach(i => { if (i.value.trim() !== '') total += parseFloat(i.dataset.acrescimo || 0); });
    totalEl.textContent = brl(total);
  }
  form.addEventListener('input', recalcular);
  form.addEventListener('change', recalcular);

  // Prévia das imagens escolhidas
  document.getElementById('persRefs').addEventListener('change', e => {
    const box = document.getElementById('persPrevias');
    box.innerHTML = '';
    [...e.target.files].slice(0, 5).forEach(f => {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f); img.alt = f.name;
      box.appendChild(img);
    });
  });

  form.addEventListener('submit', async ev => {
    ev.preventDefault();
    msg.textContent = ''; msg.className = 'pers-msg';
    if ((document.getElementById('persRefs').files || []).length > 5) { msg.textContent = 'Envie no máximo 5 imagens.'; msg.classList.add('erro'); return; }
    btn.disabled = true; btn.textContent = 'Enviando...';
    try {
      const resp = await fetch('<?= BASE_PATH ?>/personalizar.php', { method: 'POST', body: new FormData(form) });
      const dados = await resp.json();
      if (!dados.ok) throw new Error(dados.erro || 'Não foi possível salvar a personalização.');
      adicionarAoCarrinho({
        id: <?= (int)$produto['id'] ?>,
        nome: <?= json_encode($produto['nome'] . ' (personalizado)', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        preco: dados.preco,
        imagem: <?= json_encode(BASE_PATH . '/' . $produto['imagem'], JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        pers: dados.token,
        resumo: dados.resumo
      });
      form.reset(); recalcular(); document.getElementById('persPrevias').innerHTML = '';
      msg.textContent = 'Adicionado ao carrinho! Você pode personalizar outra peça se quiser.';
      msg.classList.add('ok');
    } catch (err) {
      msg.textContent = err.message; msg.classList.add('erro');
    } finally {
      btn.disabled = false; btn.textContent = 'Adicionar ao carrinho';
    }
  });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
