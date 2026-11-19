<?php
/** Admin: financeiro, clientes e configurações da loja. */

function admin_financeiro()
{
    admin_user('financeiro.ver');
    $mes = preg_match('/^\d{4}-\d{2}$/', (string) qs('mes', '')) ? qs('mes') : date('Y-m');
    $ini = $mes . '-01'; $fim = date('Y-m-t', strtotime($ini));

    $rec = q_all("SELECT r.id, r.descricao, r.valor, r.status, r.vencimento, r.recebida_em, r.pedido_id, c.nome AS categoria
                  FROM receitas r LEFT JOIN categorias_financeiras c ON c.id = r.categoria_id
                  WHERE (r.recebida_em BETWEEN ? AND ?) OR (r.status = 'a_receber' AND (r.vencimento IS NULL OR r.vencimento <= ?))
                  ORDER BY COALESCE(r.recebida_em, r.vencimento) DESC, r.id DESC LIMIT 200", [$ini, $fim, $fim]);
    $des = q_all("SELECT d.id, d.descricao, d.fornecedor, d.valor, d.status, d.vencimento, d.paga_em, c.nome AS categoria
                  FROM despesas d LEFT JOIN categorias_financeiras c ON c.id = d.categoria_id
                  WHERE (d.paga_em BETWEEN ? AND ?) OR (d.status = 'a_pagar' AND (d.vencimento IS NULL OR d.vencimento <= ?))
                  ORDER BY COALESCE(d.paga_em, d.vencimento) DESC, d.id DESC LIMIT 200", [$ini, $fim, $fim]);
    foreach ($rec as &$r) $r['valor'] = (float) $r['valor'];
    foreach ($des as &$d) $d['valor'] = (float) $d['valor'];

    $recebido = array_sum(array_map(function ($r) { return $r['status'] === 'recebida' ? $r['valor'] : 0; }, $rec));
    $pago = array_sum(array_map(function ($d) { return $d['status'] === 'paga' ? $d['valor'] : 0; }, $des));
    out([
        'mes' => $mes, 'receitas' => $rec, 'despesas' => $des,
        'totais' => [
            'recebido' => round($recebido, 2), 'pago' => round($pago, 2), 'resultado' => round($recebido - $pago, 2),
            'a_receber' => round(array_sum(array_map(function ($r) { return $r['status'] === 'a_receber' ? $r['valor'] : 0; }, $rec)), 2),
            'a_pagar'   => round(array_sum(array_map(function ($d) { return $d['status'] === 'a_pagar' ? $d['valor'] : 0; }, $des)), 2),
        ],
        'categorias' => q_all('SELECT id, tipo, nome FROM categorias_financeiras ORDER BY tipo, nome'),
    ]);
}

function admin_lancamento_create(array $p)
{
    $tipo = $p['tipo'];                       // receitas | despesas
    $u = admin_user('financeiro.gerenciar');
    $desc = str_in('descricao', 200);
    $valor = num_in('valor', false);
    if (!$desc) fail('Informe a descrição.');
    if ($valor <= 0) fail('Informe um valor maior que zero.');
    $cat = in('categoria_id') ? (int) in('categoria_id') : null;
    $venc = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) in('vencimento', '')) ? in('vencimento') : null;
    $quitado = in('quitado') ? 1 : 0;

    if ($tipo === 'receitas') {
        q('INSERT INTO receitas (categoria_id, descricao, valor, status, vencimento, recebida_em, usuario_id) VALUES (?,?,?,?,?,?,?)',
          [$cat, $desc, $valor, $quitado ? 'recebida' : 'a_receber', $venc, $quitado ? date('Y-m-d') : null, $u['id']]);
    } else {
        q('INSERT INTO despesas (categoria_id, descricao, fornecedor, valor, status, vencimento, paga_em, usuario_id) VALUES (?,?,?,?,?,?,?,?)',
          [$cat, $desc, str_in('fornecedor', 120), $valor, $quitado ? 'paga' : 'a_pagar', $venc, $quitado ? date('Y-m-d') : null, $u['id']]);
    }
    $id = (int) db()->lastInsertId();
    audit($u['id'], "financeiro.$tipo.criado", $tipo, $id, null, ['descricao' => $desc, 'valor' => $valor]);
    out(['message' => 'Lançamento criado.', 'id' => $id], 201);
}

