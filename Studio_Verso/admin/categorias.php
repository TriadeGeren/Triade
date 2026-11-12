<?php
require __DIR__ . '/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'salvar') {
        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') { flash('erro', 'Informe o nome da categoria.'); redirecionar('/admin/categorias.php'); }
        $slugDigitado = trim($_POST['slug'] ?? '');
        $slug = slugify($slugDigitado !== '' ? $slugDigitado : $nome);

        $dup = consultar('SELECT id FROM categorias WHERE slug = ? AND id <> ?', 'si', [$slug, $id]);
        if ($dup) { flash('erro', 'Já existe uma categoria com esse endereço (slug). Mude o nome ou o slug.'); redirecionar('/admin/categorias.php' . ($id ? '?editar=' . $id : '')); }

        if ($id) {
            $stmt = $conn->prepare('UPDATE categorias SET nome = ?, slug = ? WHERE id = ?');
            $stmt->bind_param('ssi', $nome, $slug, $id);
            flash('ok', 'Categoria atualizada!');
        } else {
            $stmt = $conn->prepare('INSERT INTO categorias (nome, slug) VALUES (?, ?)');
            $stmt->bind_param('ss', $nome, $slug);
            flash('ok', 'Categoria criada!');
        }
        $stmt->execute();
    }

    if ($acao === 'excluir') {
        $n = (int)consultar('SELECT COUNT(*) AS n FROM produtos WHERE categoria_id = ?', 'i', [$id])[0]['n'];
        if ($n > 0) {
            flash('erro', "Essa categoria tem $n produto(s). Mova ou exclua os produtos antes.");
        } else {
            $stmt = $conn->prepare('DELETE FROM categorias WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash('ok', 'Categoria excluída.');
        }
    }
    redirecionar('/admin/categorias.php');
}

$editando = isset($_GET['editar']) ? (consultar('SELECT * FROM categorias WHERE id = ?', 'i', [(int)$_GET['editar']])[0] ?? null) : null;
$cats = consultar('SELECT c.*, (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id) AS qtd FROM categorias c ORDER BY c.nome');

adminHeader('Categorias', 'categorias');
?>
<div class="grade-2">
  <div class="cartao">
    <h2><?= $editando ? 'Editar categoria' : 'Nova categoria' ?></h2>
    <form method="POST" class="form-coluna">
      <?= csrfField() ?>
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
      <div class="campo"><label>Nome</label><input type="text" name="nome" required maxlength="60" value="<?= e($editando['nome'] ?? '') ?>"></div>
      <div class="campo"><label>Slug <small>(endereço usado no filtro do site, ex.: <code>sketchbooks</code>. Deixe vazio para gerar sozinho)</small></label>
        <input type="text" name="slug" maxlength="40" value="<?= e($editando['slug'] ?? '') ?>"></div>
      <?php if ($editando): ?><p class="alerta alerta-erro" style="font-size:.8rem">Atenção: mudar o slug muda o link do filtro da loja (<code>?cat=...</code>).</p><?php endif; ?>
      <div class="acoes-linha">
        <button class="btn btn-primario" type="submit">Salvar</button>
        <?php if ($editando): ?><a class="btn btn-claro" href="<?= BASE_PATH ?>/admin/categorias.php">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div>
  <div class="cartao">
    <h2>Categorias (<?= count($cats) ?>)</h2>
    <div class="tabela-wrap"><table class="tabela">
      <tr><th>Nome</th><th>Slug</th><th class="num">Produtos</th><th></th></tr>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td><?= e($c['nome']) ?></td><td><code><?= e($c['slug']) ?></code></td><td class="num"><?= (int)$c['qtd'] ?></td>
          <td><div class="acoes-linha">
            <a class="btn btn-claro btn-peq" href="?editar=<?= (int)$c['id'] ?>">Editar</a>
            <form method="POST" onsubmit="return confirm('Excluir esta categoria?')">
              <?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-perigo btn-peq" type="submit">Excluir</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php adminFooter(); ?>
