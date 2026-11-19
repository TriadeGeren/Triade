<?php
/** Serviços elétricos: catálogo público e solicitações do cliente. */

function shape_servico(array $s)
{
    foreach (['id', 'tempo_estimado_min'] as $k) if (isset($s[$k]) && $s[$k] !== null) $s[$k] = (int) $s[$k];
    if (isset($s['preco_inicial']) && $s['preco_inicial'] !== null) $s['preco_inicial'] = (float) $s['preco_inicial'];
    return $s;
}

function servicos_categorias()
{
    out(['categorias' => q_all('SELECT id, nome, slug, ordem FROM categorias_servicos WHERE ativo = 1 ORDER BY ordem, nome')]);
}

function servicos_list()
{
    $sql = 'SELECT s.id, s.nome, s.slug, s.descricao, s.imagem, s.tipo_preco, s.preco_inicial, s.tempo_estimado_min,
                   c.nome AS categoria_nome, c.slug AS categoria_slug
            FROM servicos s JOIN categorias_servicos c ON c.id = s.categoria_id
            WHERE s.disponivel = 1 AND s.excluido_em IS NULL';
    $params = [];
    if ($c = qs('categoria')) { $sql .= ' AND c.slug = ?'; $params[] = $c; }
    $sql .= ' ORDER BY c.ordem, s.nome';
    out(['servicos' => array_map('shape_servico', q_all($sql, $params))]);
}

function servico_get(array $p)
{
    $s = q_one('SELECT s.id, s.nome, s.slug, s.descricao, s.imagem, s.tipo_preco, s.preco_inicial, s.tempo_estimado_min,
                       c.nome AS categoria_nome, c.slug AS categoria_slug
                FROM servicos s JOIN categorias_servicos c ON c.id = s.categoria_id
                WHERE s.slug = ? AND s.disponivel = 1 AND s.excluido_em IS NULL', [$p['slug']]);
    if (!$s) fail('Serviço não encontrado.', 404);
    out(['servico' => shape_servico($s)]);
}

function solicitacao_criar()
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    if (!$cid) fail('Use uma conta de cliente para solicitar serviços.', 403);

    $sid  = (int) in('servico_id', 0);
    $desc = str_in('descricao_cliente', 2000);
    if (!$sid || !$desc || mb_strlen($desc) < 10) fail('Descreva o que você precisa (mínimo 10 caracteres).');
    if (!q_val('SELECT 1 FROM servicos WHERE id = ? AND disponivel = 1 AND excluido_em IS NULL', [$sid])) fail('Serviço indisponível.', 404);

    $data = in('data_preferida');
    if ($data && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data)) fail('Data inválida.');
    if ($data && $data < date('Y-m-d')) fail('A data preferida não pode ser no passado.');
    $per = in('periodo_preferido', 'qualquer');
    if (!in_array($per, ['manha', 'tarde', 'noite', 'qualquer'], true)) $per = 'qualquer';

    $endId = null;
    if (in('endereco') || in('endereco_id')) {
        $e = read_address($cid);
        $endId = address_save($cid, $e);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        q("INSERT INTO solicitacoes_servicos (protocolo, cliente_id, servico_id, endereco_id, descricao_cliente, data_preferida, periodo_preferido, status)
           VALUES (?,?,?,?,?,?,?, 'solicitado')", ['TMP-' . bin2hex(random_bytes(7)), $cid, $sid, $endId, $desc, $data ?: null, $per]);
        $id = (int) $pdo->lastInsertId();
        $protocolo = sprintf('SV-%s-%06d', date('Y'), $id);
        q('UPDATE solicitacoes_servicos SET protocolo = ? WHERE id = ?', [$protocolo, $id]);
        q("INSERT INTO solicitacoes_historico (solicitacao_id, status_anterior, status_novo, observacao, visivel_cliente, usuario_id)
           VALUES (?, NULL, 'solicitado', 'Solicitação criada pelo cliente', 1, ?)", [$id, $u['id']]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }

    out(['message' => 'Solicitação enviada.', 'solicitacao' => ['id' => $id, 'protocolo' => $protocolo, 'status' => 'solicitado'],
         'whatsapp' => preg_replace('/\D/', '', (string) setting('loja_whatsapp', ''))], 201);
}

function solicitacoes_minhas()
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    if (!$cid) out(['solicitacoes' => []]);
    $rows = q_all('SELECT ss.id, ss.protocolo, ss.status, ss.valor_orcado, ss.criado_em, s.nome AS servico_nome, s.slug AS servico_slug
                   FROM solicitacoes_servicos ss JOIN servicos s ON s.id = ss.servico_id
                   WHERE ss.cliente_id = ? ORDER BY ss.criado_em DESC, ss.id DESC', [$cid]);
    foreach ($rows as &$r) if ($r['valor_orcado'] !== null) $r['valor_orcado'] = (float) $r['valor_orcado'];
    out(['solicitacoes' => $rows]);
}

function solicitacao_get(array $p)
{
    $u = current_user(true);
    $cid = cliente_id_of($u['id']);
    $s = q_one('SELECT ss.id, ss.protocolo, ss.status, ss.descricao_cliente, ss.data_preferida, ss.periodo_preferido,
                       ss.valor_orcado, ss.prazo_validade_orcamento, ss.criado_em, s.nome AS servico_nome
                FROM solicitacoes_servicos ss JOIN servicos s ON s.id = ss.servico_id
                WHERE ss.id = ? AND ss.cliente_id = ?', [(int) $p['id'], $cid]);
    if (!$s) fail('Solicitação não encontrada.', 404);
    $h = q_all('SELECT status_anterior, status_novo, observacao, criado_em FROM solicitacoes_historico
                WHERE solicitacao_id = ? AND visivel_cliente = 1 ORDER BY id', [$s['id']]);
    out(['solicitacao' => $s, 'historico' => $h]);
}
