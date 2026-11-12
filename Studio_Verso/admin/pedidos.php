<?php
require __DIR__ . '/_boot.php';
require __DIR__ . '/_faturamento.php';   // dataValida()
require_once __DIR__ . '/../includes/mercadopago.php';
require_once __DIR__ . '/../includes/personalizacao.php';
garantirTabelasPersonalizacao($conn);

/* ---------- Ações (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($acao === 'atualizar') {
        $status = $_POST['status'] ?? '';
        $pag = $_POST['status_pagamento'] ?? '';
        if (isset(STATUS_PEDIDO[$status]) && isset(STATUS_PAGAMENTO[$pag])) {
            $stmt = $conn->prepare('UPDATE pedidos SET status = ?, status_pagamento = ? WHERE id = ?');
            $stmt->bind_param('ssi', $status, $pag, $id);
            $stmt->execute();
            flash('ok', "Pedido #$id atualizado.");
        } else {
            flash('erro', 'Status inválido.');
        }
        redirecionar('/admin/pedidos.php?id=' . $id);
    }
    if ($acao === 'sincronizar') {
        try {
            $novo = mpSincronizarPedido($conn, $id);
            flash('ok', "Pedido #$id: pagamento " . (STATUS_PAGAMENTO[$novo] ?? '—') . ' (conferido no Mercado Pago).');
        } catch (Throwable $e) {
            flash('erro', 'Não consegui consultar o Mercado Pago: ' . $e->getMessage());
        }
        redirecionar('/admin/pedidos.php?id=' . $id);
    }
    if ($acao === 'sincronizar_todos') {
        $pend = consultar("SELECT id FROM pedidos WHERE status_pagamento = 'pendente' AND mp_preference_id IS NOT NULL ORDER BY id DESC LIMIT 30");
        $mudou = 0;
        try {
            foreach ($pend as $o) {
                if (mpSincronizarPedido($conn, (int)$o['id']) !== 'pendente') $mudou++;
            }
            flash('ok', count($pend) . " pedido(s) pendente(s) conferido(s) no Mercado Pago; $mudou atualizado(s).");
        } catch (Throwable $e) {
            flash('erro', 'Não consegui consultar o Mercado Pago: ' . $e->getMessage());
        }
        redirecionar('/admin/pedidos.php');
    }
    if ($acao === 'excluir') {
        foreach (consultar('SELECT id, arquivos FROM personalizacoes WHERE pedido_id = ?', 'i', [$id]) as $pz) {
            apagarArquivosPersonalizacao($pz['arquivos']);
            $conn->query('DELETE FROM personalizacoes WHERE id = ' . (int)$pz['id']);
        }
        $stmt = $conn->prepare('DELETE FROM pedidos WHERE id = ?'); // os itens saem junto (ON DELETE CASCADE)
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('ok', "Pedido #$id excluído.");
        redirecionar('/admin/pedidos.php');
    }
    redirecionar('/admin/pedidos.php');
}

/* ---------- Detalhe de um pedido ---------- */
if (isset($_GET['id'])) {
    $pedido = consultar('SELECT * FROM pedidos WHERE id = ?', 'i', [(int)$_GET['id']])[0] ?? null;
    if (!$pedido) { flash('erro', 'Pedido não encontrado.'); redirecionar('/admin/pedidos.php'); }
    $itens = consultar('SELECT pi.*, pr.nome, pz.id AS pz_id, pz.escolhas, pz.descricao, pz.arquivos FROM pedido_itens pi JOIN produtos pr ON pr.id = pi.produto_id LEFT JOIN personalizacoes pz ON pz.id = pi.personalizacao_id WHERE pi.pedido_id = ?', 'i', [$pedido['id']]);
    $zap = preg_replace('/\D+/', '', (string)$pedido['whatsapp_cliente']);
    if ($zap && strlen($zap) <= 11) $zap = '55' . $zap;

    adminHeader('Pedido #' . (int)$pedido['id'], 'pedidos');
    ?>
    <p><a href="<?= BASE_PATH ?>/admin/pedidos.php" style="color:var(--roxo);font-weight:700">← voltar aos pedidos</a></p>
    <div class="grade-2">
      <div class="cartao">
        <h2>Cliente</h2>
        <dl class="detalhe-lista">
          <dt>Nome</dt><dd><?= e($pedido['nome_cliente']) ?></dd>
          <dt>E-mail</dt><dd><a href="mailto:<?= e($pedido['email_cliente']) ?>" style="color:var(--roxo)"><?= e($pedido['email_cliente']) ?></a></dd>
          <dt>WhatsApp</dt><dd><?= $zap ? '<a target="_blank" rel="noopener" style="color:var(--roxo)" href="https://wa.me/' . e($zap) . '">' . e($pedido['whatsapp_cliente']) . ' ↗</a>' : '—' ?></dd>
          <dt>Data</dt><dd><?= dataBr($pedido['criado_em']) ?></dd>
          <dt>Total</dt><dd><strong><?= brl($pedido['total']) ?></strong></dd>
          <dt>Mercado Pago</dt><dd><small>preferência: <?= e($pedido['mp_preference_id'] ?: '—') ?><br>pagamento: <?= e($pedido['mp_payment_id'] ?: '—') ?></small></dd>
        </dl>
      </div>
      <div class="cartao">
        <h2>Situação</h2>
        <form method="POST" class="form-coluna">
          <?= csrfField() ?><input type="hidden" name="acao" value="atualizar"><input type="hidden" name="id" value="<?= (int)$pedido['id'] ?>">
          <div class="campo"><label>Status do pedido</label>
            <select name="status"><?php foreach (STATUS_PEDIDO as $k => $r): ?><option value="<?= $k ?>" <?= $pedido['status'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
          <div class="campo"><label>Pagamento</label>
            <select name="status_pagamento"><?php foreach (STATUS_PAGAMENTO as $k => $r): ?><option value="<?= $k ?>" <?= $pedido['status_pagamento'] === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select>
            <small>Normalmente o Mercado Pago atualiza isso sozinho. Altere à mão só em casos como pagamento por fora (ex.: Pix direto).</small></div>
          <button class="btn btn-primario" type="submit">Salvar situação</button>
        </form>
        <form method="POST" style="margin-top:12px">
          <?= csrfField() ?><input type="hidden" name="acao" value="sincronizar"><input type="hidden" name="id" value="<?= (int)$pedido['id'] ?>">
          <button class="btn btn-claro btn-peq" type="submit">↻ Conferir pagamento no Mercado Pago</button>
        </form>
      </div>
    </div>
    <div class="cartao">
      <h2>Itens</h2>
      <div class="tabela-wrap"><table class="tabela">
        <tr><th>Produto</th><th class="num">Qtd</th><th class="num">Preço unit.</th><th class="num">Subtotal</th></tr>
        <?php foreach ($itens as $i): ?>
          <tr><td><?= e($i['nome']) ?><?= $i['pz_id'] ? ' ' . badge('personalizado', 'st-enviado') : '' ?></td><td class="num"><?= (int)$i['quantidade'] ?></td><td class="num"><?= brl($i['preco_unitario']) ?></td><td class="num"><?= brl($i['quantidade'] * $i['preco_unitario']) ?></td></tr>
          <?php if ($i['pz_id']): $esc = (array)json_decode((string)$i['escolhas'], true); $fotos = (array)json_decode((string)$i['arquivos'], true); ?>
          <tr><td colspan="4" style="background:var(--roxo-suave)">
            <strong>✨ Personalização</strong>
            <?php if ($esc): ?><ul style="margin:6px 0 10px;padding-left:20px">
              <?php foreach ($esc as $e1): ?><li><strong><?= e($e1['opcao']) ?>:</strong>
                <?= $e1['texto'] !== null ? e($e1['texto']) : e(implode(', ', array_map(fn($x) => $x['rotulo'], $e1['itens']))) ?>
                <?= (float)$e1['acrescimo'] > 0 ? '<small>(+' . brl($e1['acrescimo']) . ')</small>' : '' ?></li><?php endforeach; ?>
            </ul><?php endif; ?>
            <?php if (trim((string)$i['descricao']) !== ''): ?><div class="msg-corpo" style="margin:8px 0"><?= e($i['descricao']) ?></div><?php endif; ?>
            <?php if ($fotos): ?><div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
              <?php foreach ($fotos as $n => $f): ?>
                <a href="<?= BASE_PATH ?>/admin/referencia.php?id=<?= (int)$i['pz_id'] ?>&i=<?= $n ?>" target="_blank" rel="noopener" title="Abrir">
                  <img src="<?= BASE_PATH ?>/admin/referencia.php?id=<?= (int)$i['pz_id'] ?>&i=<?= $n ?>" alt="Referência <?= $n + 1 ?>" style="width:110px;height:110px;object-fit:cover;border-radius:12px;border:2px solid var(--linha)">
                </a>
                <a class="btn btn-claro btn-peq" style="align-self:flex-end" href="<?= BASE_PATH ?>/admin/referencia.php?id=<?= (int)$i['pz_id'] ?>&i=<?= $n ?>&baixar=1">⬇ <?= $n + 1 ?></a>
              <?php endforeach; ?>
            </div><?php endif; ?>
          </td></tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </table></div>
    </div>
    <form method="POST" onsubmit="return confirm('Excluir o pedido #<?= (int)$pedido['id'] ?> definitivamente? Ele sairá do faturamento.')">
      <?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$pedido['id'] ?>">
      <button class="btn btn-perigo btn-peq" type="submit">Excluir pedido</button>
    </form>
    <?php
    adminFooter();
    exit;
}

/* ---------- Lista com filtros ---------- */
$q = trim($_GET['q'] ?? '');
$status = isset(STATUS_PEDIDO[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$pag = isset(STATUS_PAGAMENTO[$_GET['pagamento'] ?? '']) ? $_GET['pagamento'] : '';
$de = dataValida($_GET['de'] ?? null);
$ate = dataValida($_GET['ate'] ?? null);

$w = []; $t = ''; $p = [];
if ($q !== '') {
    $w[] = '(nome_cliente LIKE ? OR email_cliente LIKE ? OR id = ?)';
    $t .= 'ssi'; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = (int)ltrim($q, '#');
}
if ($status) { $w[] = 'status = ?'; $t .= 's'; $p[] = $status; }
if ($pag)    { $w[] = 'status_pagamento = ?'; $t .= 's'; $p[] = $pag; }
if ($de)     { $w[] = 'criado_em >= ?'; $t .= 's'; $p[] = $de . ' 00:00:00'; }
if ($ate)    { $w[] = 'criado_em < DATE_ADD(?, INTERVAL 1 DAY)'; $t .= 's'; $p[] = $ate; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';

$total = (int)consultar("SELECT COUNT(*) AS n FROM pedidos $where", $t, $p)[0]['n'];
[$offset, $paginas, $paginaAtual] = paginar($total, 20);
$pedidos = consultar("SELECT * FROM pedidos $where ORDER BY criado_em DESC, id DESC LIMIT 20 OFFSET $offset", $t, $p);

adminHeader('Pedidos', 'pedidos');
?>
<form method="GET" class="cartao">
  <div class="form-grade">
    <div class="campo"><label>Buscar</label><input type="text" name="q" placeholder="nome, e-mail ou nº" value="<?= e($q) ?>"></div>
    <div class="campo"><label>Status</label><select name="status"><option value="">Todos</option><?php foreach (STATUS_PEDIDO as $k => $r): ?><option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label>Pagamento</label><select name="pagamento"><option value="">Todos</option><?php foreach (STATUS_PAGAMENTO as $k => $r): ?><option value="<?= $k ?>" <?= $pag === $k ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?></select></div>
    <div class="campo"><label>De</label><input type="date" name="de" value="<?= e($de ?? '') ?>"></div>
    <div class="campo"><label>Até</label><input type="date" name="ate" value="<?= e($ate ?? '') ?>"></div>
    <div class="acoes-linha"><button class="btn btn-primario btn-peq" type="submit">Filtrar</button><a class="btn btn-claro btn-peq" href="<?= BASE_PATH ?>/admin/pedidos.php">Limpar</a></div>
  </div>
</form>

<div class="cartao">
  <div class="barra">
    <h2 style="margin:0"><?= $total ?> pedido(s)</h2>
    <form method="POST"><?= csrfField() ?><input type="hidden" name="acao" value="sincronizar_todos">
      <button class="btn btn-claro btn-peq" type="submit">↻ Conferir pendentes no Mercado Pago</button></form>
  </div>
  <?php if (!$pedidos): ?><p class="vazio">Nenhum pedido encontrado.</p><?php else: ?>
  <div class="tabela-wrap"><table class="tabela">
    <tr><th>#</th><th>Data</th><th>Cliente</th><th class="num">Total</th><th>Pedido</th><th>Pagamento</th><th></th></tr>
    <?php foreach ($pedidos as $o): ?>
      <tr>
        <td><strong>#<?= (int)$o['id'] ?></strong></td>
        <td><?= dataBr($o['criado_em']) ?></td>
        <td><?= e($o['nome_cliente']) ?><br><small style="color:var(--tinta-suave)"><?= e($o['email_cliente']) ?></small></td>
        <td class="num"><?= brl($o['total']) ?></td>
        <td><?= badgeStatus($o['status']) ?></td>
        <td><?= badgePagamento($o['status_pagamento']) ?></td>
        <td><a class="btn btn-claro btn-peq" href="?id=<?= (int)$o['id'] ?>">Abrir</a></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?= paginacaoHtml($paginas, $paginaAtual) ?>
  <?php endif; ?>
</div>
<?php adminFooter(); ?>
