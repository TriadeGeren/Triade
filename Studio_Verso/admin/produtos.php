<?php
require __DIR__ . '/_boot.php';

$categorias = consultar('SELECT * FROM categorias ORDER BY nome');

/** Salva a foto enviada (se houver) e devolve o caminho, ou null. Aceita só imagens de verdade. */
function salvarImagem(): ?string {
    if (empty($_FILES['imagem']['name']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) { flash('erro', 'A foto é grande demais (máximo 5 MB).'); return null; }
    $info = @getimagesize($_FILES['imagem']['tmp_name']);
    $permitidos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($permitidos[$info[2]])) { flash('erro', 'Envie uma foto JPG, PNG ou WEBP.'); return null; }
    $nome = uniqid('prod_') . '.' . $permitidos[$info[2]];
    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], __DIR__ . '/../assets/img/' . $nome)) {
        flash('erro', 'Não consegui salvar a foto (verifique a permissão da pasta assets/img).');
        return null;
    }
    return 'assets/img/' . $nome;
}
function apagarImagemSeUpload(?string $caminho): void {
    if ($caminho && str_starts_with($caminho, 'assets/img/prod_')) {
        @unlink(__DIR__ . '/../' . $caminho);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = lerPreco($_POST['preco'] ?? '0');
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $estoque = max(0, (int)($_POST['estoque'] ?? 0));
        $sob = isset($_POST['sob_encomenda']) ? 1 : 0;

        if ($nome === '' || !$categoriaId || $preco < 0) {
            flash('erro', 'Preencha nome, categoria e um preço válido.');
            redirecionar('/admin/produtos.php' . ($id ? '?editar=' . $id : ''));
        }
        $novaImg = salvarImagem();

        if ($id) {
            $atual = consultar('SELECT imagem FROM produtos WHERE id = ?', 'i', [$id])[0] ?? null;
            if (!$atual) { flash('erro', 'Produto não encontrado.'); redirecionar('/admin/produtos.php'); }
            $imagem = $novaImg ?? $atual['imagem'];
            $stmt = $conn->prepare('UPDATE produtos SET categoria_id=?, nome=?, descricao=?, preco=?, imagem=?, estoque=?, sob_encomenda=? WHERE id=?');
            $stmt->bind_param('issdsiii', $categoriaId, $nome, $descricao, $preco, $imagem, $estoque, $sob, $id);
            $stmt->execute();
            if ($novaImg) apagarImagemSeUpload($atual['imagem']);
            flash('ok', 'Produto atualizado!');
        } else {
            $imagem = $novaImg ?? 'assets/img/sem-foto.jpg';
            $stmt = $conn->prepare('INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, estoque, sob_encomenda) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('issdsii', $categoriaId, $nome, $descricao, $preco, $imagem, $estoque, $sob);
            $stmt->execute();
            flash('ok', 'Produto cadastrado!');
        }
        redirecionar('/admin/produtos.php');
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        $usos = (int)consultar('SELECT COUNT(*) AS n FROM pedido_itens WHERE produto_id = ?', 'i', [$id])[0]['n'];
        if ($usos > 0) {
            flash('erro', "Esse produto já aparece em $usos item(ns) de pedidos e não pode ser excluído (isso apagaria o histórico de vendas). Zere o estoque ou marque como sem estoque.");
        } else {
            $img = consultar('SELECT imagem FROM produtos WHERE id = ?', 'i', [$id])[0]['imagem'] ?? null;
            $stmt = $conn->prepare('DELETE FROM produtos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            apagarImagemSeUpload($img);
            flash('ok', 'Produto excluído.');
        }
        redirecionar('/admin/produtos.php');
    }
}

/* ---------- Tela ---------- */
$editando = null;
if (isset($_GET['editar'])) {
    $editando = consultar('SELECT * FROM produtos WHERE id = ?', 'i', [(int)$_GET['editar']])[0] ?? null;
}
$busca = trim($_GET['q'] ?? '');
$catF = (int)($_GET['cat'] ?? 0);
$w = []; $t = ''; $p = [];
if ($busca !== '') { $w[] = 'p.nome LIKE ?'; $t .= 's'; $p[] = '%' . $busca . '%'; }
if ($catF) { $w[] = 'p.categoria_id = ?'; $t .= 'i'; $p[] = $catF; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$produtos = consultar("SELECT p.*, c.nome AS cat_nome FROM produtos p JOIN categorias c ON c.id = p.categoria_id $where ORDER BY p.criado_em DESC, p.id DESC", $t, $p);

adminHeader($editando ? 'Editar produto' : 'Produtos', 'produtos');
?>
<div class="cartao">
  <h2><?= $editando ? 'Editando: ' . e($editando['nome']) : 'Cadastrar novo produto' ?></h2>
  <?php if (!$categorias): ?>
    <div class="alerta alerta-erro">Crie pelo menos uma <a href="<?= BASE_PATH ?>/admin/categorias.php"><u>categoria</u></a> antes de cadastrar produtos.</div>
  <?php else: ?>
  <form method="POST" enctype="multipart/form-data" class="form-coluna">
    <?= csrfField() ?>
    <input type="hidden" name="acao" value="salvar">
    <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
    <div class="campo"><label>Nome</label><input type="text" name="nome" required maxlength="120" value="<?= e($editando['nome'] ?? '') ?>"></div>
    <div class="campo"><label>Descrição</label><textarea name="descricao" rows="3"><?= e($editando['descricao'] ?? '') ?></textarea></div>
    <div class="campo"><label>Categoria</label>
      <select name="categoria_id" required>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($editando['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo"><label>Preço (R$)</label><input type="text" name="preco" placeholder="99,90" required inputmode="decimal" value="<?= $editando ? e(number_format((float)$editando['preco'], 2, ',', '')) : '' ?>"></div>
    <div class="campo"><label>Estoque</label><input type="number" min="0" name="estoque" value="<?= (int)($editando['estoque'] ?? 0) ?>"></div>
    <div class="campo campo-check"><label><input type="checkbox" name="sob_encomenda" <?= !empty($editando['sob_encomenda']) ? 'checked' : '' ?>> Feito sob encomenda</label></div>
    <div class="campo"><label>Foto <small>(JPG, PNG ou WEBP, até 5 MB<?= $editando ? ' — deixe vazio para manter a atual' : '' ?>)</small></label>
      <?php if ($editando): ?><img src="<?= BASE_PATH ?>/<?= e($editando['imagem']) ?>" class="mini" style="width:80px;height:80px;object-fit:cover;border-radius:12px;margin-bottom:8px" onerror="this.style.display='none'"><?php endif; ?>
      <input type="file" name="imagem" accept="image/*">
    </div>
    <div class="acoes-linha">
      <button type="submit" class="btn btn-primario">Salvar produto</button>
      <?php if ($editando): ?><a href="<?= BASE_PATH ?>/admin/produtos.php" class="btn btn-claro">Cancelar</a><?php endif; ?>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="cartao">
  <div class="barra">
    <h2 style="margin:0">Produtos cadastrados (<?= count($produtos) ?>)</h2>
    <form method="GET" class="acoes-linha">
      <input type="text" name="q" placeholder="Buscar por nome" value="<?= e($busca) ?>" style="padding:8px 12px;border:2px solid var(--linha);border-radius:12px;">
      <select name="cat" style="padding:8px 12px;border:2px solid var(--linha);border-radius:12px;">
        <option value="0">Todas as categorias</option>
        <?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $catF === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-claro btn-peq" type="submit">Filtrar</button>
    </form>
  </div>
  <?php if (!$produtos): ?><p class="vazio">Nenhum produto encontrado.</p><?php else: ?>
  <div class="tabela-wrap"><table class="tabela">
    <tr><th></th><th>Nome</th><th>Categoria</th><th class="num">Preço</th><th>Estoque</th><th>Criado em</th><th></th></tr>
    <?php foreach ($produtos as $p): ?>
      <tr>
        <td><img class="mini" src="<?= BASE_PATH ?>/<?= e($p['imagem']) ?>" alt="" onerror="this.src='<?= BASE_PATH ?>/assets/img/sem-foto.jpg'"></td>
        <td><?= e($p['nome']) ?></td>
        <td><?= e($p['cat_nome']) ?></td>
        <td class="num"><?= brl($p['preco']) ?></td>
        <td><?= $p['sob_encomenda'] ? badge('sob encomenda', 'st-enviado') : badge((string)(int)$p['estoque'], (int)$p['estoque'] === 0 ? 'ruim' : ((int)$p['estoque'] <= 2 ? 'aviso' : 'ok')) ?></td>
        <td><?= dataBr($p['criado_em'], false) ?></td>
        <td><div class="acoes-linha">
          <a class="btn btn-claro btn-peq" href="<?= BASE_PATH ?>/admin/produtos.php?editar=<?= (int)$p['id'] ?>">Editar</a>
          <form method="POST" onsubmit="return confirm('Excluir o produto &quot;<?= e(addslashes($p['nome'])) ?>&quot;?')">
            <?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-perigo btn-peq" type="submit">Excluir</button>
          </form>
        </div></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php adminFooter(); ?>
