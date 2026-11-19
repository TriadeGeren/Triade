<?php
/** Admin: produtos, imagens, estoque, categorias, cupons e catálogo de serviços. */

function num_in($key, $nullable = true)
{
    $v = in($key);
    if ($v === null || $v === '') return $nullable ? null : 0.0;
    $f = (float) str_replace(',', '.', (string) $v);
    return $f;
}

/* ----------------------------------------------------------------- produtos */

function admin_produtos()
{
    admin_user('produtos.ver');
    list($page, $limit, $offset) = page_params(20, 100);
    $where = ['p.excluido_em IS NULL']; $params = [];
    if ($b = qs('busca')) {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], mb_substr(trim((string) $b), 0, 60)) . '%';
        $where[] = '(p.nome LIKE ? OR p.sku LIKE ? OR p.marca LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if (qs('categoria_id')) { $where[] = 'p.categoria_id = ?'; $params[] = (int) qs('categoria_id'); }
    if (qs('ativo') !== null) { $where[] = 'p.ativo = ?'; $params[] = in_array(qs('ativo'), ['1', 'true'], true) ? 1 : 0; }
    $w = implode(' AND ', $where);
    $total = (int) q_val("SELECT COUNT(*) FROM produtos p WHERE $w", $params);
    $rows = q_all("SELECT p.id, p.sku, p.nome, p.slug, p.marca, p.preco, p.preco_promocional, p.ativo, p.destaque, p.total_vendido, p.criado_em,
                          c.nome AS categoria_nome, COALESCE(e.quantidade, 0) AS estoque, COALESCE(e.estoque_minimo, 0) AS estoque_minimo,
                          (SELECT ip.caminho FROM imagens_produtos ip WHERE ip.produto_id = p.id ORDER BY ip.principal DESC, ip.ordem, ip.id LIMIT 1) AS imagem
                   FROM produtos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN estoque e ON e.produto_id = p.id
                   WHERE $w ORDER BY p.criado_em DESC, p.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);
    foreach ($rows as &$r) {
        $r['preco'] = (float) $r['preco'];
        $r['preco_promocional'] = $r['preco_promocional'] !== null ? (float) $r['preco_promocional'] : null;
    }
    out(['produtos' => $rows, 'paginacao' => paginacao($page, $limit, $total)]);
}

