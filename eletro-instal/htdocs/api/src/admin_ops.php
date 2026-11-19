<?php
/** Admin: dashboard, pedidos e solicitações de serviço. */

const PEDIDO_STATUS = ['aguardando_pagamento', 'pagamento_aprovado', 'em_preparacao', 'enviado', 'em_transporte', 'entregue', 'cancelado'];
const SOLIC_STATUS  = ['solicitado', 'em_analise', 'orcamento_enviado', 'orcamento_aprovado', 'agendado', 'em_execucao', 'concluido', 'recusado', 'cancelado'];

function last_months($n = 6)
{
    $out = [];
    for ($i = $n - 1; $i >= 0; $i--) $out[] = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
    return $out;
}

function admin_dashboard()
{
    admin_user('dashboard.ver');
    $meses = last_months(6);
    $ini = $meses[0] . '-01';
    $mesAtual = date('Y-m'); $mesAnt = date('Y-m', strtotime('first day of last month'));

    $fat = [];
    foreach (q_all("SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes, COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS faturamento
                    FROM pedidos WHERE status <> 'cancelado' AND criado_em >= ? GROUP BY mes", [$ini]) as $r) $fat[$r['mes']] = $r;
    $faturamento = [];
    foreach ($meses as $m) $faturamento[] = ['mes' => $m, 'total_pedidos' => (int) ($fat[$m]['pedidos'] ?? 0), 'faturamento' => (float) ($fat[$m]['faturamento'] ?? 0)];

    $fin = [];
    foreach (q_all("SELECT mes, SUM(rec) AS rec, SUM(desp) AS desp FROM (
                      SELECT DATE_FORMAT(recebida_em, '%Y-%m') AS mes, valor AS rec, 0 AS desp FROM receitas WHERE status = 'recebida' AND recebida_em >= ?
                      UNION ALL
                      SELECT DATE_FORMAT(paga_em, '%Y-%m'), 0, valor FROM despesas WHERE status = 'paga' AND paga_em >= ?
                    ) t GROUP BY mes", [$ini, $ini]) as $r) $fin[$r['mes']] = $r;
    $resumo = [];
    foreach ($meses as $m) {
        $rc = (float) ($fin[$m]['rec'] ?? 0); $dp = (float) ($fin[$m]['desp'] ?? 0);
        $resumo[] = ['mes' => $m, 'receita_recebida' => $rc, 'despesas_pagas' => $dp, 'resultado' => round($rc - $dp, 2)];
    }

    $fatAtual = $faturamento[count($faturamento) - 1];
    $fatAnt = $fat[$mesAnt]['faturamento'] ?? 0;

    $estoqueBaixo = q_all('SELECT p.id, p.sku, p.nome, e.quantidade, e.estoque_minimo FROM produtos p JOIN estoque e ON e.produto_id = p.id
                           WHERE p.excluido_em IS NULL AND p.ativo = 1 AND e.quantidade <= e.estoque_minimo ORDER BY e.quantidade, p.nome LIMIT 10');
    $totalBaixo = (int) q_val('SELECT COUNT(*) FROM produtos p JOIN estoque e ON e.produto_id = p.id
                               WHERE p.excluido_em IS NULL AND p.ativo = 1 AND e.quantidade <= e.estoque_minimo');

    out([
        'kpis' => [
            'faturamento_mes'          => $fatAtual['faturamento'],
            'faturamento_mes_anterior' => (float) $fatAnt,
            'pedidos_mes'              => $fatAtual['total_pedidos'],
            'ticket_medio'             => $fatAtual['total_pedidos'] ? round($fatAtual['faturamento'] / $fatAtual['total_pedidos'], 2) : 0,
            'pedidos_aguardando_pagamento' => (int) q_val("SELECT COUNT(*) FROM pedidos WHERE status = 'aguardando_pagamento'"),
            'solicitacoes_abertas'     => (int) q_val("SELECT COUNT(*) FROM solicitacoes_servicos WHERE status IN ('solicitado','em_analise')"),
            'estoque_baixo_total'      => $totalBaixo,
            'clientes_total'           => (int) q_val('SELECT COUNT(*) FROM clientes'),
            'produtos_ativos'          => (int) q_val('SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND excluido_em IS NULL'),
        ],
        'faturamento_mensal' => $faturamento,
        'resumo_financeiro'  => $resumo,
        'status_pedidos'     => q_all('SELECT status, COUNT(*) AS total FROM pedidos GROUP BY status ORDER BY total DESC'),
        'status_solicitacoes'=> q_all('SELECT status, COUNT(*) AS total FROM solicitacoes_servicos GROUP BY status ORDER BY total DESC'),
        'estoque_baixo'      => $estoqueBaixo,
        'top_produtos'       => q_all('SELECT nome, sku, total_vendido FROM produtos WHERE excluido_em IS NULL AND total_vendido > 0 ORDER BY total_vendido DESC LIMIT 5'),
        'ultimos_pedidos'    => q_all('SELECT p.id, p.numero, p.total, p.status, p.criado_em, u.nome AS cliente_nome
                                       FROM pedidos p JOIN clientes c ON c.id = p.cliente_id JOIN usuarios u ON u.id = c.usuario_id
                                       ORDER BY p.criado_em DESC, p.id DESC LIMIT 6'),
    ]);
}

/* ------------------------------------------------------------------ pedidos */

function admin_pedidos()
{
    admin_user('pedidos.ver');
    list($page, $limit, $offset) = page_params(20, 100);
    $where = ['1=1']; $params = [];
    if (($s = qs('status')) && in_array($s, PEDIDO_STATUS, true)) { $where[] = 'p.status = ?'; $params[] = $s; }
    if ($b = qs('busca')) {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], mb_substr(trim((string) $b), 0, 60)) . '%';
        $where[] = '(p.numero LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    $from = 'FROM pedidos p JOIN clientes c ON c.id = p.cliente_id JOIN usuarios u ON u.id = c.usuario_id WHERE ' . implode(' AND ', $where);
    $total = (int) q_val("SELECT COUNT(*) $from", $params);
    $rows = q_all("SELECT p.id, p.numero, p.total, p.status, p.criado_em, u.nome AS cliente_nome, u.email AS cliente_email,
                          (SELECT metodo FROM pagamentos pg WHERE pg.pedido_id = p.id ORDER BY pg.id DESC LIMIT 1) AS metodo
                   $from ORDER BY p.criado_em DESC, p.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);
    foreach ($rows as &$r) $r['total'] = (float) $r['total'];
    out(['pedidos' => $rows, 'paginacao' => paginacao($page, $limit, $total)]);
}

function admin_pedido_get(array $p)
{
    admin_user('pedidos.ver');
    $ped = q_one('SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email, c.telefone AS cliente_telefone
                  FROM pedidos p JOIN clientes c ON c.id = p.cliente_id JOIN usuarios u ON u.id = c.usuario_id WHERE p.id = ?', [(int) $p['id']]);
    if (!$ped) fail('Pedido não encontrado.', 404);
    out([
        'pedido'    => $ped,
        'itens'     => q_all('SELECT nome_produto, sku, preco_unitario, quantidade, subtotal FROM itens_pedido WHERE pedido_id = ?', [$ped['id']]),
        'pagamento' => q_one('SELECT id, metodo, status, valor, pago_em FROM pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1', [$ped['id']]),
        'historico' => q_all('SELECT h.status_anterior, h.status_novo, h.observacao, h.criado_em, u.nome AS usuario
                              FROM pedidos_historico_status h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.pedido_id = ? ORDER BY h.id', [$ped['id']]),
    ]);
}

function admin_pedido_status(array $p)
{
    $user = admin_user('pedidos.atualizar');
    $novo = (string) in('status', '');
    $obs = str_in('observacao', 255);
    if (!in_array($novo, PEDIDO_STATUS, true)) fail('Status inválido.');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ped = q_one('SELECT id, numero, status, total FROM pedidos WHERE id = ? FOR UPDATE', [(int) $p['id']]);
        if (!$ped) { $pdo->rollBack(); fail('Pedido não encontrado.', 404); }
        $ant = $ped['status'];
        if ($ant === $novo) { $pdo->rollBack(); fail('O pedido já está com este status.'); }
        if ($ant === 'cancelado') { $pdo->rollBack(); fail('Pedido cancelado não pode ser reaberto. Peça ao cliente um novo pedido.'); }

        q('UPDATE pedidos SET status = ? WHERE id = ?', [$novo, $ped['id']]);

        // Cancelamento devolve o estoque
        if ($novo === 'cancelado') {
            foreach (q_all('SELECT produto_id, quantidade FROM itens_pedido WHERE pedido_id = ? AND produto_id IS NOT NULL', [$ped['id']]) as $it) {
                $ant_q = (int) q_val('SELECT quantidade FROM estoque WHERE produto_id = ? FOR UPDATE', [$it['produto_id']]);
                q('UPDATE estoque SET quantidade = quantidade + ? WHERE produto_id = ?', [$it['quantidade'], $it['produto_id']]);
                q("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, saldo_anterior, saldo_posterior, motivo, pedido_id, usuario_id)
                   VALUES (?, 'cancelamento', ?, ?, ?, ?, ?, ?)", [$it['produto_id'], $it['quantidade'], $ant_q, $ant_q + $it['quantidade'], "Cancelamento {$ped['numero']}", $ped['id'], $user['id']]);
            }
            q("UPDATE pagamentos SET status = 'cancelado' WHERE pedido_id = ? AND status = 'pendente'", [$ped['id']]);
        }

        // Primeira aprovação: baixa financeira + popularidade
        if ($novo === 'pagamento_aprovado' && $ant === 'aguardando_pagamento') {
            q("UPDATE pagamentos SET status = 'aprovado', pago_em = NOW() WHERE pedido_id = ? AND status = 'pendente'", [$ped['id']]);
            q('UPDATE produtos p JOIN itens_pedido ip ON ip.produto_id = p.id SET p.total_vendido = p.total_vendido + ip.quantidade WHERE ip.pedido_id = ?', [$ped['id']]);
            $pgId = q_val('SELECT id FROM pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1', [$ped['id']]);
            $cat = q_val("SELECT id FROM categorias_financeiras WHERE tipo = 'receita' AND nome = 'Venda de produtos'");
            q("INSERT INTO receitas (categoria_id, descricao, valor, status, recebida_em, pagamento_id, pedido_id, usuario_id)
               VALUES (?, ?, ?, 'recebida', CURDATE(), ?, ?, ?)", [$cat ?: null, "Pedido {$ped['numero']}", $ped['total'], $pgId ?: null, $ped['id'], $user['id']]);
        }

        q('INSERT INTO pedidos_historico_status (pedido_id, status_anterior, status_novo, observacao, usuario_id) VALUES (?,?,?,?,?)',
          [$ped['id'], $ant, $novo, $obs, $user['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    audit($user['id'], 'pedido.status', 'pedidos', (int) $p['id'], ['status' => $ant], ['status' => $novo]);
    out(['message' => 'Status atualizado.']);
}

/* -------------------------------------------------------------- solicitações */

function admin_solicitacoes()
{
    admin_user('servicos.ver');
    list($page, $limit, $offset) = page_params(20, 100);
    $where = ['1=1']; $params = [];
    if (($s = qs('status')) && in_array($s, SOLIC_STATUS, true)) { $where[] = 'ss.status = ?'; $params[] = $s; }
    $from = 'FROM solicitacoes_servicos ss JOIN servicos s ON s.id = ss.servico_id JOIN clientes c ON c.id = ss.cliente_id
             JOIN usuarios u ON u.id = c.usuario_id WHERE ' . implode(' AND ', $where);
    $total = (int) q_val("SELECT COUNT(*) $from", $params);
    $rows = q_all("SELECT ss.id, ss.protocolo, ss.status, ss.valor_orcado, ss.criado_em, s.nome AS servico_nome, u.nome AS cliente_nome
                   $from ORDER BY ss.criado_em DESC, ss.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);
    foreach ($rows as &$r) if ($r['valor_orcado'] !== null) $r['valor_orcado'] = (float) $r['valor_orcado'];
    out(['solicitacoes' => $rows, 'paginacao' => paginacao($page, $limit, $total)]);
}

function admin_solicitacao_get(array $p)
{
    admin_user('servicos.ver');
    $s = q_one('SELECT ss.*, s.nome AS servico_nome, u.nome AS cliente_nome, u.email AS cliente_email, c.telefone AS cliente_telefone,
                       e.logradouro, e.numero, e.bairro, e.cidade, e.uf, e.cep
                FROM solicitacoes_servicos ss JOIN servicos s ON s.id = ss.servico_id JOIN clientes c ON c.id = ss.cliente_id
                JOIN usuarios u ON u.id = c.usuario_id LEFT JOIN enderecos e ON e.id = ss.endereco_id WHERE ss.id = ?', [(int) $p['id']]);
    if (!$s) fail('Solicitação não encontrada.', 404);
    out(['solicitacao' => $s, 'historico' => q_all('SELECT h.status_anterior, h.status_novo, h.observacao, h.criado_em, u.nome AS usuario
        FROM solicitacoes_historico h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.solicitacao_id = ? ORDER BY h.id', [$s['id']])]);
}

function admin_solicitacao_update(array $p)
{
    $user = admin_user('servicos.gerenciar');
    $cur = q_one('SELECT status, valor_orcado FROM solicitacoes_servicos WHERE id = ?', [(int) $p['id']]);
    if (!$cur) fail('Solicitação não encontrada.', 404);

    $set = []; $par = [];
    $status = in('status');
    if ($status !== null && $status !== '') {
        if (!in_array($status, SOLIC_STATUS, true)) fail('Status inválido.');
        $set[] = 'status = ?'; $par[] = $status;
    }
    if (in('valor_orcado') !== null && in('valor_orcado') !== '') {
        $v = (float) str_replace(',', '.', (string) in('valor_orcado'));
        if ($v < 0) fail('Valor do orçamento inválido.');
        $set[] = 'valor_orcado = ?'; $par[] = $v;
    }
    if (in('prazo_validade_orcamento')) { $set[] = 'prazo_validade_orcamento = ?'; $par[] = (string) in('prazo_validade_orcamento'); }
    if (in('observacoes_internas') !== null) { $set[] = 'observacoes_internas = ?'; $par[] = str_in('observacoes_internas', 2000); }
    if (!$set) fail('Nada para atualizar.');

    $par[] = (int) $p['id'];
    q('UPDATE solicitacoes_servicos SET ' . implode(', ', $set) . ' WHERE id = ?', $par);
    if ($status && $status !== $cur['status']) {
        q('INSERT INTO solicitacoes_historico (solicitacao_id, status_anterior, status_novo, observacao, visivel_cliente, usuario_id) VALUES (?,?,?,?,1,?)',
          [(int) $p['id'], $cur['status'], $status, str_in('observacao', 255), $user['id']]);
    }
    audit($user['id'], 'solicitacao.atualizada', 'solicitacoes_servicos', (int) $p['id'], $cur, body());
    out(['message' => 'Solicitação atualizada.']);
}
