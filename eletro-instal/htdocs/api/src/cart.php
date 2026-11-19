<?php
/** Carrinho (visitante via X-Cart-Token; logado via cliente) e cupons. */

function cart_token_header()
{
    $t = $_SERVER['HTTP_X_CART_TOKEN'] ?? '';
    return preg_match('/^[a-f0-9]{64}$/', $t) ? $t : null;
}

/** Resolve (e opcionalmente cria) o carrinho ativo. Funde o carrinho de visitante ao do cliente no login. */
function cart_resolve($create)
{
    $user = current_user(false);
    $cid  = $user ? cliente_id_of($user['id']) : null;
    $tok  = cart_token_header();
    $pdo  = db();

    if ($cid) {
        $cart = q_one("SELECT * FROM carrinhos WHERE cliente_id = ? AND status = 'ativo' ORDER BY atualizado_em DESC, id DESC LIMIT 1", [$cid]);
        $guest = $tok ? q_one("SELECT * FROM carrinhos WHERE token_sessao = ? AND status = 'ativo' AND cliente_id IS NULL", [$tok]) : null;

        if ($guest) {
            if (!$cart) {                                    // adota o carrinho do visitante
                q('UPDATE carrinhos SET cliente_id = ?, token_sessao = NULL WHERE id = ?', [$cid, $guest['id']]);
                return q_one('SELECT * FROM carrinhos WHERE id = ?', [$guest['id']]);
            }
            $pdo->beginTransaction();                        // funde os itens
            try {
                q('INSERT INTO itens_carrinho (carrinho_id, produto_id, quantidade)
                   SELECT ?, produto_id, quantidade FROM itens_carrinho WHERE carrinho_id = ?
                   ON DUPLICATE KEY UPDATE quantidade = itens_carrinho.quantidade + VALUES(quantidade)', [$cart['id'], $guest['id']]);
                q("UPDATE carrinhos SET status = 'abandonado', token_sessao = NULL WHERE id = ?", [$guest['id']]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        }
        if ($cart) return $cart;
        if (!$create) return null;
        q("INSERT INTO carrinhos (cliente_id, status) VALUES (?, 'ativo')", [$cid]);
        return q_one('SELECT * FROM carrinhos WHERE id = ?', [$pdo->lastInsertId()]);
    }

    if ($tok) {
        $cart = q_one("SELECT * FROM carrinhos WHERE token_sessao = ? AND status = 'ativo' AND cliente_id IS NULL", [$tok]);
        if ($cart) return $cart;
    }
    if (!$create) return null;
    $new = bin2hex(random_bytes(32));
    q("INSERT INTO carrinhos (cliente_id, token_sessao, status) VALUES (NULL, ?, 'ativo')", [$new]);
    return q_one('SELECT * FROM carrinhos WHERE id = ?', [$pdo->lastInsertId()]);
}

function cart_items($cartId)
{
    $preco = sql_preco('p');
    $rows = q_all("SELECT ic.id, ic.produto_id, ic.quantidade, p.nome, p.sku, p.slug, p.preco AS preco_cheio, p.custo,
                          ($preco) AS preco_unitario, COALESCE(e.quantidade, 0) AS estoque,
                          (SELECT ip.caminho FROM imagens_produtos ip WHERE ip.produto_id = p.id ORDER BY ip.principal DESC, ip.ordem, ip.id LIMIT 1) AS imagem
                   FROM itens_carrinho ic
                   JOIN produtos p ON p.id = ic.produto_id AND p.ativo = 1 AND p.excluido_em IS NULL
                   LEFT JOIN estoque e ON e.produto_id = p.id
                   WHERE ic.carrinho_id = ? ORDER BY ic.id", [$cartId]);
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id']; $r['produto_id'] = (int) $r['produto_id']; $r['quantidade'] = (int) $r['quantidade'];
        $r['estoque'] = (int) $r['estoque'];
        $r['preco_unitario'] = (float) $r['preco_unitario'];
        $r['preco_cheio'] = (float) $r['preco_cheio'];
        $r['subtotal'] = money($r['preco_unitario'] * $r['quantidade']);
        $r['disponivel'] = $r['estoque'] >= $r['quantidade'];
    }
    return $rows;
}

/** Valida cupom contra o subtotal. Retorna [cupom|null, mensagem_erro|null]. */
function coupon_validate($cupom, $subtotal, $clienteId)
{
    if (!$cupom || !(int) $cupom['ativo']) return [null, 'Cupom inválido.'];
    $now = time();
    if ($cupom['inicio_em'] && strtotime($cupom['inicio_em']) > $now) return [null, 'Este cupom ainda não está válido.'];
    if ($cupom['fim_em'] && strtotime($cupom['fim_em']) < $now) return [null, 'Este cupom expirou.'];
    if ($cupom['limite_total'] !== null && (int) $cupom['total_usos'] >= (int) $cupom['limite_total']) return [null, 'Este cupom está esgotado.'];
    if ($subtotal < (float) $cupom['valor_minimo_pedido']) {
        return [null, 'Pedido mínimo de R$ ' . number_format((float) $cupom['valor_minimo_pedido'], 2, ',', '.') . ' para usar este cupom.'];
    }
    if ($clienteId) {
        $usos = (int) q_val('SELECT COUNT(*) FROM cupons_uso WHERE cupom_id = ? AND cliente_id = ?', [$cupom['id'], $clienteId]);
        if ($usos >= (int) $cupom['limite_por_cliente']) return [null, 'Você já utilizou este cupom.'];
    }
    return [$cupom, null];
}

function cart_totals(array $itens, $cupom)
{
    $subtotal = 0.0;
    foreach ($itens as $i) $subtotal += $i['subtotal'];
    $subtotal = money($subtotal);

    $desc = 0.0; $freteGratis = false;
    if ($cupom) {
        if ($cupom['tipo'] === 'percentual')   $desc = $subtotal * ((float) $cupom['valor'] / 100);
        if ($cupom['tipo'] === 'valor_fixo')   $desc = min((float) $cupom['valor'], $subtotal);
        if ($cupom['tipo'] === 'frete_gratis') $freteGratis = true;
    }
    $desc = money($desc);

    $frete = (float) setting('frete_fixo', 0);
    $gratisAcima = (float) setting('frete_gratis_acima', 0);
    if ($freteGratis || ($gratisAcima > 0 && $subtotal >= $gratisAcima)) $frete = 0.0;

    return ['subtotal' => $subtotal, 'desconto' => $desc, 'frete' => money($frete), 'total' => money(max(0, $subtotal - $desc + $frete))];
}

function cart_payload($cart)
{
    if (!$cart) {
        return ['carrinho' => ['id' => null, 'token_sessao' => null], 'itens' => [], 'total_itens' => 0,
                'subtotal' => 0, 'desconto' => 0, 'frete' => 0, 'total' => 0, 'cupom' => null];
    }
    $itens = cart_items($cart['id']);
    $user = current_user(false);
    $cid = $user ? cliente_id_of($user['id']) : null;

    $cupom = null;
    if ($cart['cupom_id']) {
        $c = q_one('SELECT * FROM cupons WHERE id = ?', [$cart['cupom_id']]);
        $sub = 0; foreach ($itens as $i) $sub += $i['subtotal'];
        list($cupom, $err) = coupon_validate($c, money($sub), $cid);
        if (!$cupom) q('UPDATE carrinhos SET cupom_id = NULL WHERE id = ?', [$cart['id']]);
    }
    $t = cart_totals($itens, $cupom);
    foreach ($itens as &$i) unset($i['custo']);

    return array_merge([
        'carrinho'    => ['id' => (int) $cart['id'], 'token_sessao' => $cart['token_sessao']],
        'itens'       => $itens,
        'total_itens' => array_sum(array_column($itens, 'quantidade')),
        'cupom'       => $cupom ? ['codigo' => $cupom['codigo'], 'tipo' => $cupom['tipo'], 'valor' => (float) $cupom['valor'], 'descricao' => $cupom['descricao']] : null,
    ], $t);
}

function cart_get()
{
    out(cart_payload(cart_resolve(false)));
}

function cart_add()
{
    $pid = (int) in('produto_id', 0);
    $qtd = (int) in('quantidade', 1);
    if ($pid < 1 || $qtd < 1) fail('Produto ou quantidade inválidos.');
    if ($qtd > 999) fail('Quantidade máxima por item: 999.');

    $est = q_one('SELECT p.nome, COALESCE(e.quantidade, 0) AS qtd FROM produtos p LEFT JOIN estoque e ON e.produto_id = p.id
                  WHERE p.id = ? AND p.ativo = 1 AND p.excluido_em IS NULL', [$pid]);
    if (!$est) fail('Produto indisponível.', 404);

    $cart = cart_resolve(true);
    $ja = (int) q_val('SELECT quantidade FROM itens_carrinho WHERE carrinho_id = ? AND produto_id = ?', [$cart['id'], $pid]);
    if ($ja + $qtd > (int) $est['qtd']) {
        fail($est['qtd'] > 0 ? "Estoque insuficiente: restam {$est['qtd']} unidade(s) de {$est['nome']}." : "{$est['nome']} está sem estoque.", 409);
    }
    q('INSERT INTO itens_carrinho (carrinho_id, produto_id, quantidade) VALUES (?,?,?)
       ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)', [$cart['id'], $pid, $qtd]);
    q('UPDATE carrinhos SET atualizado_em = NOW() WHERE id = ?', [$cart['id']]);
    out(cart_payload(q_one('SELECT * FROM carrinhos WHERE id = ?', [$cart['id']])));
}

function cart_update(array $p)
{
    $qtd = (int) in('quantidade', 0);
    if ($qtd < 1) fail('Quantidade inválida.');
    $cart = cart_resolve(false);
    if (!$cart) fail('Carrinho não encontrado.', 404);
    $it = q_one('SELECT ic.id, COALESCE(e.quantidade, 0) AS estoque, p.nome FROM itens_carrinho ic
                 JOIN produtos p ON p.id = ic.produto_id LEFT JOIN estoque e ON e.produto_id = p.id
                 WHERE ic.id = ? AND ic.carrinho_id = ?', [(int) $p['id'], $cart['id']]);
    if (!$it) fail('Item não encontrado.', 404);
    if ($qtd > (int) $it['estoque']) fail("Estoque insuficiente: restam {$it['estoque']} unidade(s) de {$it['nome']}.", 409);
    q('UPDATE itens_carrinho SET quantidade = ? WHERE id = ?', [$qtd, $it['id']]);
    out(cart_payload($cart));
}

function cart_remove(array $p)
{
    $cart = cart_resolve(false);
    if (!$cart) fail('Carrinho não encontrado.', 404);
    q('DELETE FROM itens_carrinho WHERE id = ? AND carrinho_id = ?', [(int) $p['id'], $cart['id']]);
    out(cart_payload($cart));
}

function cart_coupon_apply()
{
    $codigo = strtoupper(trim((string) in('codigo', '')));
    if ($codigo === '') fail('Informe o código do cupom.');
    $cart = cart_resolve(false);
    if (!$cart) fail('Seu carrinho está vazio.');
    $itens = cart_items($cart['id']);
    if (!$itens) fail('Adicione produtos antes de aplicar um cupom.');

    $user = current_user(false);
    $cid = $user ? cliente_id_of($user['id']) : null;
    $sub = 0; foreach ($itens as $i) $sub += $i['subtotal'];
    $c = q_one('SELECT * FROM cupons WHERE codigo = ?', [$codigo]);
    list($ok, $err) = coupon_validate($c, money($sub), $cid);
    if (!$ok) fail($err ?: 'Cupom inválido.', 400);
    q('UPDATE carrinhos SET cupom_id = ? WHERE id = ?', [$ok['id'], $cart['id']]);
    out(cart_payload(q_one('SELECT * FROM carrinhos WHERE id = ?', [$cart['id']])));
}

function cart_coupon_remove()
{
    $cart = cart_resolve(false);
    if ($cart) q('UPDATE carrinhos SET cupom_id = NULL WHERE id = ?', [$cart['id']]);
    out(cart_payload($cart ? q_one('SELECT * FROM carrinhos WHERE id = ?', [$cart['id']]) : null));
}