function admin_lancamento_status(array $p)
{
    $u = admin_user('financeiro.gerenciar');
    $id = (int) $p['id'];
    $acao = (string) in('acao', '');
    if ($p['tipo'] === 'receitas') {
        $map = ['quitar' => ["status='recebida', recebida_em=CURDATE()"], 'cancelar' => ["status='cancelada', recebida_em=NULL"]];
        if (!isset($map[$acao])) fail('Ação inválida.');
        $st = q("UPDATE receitas SET {$map[$acao][0]} WHERE id = ? AND status <> 'cancelada'", [$id]);
    } else {
        $map = ['quitar' => ["status='paga', paga_em=CURDATE()"], 'cancelar' => ["status='cancelada', paga_em=NULL"]];
        if (!isset($map[$acao])) fail('Ação inválida.');
        $st = q("UPDATE despesas SET {$map[$acao][0]} WHERE id = ? AND status <> 'cancelada'", [$id]);
    }
    if (!$st->rowCount()) fail('Lançamento não encontrado ou já cancelado.', 404);
    audit($u['id'], "financeiro.{$p['tipo']}.$acao", $p['tipo'], $id);
    out(['message' => 'Lançamento atualizado.']);
}

function admin_clientes()
{
    admin_user('clientes.ver');
    list($page, $limit, $offset) = page_params(20, 100);
    $where = ['u.excluido_em IS NULL']; $params = [];
    if ($b = qs('busca')) {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], mb_substr(trim((string) $b), 0, 60)) . '%';
        $where[] = '(u.nome LIKE ? OR u.email LIKE ? OR c.telefone LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    $from = 'FROM clientes c JOIN usuarios u ON u.id = c.usuario_id WHERE ' . implode(' AND ', $where);
    $total = (int) q_val("SELECT COUNT(*) $from", $params);
    $rows = q_all("SELECT c.id, u.nome, u.email, c.telefone, u.status, u.criado_em,
                          (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id AND p.status <> 'cancelado') AS pedidos,
                          (SELECT COALESCE(SUM(p.total), 0) FROM pedidos p WHERE p.cliente_id = c.id AND p.status <> 'cancelado') AS total_gasto
                   $from ORDER BY u.criado_em DESC, c.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset, $params);
    foreach ($rows as &$r) { $r['pedidos'] = (int) $r['pedidos']; $r['total_gasto'] = (float) $r['total_gasto']; }
    out(['clientes' => $rows, 'paginacao' => paginacao($page, $limit, $total)]);
}

const CONFIG_KEYS = [
    'loja_nome'          => 'Nome da empresa',
    'loja_whatsapp'      => 'WhatsApp para atendimento (somente números, com DDI. Ex.: 5585999999999)',
    'loja_email_contato' => 'E-mail de contato',
    'frete_fixo'         => 'Valor fixo de frete (0 = grátis)',
    'frete_gratis_acima' => 'Valor mínimo para frete grátis (0 = desativado)',
];

function admin_config_get()
{
    admin_user('configuracoes.editar');
    $vals = [];
    foreach (CONFIG_KEYS as $k => $desc) $vals[] = ['chave' => $k, 'descricao' => $desc, 'valor' => (string) setting($k, '')];
    out(['configuracoes' => $vals]);
}

function admin_config_save()
{
    $u = admin_user('configuracoes.editar');
    foreach (CONFIG_KEYS as $k => $desc) {
        if (in($k) === null) continue;
        $v = trim((string) in($k));
        if (in_array($k, ['frete_fixo', 'frete_gratis_acima'], true)) {
            $v = (string) max(0, (float) str_replace(',', '.', $v));
        } elseif ($k === 'loja_whatsapp') {
            $v = preg_replace('/\D/', '', $v);
        } elseif ($k === 'loja_email_contato' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            fail('E-mail de contato inválido.');
        }
        q('INSERT INTO configuracoes (chave, valor, descricao) VALUES (?,?,?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$k, mb_substr($v, 0, 500), $desc]);
    }
    audit($u['id'], 'configuracoes.atualizadas', 'configuracoes', null, null, body());
    out(['message' => 'Configurações salvas.']);
}
