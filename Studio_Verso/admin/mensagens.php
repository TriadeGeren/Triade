<?php
require __DIR__ . '/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($acao === 'lida' || $acao === 'nao_lida') {
        $v = $acao === 'lida' ? 1 : 0;
        $stmt = $conn->prepare('UPDATE mensagens SET lida = ? WHERE id = ?');
        $stmt->bind_param('ii', $v, $id);
        $stmt->execute();
        redirecionar('/admin/mensagens.php' . ($v ? '' : '?ver=' . $id));
    }
    if ($acao === 'excluir') {
        $stmt = $conn->prepare('DELETE FROM mensagens WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('ok', 'Mensagem excluída.');
    }
    redirecionar('/admin/mensagens.php');
}

// Abrir uma mensagem marca como lida
$aberta = null;
if (isset($_GET['ver'])) {
    $aberta = consultar('SELECT * FROM mensagens WHERE id = ?', 'i', [(int)$_GET['ver']])[0] ?? null;
    if ($aberta && !$aberta['lida'] && !isset($_GET['manter'])) {
        $stmt = $conn->prepare('UPDATE mensagens SET lida = 1 WHERE id = ?');
        $stmt->bind_param('i', $aberta['id']);
        $stmt->execute();
        $aberta['lida'] = 1;
    }
}
$msgs = consultar('SELECT * FROM mensagens ORDER BY criado_em DESC, id DESC LIMIT 200');

adminHeader('Mensagens e orçamentos', 'mensagens');
?>
<?php if ($aberta): ?>
<div class="cartao">
  <p><a href="<?= BASE_PATH ?>/admin/mensagens.php" style="color:var(--roxo);font-weight:700">← voltar</a></p>
  <h2><?= e($aberta['tipo'] ?: 'Mensagem') ?> — <?= e($aberta['nome']) ?></h2>
  <p style="color:var(--tinta-suave)"><?= e($aberta['email']) ?> · <?= dataBr($aberta['criado_em']) ?></p>
  <div class="msg-corpo"><?= e($aberta['mensagem']) ?></div>
  <div class="acoes-linha" style="margin-top:14px">
    <a class="btn btn-primario btn-peq" href="mailto:<?= e($aberta['email']) ?>?subject=<?= rawurlencode('Re: seu pedido de orçamento — Studio Verso') ?>">Responder por e-mail</a>
    <form method="POST"><?= csrfField() ?><input type="hidden" name="acao" value="nao_lida"><input type="hidden" name="id" value="<?= (int)$aberta['id'] ?>"><button class="btn btn-claro btn-peq">Marcar como não lida</button></form>
    <form method="POST" onsubmit="return confirm('Excluir esta mensagem?')"><?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$aberta['id'] ?>"><button class="btn btn-perigo btn-peq">Excluir</button></form>
  </div>
</div>
<?php else: ?>
<div class="cartao">
  <h2><?= count($msgs) ?> mensagem(ns)</h2>
  <?php if (!$msgs): ?><p class="vazio">Nenhuma mensagem recebida ainda. Os pedidos de orçamento do formulário de contato aparecem aqui.</p><?php else: ?>
  <div class="tabela-wrap"><table class="tabela">
    <tr><th>Data</th><th>De</th><th>Assunto</th><th>Prévia</th><th></th></tr>
    <?php foreach ($msgs as $m): ?>
      <tr class="<?= $m['lida'] ? '' : 'nao-lida' ?>">
        <td><?= dataBr($m['criado_em']) ?></td>
        <td><?= e($m['nome']) ?><br><small><?= e($m['email']) ?></small></td>
        <td><?= e($m['tipo'] ?? '') ?></td>
        <td><?= e(mb_strimwidth($m['mensagem'], 0, 70, '…')) ?></td>
        <td><a class="btn btn-claro btn-peq" href="?ver=<?= (int)$m['id'] ?>">Abrir</a></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php adminFooter(); ?>
