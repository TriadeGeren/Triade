<?php
/** Checkout transacional: estoque travado, snapshots, cupom, pagamento pendente. */

function read_address($clienteId)
{
    $id = (int) in('endereco_id', 0);
    if ($id) {
        $e = q_one('SELECT * FROM enderecos WHERE id = ? AND cliente_id = ?', [$id, $clienteId]);
        if (!$e) fail('Endereço não encontrado.', 404);
        return $e;
    }
    $a = in('endereco');
    if (!is_array($a)) fail('Informe o endereço de entrega.');
    $e = [
        'apelido'      => mb_substr(trim((string) ($a['apelido'] ?? '')), 0, 40) ?: null,
        'destinatario' => mb_substr(trim((string) ($a['destinatario'] ?? '')), 0, 120),
        'cep'          => preg_replace('/\D/', '', (string) ($a['cep'] ?? '')),
        'logradouro'   => mb_substr(trim((string) ($a['logradouro'] ?? '')), 0, 160),
        'numero'       => mb_substr(trim((string) ($a['numero'] ?? '')), 0, 20),
        'complemento'  => mb_substr(trim((string) ($a['complemento'] ?? '')), 0, 80) ?: null,
        'bairro'       => mb_substr(trim((string) ($a['bairro'] ?? '')), 0, 80),
        'cidade'       => mb_substr(trim((string) ($a['cidade'] ?? '')), 0, 80),
        'uf'           => strtoupper(substr(trim((string) ($a['uf'] ?? '')), 0, 2)),
    ];
    foreach (['destinatario' => 'nome do destinatário', 'logradouro' => 'rua', 'numero' => 'número', 'bairro' => 'bairro', 'cidade' => 'cidade'] as $k => $label) {
        if ($e[$k] === '') fail("Preencha o campo: $label.");
    }
    if (strlen($e['cep']) !== 8) fail('CEP inválido (use 8 números).');
    if (!preg_match('/^[A-Z]{2}$/', $e['uf'])) fail('UF inválida.');
    return $e;
}

function address_save($clienteId, array $e)
{
    if (isset($e['id'])) return (int) $e['id'];
    $first = !q_val('SELECT 1 FROM enderecos WHERE cliente_id = ? LIMIT 1', [$clienteId]);
    q('INSERT INTO enderecos (cliente_id, apelido, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, padrao)
       VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$clienteId, $e['apelido'], $e['destinatario'], $e['cep'], $e['logradouro'], $e['numero'],
        $e['complemento'], $e['bairro'], $e['cidade'], $e['uf'], $first ? 1 : 0]);
    return (int) db()->lastInsertId();
}

