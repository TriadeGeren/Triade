<?php
require __DIR__ . '/_boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfExigir();
    $atual = (string)($_POST['senha_atual'] ?? '');
    $nova = (string)($_POST['nova_senha'] ?? '');
    $conf = (string)($_POST['confirmar'] ?? '');

    $hash = consultar('SELECT senha_hash FROM usuarios WHERE id = ?', 'i', [(int)$adminAtual['id']])[0]['senha_hash'] ?? '';
    if (!password_verify($atual, $hash)) {
        flash('erro', 'A senha atual está incorreta.');
    } elseif (strlen($nova) < 8) {
        flash('erro', 'A nova senha precisa ter pelo menos 8 caracteres.');
    } elseif ($nova !== $conf) {
        flash('erro', 'A confirmação não bate com a nova senha.');
    } else {
        $novoHash = password_hash($nova, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $novoHash, $adminAtual['id']);
        $stmt->execute();
        flash('ok', 'Senha alterada com sucesso!');
    }
    redirecionar('/admin/conta.php');
}

$aindaPadrao = password_verify('admin123', consultar('SELECT senha_hash FROM usuarios WHERE id = ?', 'i', [(int)$adminAtual['id']])[0]['senha_hash'] ?? '');

adminHeader('Minha conta', 'conta');
?>
<?php if ($aindaPadrao): ?>
  <div class="alerta alerta-erro">⚠ Você ainda está usando a senha padrão (<code>admin123</code>) que vem no schema do projeto. Troque agora — qualquer pessoa que veja o código conhece essa senha.</div>
<?php endif; ?>
<div class="cartao form-coluna">
  <h2>Alterar minha senha</h2>
  <p style="color:var(--tinta-suave);font-size:.9rem"><?= e($adminAtual['nome']) ?> · <?= e($adminAtual['email']) ?></p>
  <form method="POST">
    <?= csrfField() ?>
    <div class="campo"><label>Senha atual</label><input type="password" name="senha_atual" required autocomplete="current-password"></div>
    <div class="campo"><label>Nova senha <small>(mín. 8 caracteres)</small></label><input type="password" name="nova_senha" required minlength="8" autocomplete="new-password"></div>
    <div class="campo"><label>Confirmar nova senha</label><input type="password" name="confirmar" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primario" type="submit">Salvar nova senha</button>
  </form>
</div>
<?php adminFooter(); ?>
