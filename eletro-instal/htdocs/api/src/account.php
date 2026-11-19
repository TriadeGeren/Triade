<?php
/** Área do cliente: pedidos e endereços. */

function conta_pedidos()
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    if (!$cid) out(['pedidos' => []]);
    $rows = q_all("SELECT p.id, p.numero, p.total, p.status, p.criado_em,
                          (SELECT COALESCE(SUM(ip.quantidade),0) FROM itens_pedido ip WHERE ip.pedido_id = p.id) AS itens
                   FROM pedidos p WHERE p.cliente_id = ? ORDER BY p.criado_em DESC, p.id DESC LIMIT 100", [$cid]);
    foreach ($rows as &$r) { $r['total'] = (float) $r['total']; $r['itens'] = (int) $r['itens']; }
    out(['pedidos' => $rows]);
}

function conta_pedido(array $p)
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    $ped = q_one('SELECT id, numero, subtotal, desconto, frete, total, status, observacoes, criado_em,
                         entrega_destinatario, entrega_cep, entrega_logradouro, entrega_numero, entrega_complemento,
                         entrega_bairro, entrega_cidade, entrega_uf
                  FROM pedidos WHERE numero = ? AND cliente_id = ?', [$p['numero'], $cid]);
    if (!$ped) fail('Pedido não encontrado.', 404);
    $itens = q_all('SELECT nome_produto, sku, preco_unitario, quantidade, subtotal FROM itens_pedido WHERE pedido_id = ?', [$ped['id']]);
    $pag = q_one('SELECT metodo, status, valor FROM pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1', [$ped['id']]);
    $hist = q_all('SELECT status_novo, observacao, criado_em FROM pedidos_historico_status WHERE pedido_id = ? ORDER BY id', [$ped['id']]);
    $ent = q_one('SELECT transportadora, servico, codigo_rastreio, status FROM entregas WHERE pedido_id = ?', [$ped['id']]);
    out(['pedido' => $ped, 'itens' => $itens, 'pagamento' => $pag, 'historico' => $hist, 'entrega' => $ent]);
}

function conta_enderecos()
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    out(['enderecos' => $cid ? q_all('SELECT id, apelido, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, padrao
                                      FROM enderecos WHERE cliente_id = ? ORDER BY padrao DESC, id DESC', [$cid]) : []]);
}

function conta_endereco_criar()
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    if (!$cid) fail('Cadastro de cliente não encontrado.', 403);
    $e = read_address($cid);
    $id = address_save($cid, $e);
    out(['message' => 'Endereço salvo.', 'id' => $id], 201);
}