function checkout_create()
{
    $user = current_user(true);
    $cid = cliente_id_of($user['id']);
    if (!$cid) fail('Sua conta não possui cadastro de cliente. Use uma conta de cliente para comprar.', 403);

    $metodo = (string) in('metodo', 'pix');
    if (!in_array($metodo, ['pix', 'transferencia', 'dinheiro', 'boleto'], true)) fail('Forma de pagamento inválida.');
    $obs = str_in('observacoes', 500);
    $end = read_address($cid);

    // Resolve (e funde com o de visitante, se houver) ANTES de abrir a transação
    $cart0 = cart_resolve(false);
    if (!$cart0 || (int) $cart0['cliente_id'] !== $cid) fail('Seu carrinho está vazio.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $cart = q_one("SELECT * FROM carrinhos WHERE id = ? AND status = 'ativo' FOR UPDATE", [$cart0['id']]);
        if (!$cart) { $pdo->rollBack(); fail('Seu carrinho está vazio.'); }

        $itens = cart_items($cart['id']);
        if (!$itens) { $pdo->rollBack(); fail('Seu carrinho está vazio.'); }

        // Trava as linhas de estoque e confere de novo (evita vender além do disponível)
        foreach ($itens as $i) {
            $saldo = q_val('SELECT quantidade FROM estoque WHERE produto_id = ? FOR UPDATE', [$i['produto_id']]);
            if ($saldo === null || (int) $saldo < $i['quantidade']) {
                $pdo->rollBack();
                fail("Estoque insuficiente para \"{$i['nome']}\". Ajuste o carrinho e tente novamente.", 409);
            }
        }

        $cupom = null;
        if ($cart['cupom_id']) {
            $c = q_one('SELECT * FROM cupons WHERE id = ? FOR UPDATE', [$cart['cupom_id']]);
            $sub = 0; foreach ($itens as $i) $sub += $i['subtotal'];
            list($cupom, $err) = coupon_validate($c, money($sub), $cid);
            if (!$cupom) { $pdo->rollBack(); fail($err ?: 'Cupom inválido.'); }
        }
        $t = cart_totals($itens, $cupom);

        $endId = address_save($cid, $end);

        q("INSERT INTO pedidos (numero, cliente_id, endereco_id, cupom_id, subtotal, desconto, frete, total, status, observacoes,
                                entrega_destinatario, entrega_cep, entrega_logradouro, entrega_numero, entrega_complemento,
                                entrega_bairro, entrega_cidade, entrega_uf)
           VALUES (?,?,?,?,?,?,?,?, 'aguardando_pagamento', ?, ?,?,?,?,?,?,?,?)", [
            'TMP-' . bin2hex(random_bytes(7)), $cid, $endId, $cupom ? $cupom['id'] : null,
            $t['subtotal'], $t['desconto'], $t['frete'], $t['total'], $obs,
            $end['destinatario'], $end['cep'], $end['logradouro'], $end['numero'], $end['complemento'],
            $end['bairro'], $end['cidade'], $end['uf'],
        ]);
        $pedidoId = (int) $pdo->lastInsertId();
        $numero = sprintf('EI-%s-%06d', date('Y'), $pedidoId);
        q('UPDATE pedidos SET numero = ? WHERE id = ?', [$numero, $pedidoId]);

        foreach ($itens as $i) {
            q('INSERT INTO itens_pedido (pedido_id, produto_id, nome_produto, sku, preco_unitario, custo_unitario, quantidade, subtotal)
               VALUES (?,?,?,?,?,?,?,?)', [$pedidoId, $i['produto_id'], $i['nome'], $i['sku'], $i['preco_unitario'],
                $i['custo'] !== null ? $i['custo'] : null, $i['quantidade'], $i['subtotal']]);

            $saldoAnt = (int) q_val('SELECT quantidade FROM estoque WHERE produto_id = ?', [$i['produto_id']]);
            q('UPDATE estoque SET quantidade = quantidade - ? WHERE produto_id = ?', [$i['quantidade'], $i['produto_id']]);
            q("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, pedido_id, usuario_id)
               VALUES (?, 'venda', ?, ?, ?, ?, ?, ?)", [$i['produto_id'], $i['quantidade'], $saldoAnt, $saldoAnt - $i['quantidade'],
                "Pedido $numero", $pedidoId, $user['id']]);
        }

        if ($cupom) {
            q('INSERT INTO cupons_uso (cupom_id, cliente_id, pedido_id) VALUES (?,?,?)', [$cupom['id'], $cid, $pedidoId]);
            q('UPDATE cupons SET total_usos = total_usos + 1 WHERE id = ?', [$cupom['id']]);
        }

        q("INSERT INTO pagamentos (pedido_id, gateway, metodo, valor, status) VALUES (?, 'manual', ?, ?, 'pendente')", [$pedidoId, $metodo, $t['total']]);
        q('INSERT INTO pedidos_historico_status (pedido_id, status_anterior, status_novo, observacao, usuario_id)
           VALUES (?, NULL, ?, ?, ?)', [$pedidoId, 'aguardando_pagamento', 'Pedido criado pelo cliente', $user['id']]);
        q("UPDATE carrinhos SET status = 'convertido', cupom_id = NULL WHERE id = ?", [$cart['id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    out(['message' => 'Pedido criado com sucesso.',
         'pedido' => ['id' => $pedidoId, 'numero' => $numero, 'total' => $t['total'], 'status' => 'aguardando_pagamento', 'metodo' => $metodo],
         'whatsapp' => preg_replace('/\D/', '', (string) setting('loja_whatsapp', ''))], 201);
}
