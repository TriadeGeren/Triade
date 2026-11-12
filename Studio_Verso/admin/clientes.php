<?php
require __DIR__ . '/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $souEu = $id === (int)$adminAtual['id'];

    if ($acao === 'admin' && !$souEu) {
        $novo = (int)($_POST['valor'] ?? 0) ? 1 : 0;
        $stmt = $conn->prepare('UPDATE usuarios SET is_admin = ? WHERE id = ?');
        $stmt->bind_param('ii', $novo, $id);
        $stmt->execute();
        flash('ok', $novo ? 'Usuário agora é administrador.' : 'Permissão de administrador removida.');
    } elseif ($acao === 'senha') {
        $nova = (string)($_POST['nova_senha'] ?? '');
        if (strlen($nova) < 6) {
            flash('erro', 'A nova senha precisa ter pelo menos 6 caracteres.');
        } else {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            flash('ok', 'Senha redefinida.');
        }
    } elseif ($acao === 'excluir' && !$souEu) {
        // Mantém o histórico de pedidos: só desvincula o pedido da conta apagada.
        $stmt = $conn->prepare('UPDATE pedidos SET usuario_id = NULL WHERE usuario_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('ok', 'Conta excluída (os pedidos dela foram mantidos).');
    } else {
        flash('erro', 'Você não pode fazer isso com a sua própria conta.');
    }
    redirecionar('/admin/clientes.php');
}

$q = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? '';
$w = []; $t = ''; $p = [];
if ($q !== '') { $w[] = '(u.nome LIKE ? OR u.email LIKE ?)'; $t .= 'ss'; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($tipo === 'admin') $w[] = 'u.is_admin = 1';
if ($tipo === 'cliente') $w[] = 'u.is_admin = 0';
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$usuarios = consultar("SELECT u.*,
        (SELECT COUNT(*) FROM pedidos o WHERE o.usuario_id = u.id) AS pedidos,
        (SELECT COALESCE(SUM(o.total),0) FROM pedidos o WHERE o.usuario_id = u.id AND o.status_pagamento = 'aprovado') AS gasto
    FROM usuarios u $where ORDER BY u.criado_em DESC", $t, $p);

adminHeader('Clientes e usuários', 'clientes');
?>
<div class="cartao">
  <div class="barra">
    <h2 style="margin:0"><?= count($usuarios) ?> conta(s)</h2>
    <form method="GET" class="acoes-linha">
      <input type="text" name="q" placeholder="Nome ou e-mail" value="<?= e($q) ?>" style="padding:8px 12px;border:2px solid var(--linha);border-radius:12px;">
      <select name="tipo" style="padding:8px 12px;border:2px solid var(--linha);border-radius:12px;">
        <option value="">Todos</option><option value="cliente" <?= $tipo === 'cliente' ? 'selected' : '' ?>>Clientes</option><option value="admin" <?= $tipo === 'admin' ? 'selected' : '' ?>>Administradores</option>
      </select>
      <button class="btn btn-claro btn-peq" type="submit">Filtrar</button>
    </form>
  </div>
  <div class="tabela-wrap"><table class="tabela">
    <tr><th>Nome</th><th>E-mail</th><th>Tipo</th><th class="num">Pedidos</th><th class="num">Total pago</th><th>Desde</th><th>Ações</th></tr>
    <?php foreach ($usuarios as $u): $souEu = (int)$u['id'] === (int)$adminAtual['id']; ?>
      <tr>
        <td><?= e($u['nome']) ?><?= $souEu ? ' <small>(você)</small>' : '' ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= $u['is_admin'] ? badge('Administrador', 'st-enviado') : badge('Cliente', 'st-novo') ?></td>
        <td class="num"><?= (int)$u['pedidos'] ?></td>
        <td class="num"><?= brl($u['gasto']) ?></td>
        <td><?= dataBr($u['criado_em'], false) ?></td>
        <td><div class="acoes-linha">
          <?php if (!$souEu): ?>
          <form method="POST" onsubmit="return confirm('Confirmar a alteração de permissão?')">
            <?= csrfField() ?><input type="hidden" name="acao" value="admin"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="valor" value="<?= $u['is_admin'] ? 0 : 1 ?>">
            <button class="btn btn-claro btn-peq" type="submit"><?= $u['is_admin'] ? 'Remover admin' : 'Tornar admin' ?></button>
          </form>
          <?php endif; ?>
          <form method="POST" onsubmit="var s=prompt('Nova senha (mín. 6 caracteres):'); if(!s) return false; this.nova_senha.value=s; return true;">
            <?= csrfField() ?><input type="hidden" name="acao" value="senha"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="nova_senha" value="">
            <button class="btn btn-claro btn-peq" type="submit">Redefinir senha</button>
          </form>
          <?php if (!$souEu): ?>
          <form method="POST" onsubmit="return confirm('Excluir esta conta? Os pedidos dela serão mantidos.')">
            <?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-perigo btn-peq" type="submit">Excluir</button>
          </form>
          <?php endif; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php adminFooter(); ?>