function admin_produto_get(array $p)
{
    admin_user('produtos.ver');
    $r = q_one('SELECT p.*, c.nome AS categoria_nome, COALESCE(e.quantidade, 0) AS estoque, COALESCE(e.estoque_minimo, 5) AS estoque_minimo
                FROM produtos p JOIN categorias c ON c.id = p.categoria_id LEFT JOIN estoque e ON e.produto_id = p.id
                WHERE p.id = ? AND p.excluido_em IS NULL', [(int) $p['id']]);
    if (!$r) fail('Produto não encontrado.', 404);
    $r['imagens'] = q_all('SELECT id, caminho, alt, principal, ordem FROM imagens_produtos WHERE produto_id = ? ORDER BY principal DESC, ordem, id', [$r['id']]);
    out(['produto' => $r]);
}

function produto_fields_validate($creating, $existing = null)
{
    $f = [];
    $f['categoria_id'] = (int) in('categoria_id', $existing['categoria_id'] ?? 0);
    $f['sku']  = strtoupper((string) str_in('sku', 40) ?: ($existing['sku'] ?? ''));
    $f['nome'] = (string) (str_in('nome', 160) ?: ($existing['nome'] ?? ''));
    $f['preco'] = in('preco') !== null ? num_in('preco', false) : (float) ($existing['preco'] ?? 0);

    if (!$f['categoria_id'] || !q_val('SELECT 1 FROM categorias WHERE id = ?', [$f['categoria_id']])) fail('Selecione uma categoria válida.');
    if ($f['sku'] === '' || !preg_match('/^[A-Z0-9._-]{2,40}$/', $f['sku'])) fail('SKU inválido (use letras, números, ponto, hífen ou sublinhado).');
    if (mb_strlen($f['nome']) < 3) fail('Informe o nome do produto.');
    if ($f['preco'] <= 0) fail('Informe um preço maior que zero.');

    $f['preco_promocional'] = in('preco_promocional') !== null ? num_in('preco_promocional') : ($existing['preco_promocional'] ?? null);
    if ($f['preco_promocional'] !== null && $f['preco_promocional'] !== '') {
        $f['preco_promocional'] = (float) $f['preco_promocional'];
        if ($f['preco_promocional'] <= 0) $f['preco_promocional'] = null;
        elseif ($f['preco_promocional'] >= $f['preco']) fail('O preço promocional deve ser menor que o preço normal.');
    } else $f['preco_promocional'] = null;

    $f['promocao_inicio'] = in('promocao_inicio') !== null ? dt_in(in('promocao_inicio')) : ($existing['promocao_inicio'] ?? null);
    $f['promocao_fim']    = in('promocao_fim') !== null ? dt_in(in('promocao_fim')) : ($existing['promocao_fim'] ?? null);
    if ($f['promocao_inicio'] && $f['promocao_fim'] && $f['promocao_fim'] < $f['promocao_inicio']) fail('O fim da promoção não pode ser antes do início.');

    foreach (['descricao_curta' => 255, 'marca' => 80, 'meta_titulo' => 70, 'meta_descricao' => 160] as $k => $max) {
        $f[$k] = in($k) !== null ? str_in($k, $max) : ($existing[$k] ?? null);
    }
    $f['descricao'] = in('descricao') !== null ? str_in('descricao', 20000) : ($existing['descricao'] ?? null);
    foreach (['custo', 'peso_kg', 'altura_cm', 'largura_cm', 'comprimento_cm'] as $k) {
        $f[$k] = in($k) !== null ? num_in($k) : ($existing[$k] ?? null);
        if ($f[$k] !== null && $f[$k] < 0) fail("Valor inválido em $k.");
    }
    $f['destaque'] = in('destaque') !== null ? (in('destaque') ? 1 : 0) : (int) ($existing['destaque'] ?? 0);
    $f['ativo']    = in('ativo') !== null ? (in('ativo') ? 1 : 0) : (int) ($existing['ativo'] ?? 1);
    return $f;
}

function admin_produto_create()
{
    $u = admin_user('produtos.criar');
    $f = produto_fields_validate(true);
    if (q_val('SELECT 1 FROM produtos WHERE sku = ?', [$f['sku']])) fail('Já existe um produto com este SKU.', 409);

    $slug = slugify($f['nome']) . '-' . strtolower(preg_replace('/[^A-Za-z0-9]/', '', $f['sku']));
    $base = $slug; $n = 2;
    while (q_val('SELECT 1 FROM produtos WHERE slug = ?', [$slug])) $slug = $base . '-' . $n++;

    $pdo = db();
    $pdo->beginTransaction();
    try {
        q('INSERT INTO produtos (categoria_id, sku, nome, slug, descricao_curta, descricao, marca, preco, preco_promocional, promocao_inicio, promocao_fim,
                                 custo, peso_kg, altura_cm, largura_cm, comprimento_cm, destaque, ativo, meta_titulo, meta_descricao)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $f['categoria_id'], $f['sku'], $f['nome'], $slug, $f['descricao_curta'], $f['descricao'], $f['marca'], $f['preco'],
            $f['preco_promocional'], $f['promocao_inicio'], $f['promocao_fim'], $f['custo'], $f['peso_kg'], $f['altura_cm'],
            $f['largura_cm'], $f['comprimento_cm'], $f['destaque'], $f['ativo'], $f['meta_titulo'], $f['meta_descricao']]);
        $id = (int) $pdo->lastInsertId();
        $qtd = max(0, (int) in('estoque_inicial', 0));
        q('INSERT INTO estoque (produto_id, quantidade, estoque_minimo) VALUES (?,?,?)', [$id, $qtd, max(0, (int) in('estoque_minimo', 5))]);
        if ($qtd > 0) {
            q("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, usuario_id)
               VALUES (?, 'entrada', ?, 0, ?, 'Estoque inicial', ?)", [$id, $qtd, $qtd, $u['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    audit($u['id'], 'produto.criado', 'produtos', $id, null, ['sku' => $f['sku'], 'nome' => $f['nome'], 'preco' => $f['preco']]);
    out(['message' => 'Produto criado.', 'produto' => ['id' => $id, 'slug' => $slug]], 201);
}

function admin_produto_update(array $p)
{
    $u = admin_user('produtos.editar');
    $id = (int) $p['id'];
    $cur = q_one('SELECT * FROM produtos WHERE id = ? AND excluido_em IS NULL', [$id]);
    if (!$cur) fail('Produto não encontrado.', 404);
    $f = produto_fields_validate(false, $cur);
    if (q_val('SELECT 1 FROM produtos WHERE sku = ? AND id <> ?', [$f['sku'], $id])) fail('Já existe outro produto com este SKU.', 409);

    q('UPDATE produtos SET categoria_id=?, sku=?, nome=?, descricao_curta=?, descricao=?, marca=?, preco=?, preco_promocional=?, promocao_inicio=?, promocao_fim=?,
              custo=?, peso_kg=?, altura_cm=?, largura_cm=?, comprimento_cm=?, destaque=?, ativo=?, meta_titulo=?, meta_descricao=? WHERE id = ?', [
        $f['categoria_id'], $f['sku'], $f['nome'], $f['descricao_curta'], $f['descricao'], $f['marca'], $f['preco'], $f['preco_promocional'],
        $f['promocao_inicio'], $f['promocao_fim'], $f['custo'], $f['peso_kg'], $f['altura_cm'], $f['largura_cm'], $f['comprimento_cm'],
        $f['destaque'], $f['ativo'], $f['meta_titulo'], $f['meta_descricao'], $id]);

    if (in('estoque_minimo') !== null && in('estoque_minimo') !== '') {
        q('INSERT INTO estoque (produto_id, quantidade, estoque_minimo) VALUES (?, 0, ?) ON DUPLICATE KEY UPDATE estoque_minimo = VALUES(estoque_minimo)', [$id, max(0, (int) in('estoque_minimo'))]);
    }
    audit($u['id'], 'produto.atualizado', 'produtos', $id, ['preco' => $cur['preco'], 'nome' => $cur['nome'], 'ativo' => $cur['ativo']], ['preco' => $f['preco'], 'nome' => $f['nome'], 'ativo' => $f['ativo']]);
    out(['message' => 'Produto atualizado.']);
}

function admin_produto_delete(array $p)
{
    $u = admin_user('produtos.excluir');
    $st = q('UPDATE produtos SET excluido_em = NOW(), ativo = 0 WHERE id = ? AND excluido_em IS NULL', [(int) $p['id']]);
    if (!$st->rowCount()) fail('Produto não encontrado.', 404);
    q('DELETE ic FROM itens_carrinho ic WHERE ic.produto_id = ?', [(int) $p['id']]);
    audit($u['id'], 'produto.excluido', 'produtos', (int) $p['id']);
    out(['message' => 'Produto excluído.']);
}

/* ------------------------------------------------------------------- imagens */

function upload_dir()
{
    $d = EI_ROOT . '/uploads/produtos';
    if (!is_dir($d)) @mkdir($d, 0755, true);
    return $d;
}

function admin_produto_imagem_upload(array $p)
{
    $u = admin_user('produtos.editar');
    $id = (int) $p['id'];
    if (!q_val('SELECT 1 FROM produtos WHERE id = ? AND excluido_em IS NULL', [$id])) fail('Produto não encontrado.', 404);

    $files = [];
    foreach ($_FILES as $f) {
        if (is_array($f['name'])) { foreach ($f['name'] as $i => $n) $files[] = ['tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]]; }
        else $files[] = $f;
    }
    if (!$files) fail('Nenhuma imagem enviada (ou o arquivo excede o limite do servidor).');
    if (count($files) > 8) fail('Envie no máximo 8 imagens por vez.');

    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $fi = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
    $saved = [];
    foreach ($files as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) fail($f['error'] === UPLOAD_ERR_INI_SIZE ? 'Imagem maior que o limite do servidor.' : 'Falha no upload da imagem.');
        if ($f['size'] > 3 * 1024 * 1024) fail('Cada imagem pode ter no máximo 3 MB.');
        $gi = @getimagesize($f['tmp_name']);
        $mime = $fi ? $fi->file($f['tmp_name']) : ($gi['mime'] ?? '');
        if (!isset($ext[$mime]) || !@getimagesize($f['tmp_name'])) fail('Formato inválido. Envie JPG, PNG, WEBP ou GIF.');
        $name = "p$id-" . bin2hex(random_bytes(6)) . '.' . $ext[$mime];
        if (!move_uploaded_file($f['tmp_name'], upload_dir() . '/' . $name)) fail('Não foi possível salvar a imagem no servidor.', 500);

        $principal = q_val('SELECT 1 FROM imagens_produtos WHERE produto_id = ? LIMIT 1', [$id]) ? 0 : 1;
        $ordem = (int) q_val('SELECT COALESCE(MAX(ordem), 0) + 1 FROM imagens_produtos WHERE produto_id = ?', [$id]);
        q('INSERT INTO imagens_produtos (produto_id, caminho, alt, principal, ordem) VALUES (?,?,?,?,?)',
          [$id, 'uploads/produtos/' . $name, q_val('SELECT nome FROM produtos WHERE id = ?', [$id]), $principal, $ordem]);
        $saved[] = 'uploads/produtos/' . $name;
    }
    audit($u['id'], 'produto.imagem_enviada', 'produtos', $id, null, ['arquivos' => $saved]);
    out(['message' => 'Imagem(ns) enviada(s).', 'imagens' => q_all('SELECT id, caminho, alt, principal, ordem FROM imagens_produtos WHERE produto_id = ? ORDER BY principal DESC, ordem, id', [$id])], 201);
}

function admin_imagem_delete(array $p)
{
    $u = admin_user('produtos.editar');
    $img = q_one('SELECT id, produto_id, caminho, principal FROM imagens_produtos WHERE id = ?', [(int) $p['id']]);
    if (!$img) fail('Imagem não encontrada.', 404);
    q('DELETE FROM imagens_produtos WHERE id = ?', [$img['id']]);
    if ($img['principal']) {
        q('UPDATE imagens_produtos SET principal = 1 WHERE produto_id = ? ORDER BY ordem, id LIMIT 1', [$img['produto_id']]);
    }
    if (strpos($img['caminho'], 'uploads/produtos/') === 0 && basename($img['caminho']) === substr($img['caminho'], strlen('uploads/produtos/'))) {
        @unlink(EI_ROOT . '/' . $img['caminho']);
    }
    audit($u['id'], 'produto.imagem_removida', 'produtos', (int) $img['produto_id']);
    out(['message' => 'Imagem removida.']);
}

function admin_imagem_principal(array $p)
{
    admin_user('produtos.editar');
    $img = q_one('SELECT id, produto_id FROM imagens_produtos WHERE id = ?', [(int) $p['id']]);
    if (!$img) fail('Imagem não encontrada.', 404);
    q('UPDATE imagens_produtos SET principal = (id = ?) WHERE produto_id = ?', [$img['id'], $img['produto_id']]);
    out(['message' => 'Imagem principal definida.']);
}

/* ------------------------------------------------------------------- estoque */

function admin_estoque_baixo()
{
    admin_user('estoque.ver');
    out(['produtos' => q_all('SELECT p.id, p.sku, p.nome, e.quantidade, e.estoque_minimo FROM produtos p JOIN estoque e ON e.produto_id = p.id
                              WHERE p.excluido_em IS NULL AND p.ativo = 1 AND e.quantidade <= e.estoque_minimo ORDER BY e.quantidade, p.nome')]);
}

function admin_estoque_movs(array $p)
{
    admin_user('estoque.ver');
    out(['movimentacoes' => q_all('SELECT m.tipo, m.quantidade, m.saldo_anterior, m.saldo_posterior, m.motivo, m.criado_em, u.nome AS usuario
                                   FROM movimentacoes_estoque m LEFT JOIN usuarios u ON u.id = m.usuario_id
                                   WHERE m.produto_id = ? ORDER BY m.id DESC LIMIT 30', [(int) $p['id']])]);
}

function admin_estoque_ajuste(array $p)
{
    $u = admin_user('estoque.ajustar');
    $id = (int) $p['id'];
    $tipo = (string) in('tipo', '');
    $qtd = (int) in('quantidade', -1);
    $motivo = str_in('motivo', 255);
    if (!in_array($tipo, ['entrada', 'saida', 'ajuste'], true)) fail('Tipo de movimentação inválido.');
    if ($qtd < 0 || ($tipo !== 'ajuste' && $qtd < 1)) fail('Informe uma quantidade válida.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if (!q_val('SELECT 1 FROM produtos WHERE id = ? AND excluido_em IS NULL', [$id])) { $pdo->rollBack(); fail('Produto não encontrado.', 404); }
        q('INSERT IGNORE INTO estoque (produto_id, quantidade, estoque_minimo) VALUES (?, 0, 5)', [$id]);
        $ant = (int) q_val('SELECT quantidade FROM estoque WHERE produto_id = ? FOR UPDATE', [$id]);
        $novo = $tipo === 'entrada' ? $ant + $qtd : ($tipo === 'saida' ? $ant - $qtd : $qtd);
        if ($novo < 0) { $pdo->rollBack(); fail("Saldo insuficiente: o estoque atual é $ant."); }
        q('UPDATE estoque SET quantidade = ? WHERE produto_id = ?', [$novo, $id]);
        q('INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, usuario_id) VALUES (?,?,?,?,?,?,?)',
          [$id, $tipo, $tipo === 'ajuste' ? abs($novo - $ant) : $qtd, $ant, $novo, $motivo, $u['id']]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    audit($u['id'], 'estoque.ajuste', 'produtos', $id, ['quantidade' => $ant], ['quantidade' => $novo, 'tipo' => $tipo]);
    out(['message' => 'Estoque atualizado.', 'quantidade' => $novo]);
}

/* ---------------------------------------------------------------- categorias */

function admin_categorias()
{
    admin_user('produtos.ver');
    out(['categorias' => q_all('SELECT c.id, c.pai_id, c.nome, c.slug, c.descricao, c.icone, c.ordem, c.ativo,
                                       (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id AND p.excluido_em IS NULL) AS total_produtos
                                FROM categorias c ORDER BY c.ordem, c.nome')]);
}

function admin_categoria_save(array $p = [])
{
    $u = admin_user('categorias.gerenciar');
    $nome = str_in('nome', 100);
    if (!$nome) fail('Informe o nome da categoria.');
    $pai = in('pai_id') ? (int) in('pai_id') : null;
    $ordem = (int) in('ordem', 0);
    $ativo = in('ativo') === null ? 1 : (in('ativo') ? 1 : 0);
    $desc = str_in('descricao', 255);

    if (isset($p['id'])) {
        $id = (int) $p['id'];
        if ($pai === $id) fail('Uma categoria não pode ser pai de si mesma.');
        q('UPDATE categorias SET nome=?, pai_id=?, descricao=?, ordem=?, ativo=? WHERE id=?', [$nome, $pai, $desc, $ordem, $ativo, $id]);
        audit($u['id'], 'categoria.atualizada', 'categorias', $id);
        out(['message' => 'Categoria atualizada.']);
    }
    $slug = slugify($nome); $base = $slug; $n = 2;
    while (q_val('SELECT 1 FROM categorias WHERE slug = ?', [$slug])) $slug = $base . '-' . $n++;
    q('INSERT INTO categorias (nome, slug, pai_id, descricao, icone, ordem, ativo) VALUES (?,?,?,?,?,?,?)', [$nome, $slug, $pai, $desc, null, $ordem, $ativo]);
    $id = (int) db()->lastInsertId();
    audit($u['id'], 'categoria.criada', 'categorias', $id);
    out(['message' => 'Categoria criada.', 'id' => $id], 201);
}

/* -------------------------------------------------------------------- cupons */

function admin_cupons()
{
    admin_user('cupons.gerenciar');
    $rows = q_all('SELECT * FROM cupons ORDER BY ativo DESC, id DESC');
    foreach ($rows as &$r) { $r['valor'] = (float) $r['valor']; $r['valor_minimo_pedido'] = (float) $r['valor_minimo_pedido']; }
    out(['cupons' => $rows]);
}

function admin_cupom_save(array $p = [])
{
    $u = admin_user('cupons.gerenciar');
    $tipo = (string) in('tipo', '');
    if (!in_array($tipo, ['percentual', 'valor_fixo', 'frete_gratis'], true)) fail('Tipo de cupom inválido.');
    $valor = num_in('valor', false);
    if ($tipo === 'percentual' && ($valor <= 0 || $valor > 100)) fail('O percentual deve estar entre 1 e 100.');
    if ($tipo === 'valor_fixo' && $valor <= 0) fail('Informe o valor do desconto.');
    $fields = [$tipo, $valor, max(0, (float) num_in('valor_minimo_pedido', false)), in('limite_total') ? (int) in('limite_total') : null,
               max(1, (int) in('limite_por_cliente', 1)), dt_in(in('inicio_em')), dt_in(in('fim_em')), in('ativo') === null ? 1 : (in('ativo') ? 1 : 0), str_in('descricao', 160)];

    if (isset($p['id'])) {
        q('UPDATE cupons SET tipo=?, valor=?, valor_minimo_pedido=?, limite_total=?, limite_por_cliente=?, inicio_em=?, fim_em=?, ativo=?, descricao=? WHERE id=?', array_merge($fields, [(int) $p['id']]));
        audit($u['id'], 'cupom.atualizado', 'cupons', (int) $p['id']);
        out(['message' => 'Cupom atualizado.']);
    }
    $codigo = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) in('codigo', '')));
    if (strlen($codigo) < 3) fail('O código deve ter ao menos 3 caracteres (letras e números).');
    if (q_val('SELECT 1 FROM cupons WHERE codigo = ?', [$codigo])) fail('Já existe um cupom com este código.', 409);
    q('INSERT INTO cupons (codigo, tipo, valor, valor_minimo_pedido, limite_total, limite_por_cliente, inicio_em, fim_em, ativo, descricao) VALUES (?,?,?,?,?,?,?,?,?,?)', array_merge([$codigo], $fields));
    $id = (int) db()->lastInsertId();
    audit($u['id'], 'cupom.criado', 'cupons', $id, null, ['codigo' => $codigo]);
    out(['message' => 'Cupom criado.', 'id' => $id], 201);
}

/* ------------------------------------------------------ catálogo de serviços */

function admin_servicos()
{
    admin_user('servicos.ver');
    $rows = q_all('SELECT s.id, s.nome, s.slug, s.descricao, s.tipo_preco, s.preco_inicial, s.tempo_estimado_min, s.disponivel, s.categoria_id, c.nome AS categoria_nome
                   FROM servicos s JOIN categorias_servicos c ON c.id = s.categoria_id WHERE s.excluido_em IS NULL ORDER BY c.ordem, s.nome');
    foreach ($rows as &$r) if ($r['preco_inicial'] !== null) $r['preco_inicial'] = (float) $r['preco_inicial'];
    out(['servicos' => $rows]);
}

function admin_servico_save(array $p = [])
{
    $u = admin_user('servicos.gerenciar');
    $nome = str_in('nome', 160);
    $cat = (int) in('categoria_id', 0);
    $tipo = in('tipo_preco') === 'preco_inicial' ? 'preco_inicial' : 'sob_orcamento';
    $preco = $tipo === 'preco_inicial' ? num_in('preco_inicial') : null;
    if (!$nome) fail('Informe o nome do serviço.');
    if (!q_val('SELECT 1 FROM categorias_servicos WHERE id = ?', [$cat])) fail('Selecione uma categoria válida.');
    if ($tipo === 'preco_inicial' && ($preco === null || $preco <= 0)) fail('Informe o preço inicial.');
    $tempo = in('tempo_estimado_min') ? (int) in('tempo_estimado_min') : null;
    $disp = in('disponivel') === null ? 1 : (in('disponivel') ? 1 : 0);
    $desc = str_in('descricao', 5000);

    if (isset($p['id'])) {
        q('UPDATE servicos SET nome=?, categoria_id=?, descricao=?, tipo_preco=?, preco_inicial=?, tempo_estimado_min=?, disponivel=? WHERE id=? AND excluido_em IS NULL',
          [$nome, $cat, $desc, $tipo, $preco, $tempo, $disp, (int) $p['id']]);
        audit($u['id'], 'servico.atualizado', 'servicos', (int) $p['id']);
        out(['message' => 'Serviço atualizado.']);
    }
    $slug = slugify($nome); $base = $slug; $n = 2;
    while (q_val('SELECT 1 FROM servicos WHERE slug = ?', [$slug])) $slug = $base . '-' . $n++;
    q('INSERT INTO servicos (categoria_id, nome, slug, descricao, tipo_preco, preco_inicial, tempo_estimado_min, disponivel) VALUES (?,?,?,?,?,?,?,?)',
      [$cat, $nome, $slug, $desc, $tipo, $preco, $tempo, $disp]);
    $id = (int) db()->lastInsertId();
    audit($u['id'], 'servico.criado', 'servicos', $id);
    out(['message' => 'Serviço criado.', 'id' => $id], 201);
}

function admin_servico_delete(array $p)
{
    $u = admin_user('servicos.gerenciar');
    $st = q('UPDATE servicos SET excluido_em = NOW(), disponivel = 0 WHERE id = ? AND excluido_em IS NULL', [(int) $p['id']]);
    if (!$st->rowCount()) fail('Serviço não encontrado.', 404);
    audit($u['id'], 'servico.excluido', 'servicos', (int) $p['id']);
    out(['message' => 'Serviço excluído.']);
}
