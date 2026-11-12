<?php
require_once __DIR__ . '/config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
        $erro = 'Preencha nome, e-mail válido e senha com pelo menos 6 caracteres.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erro = 'Já existe uma conta com esse e-mail.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nome, $email, $hash);
            $stmt->execute();

            session_regenerate_id(true);
            $_SESSION['usuario'] = ['id' => $conn->insert_id, 'nome' => $nome, 'email' => $email, 'is_admin' => 0];
            header('Location: ' . BASE_PATH . '/index.php');
            exit;
        }
    }
}

$tituloPagina = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<section class="secao">
  <div class="container pagina-form">
    <div class="card">
      <h2>Criar conta</h2>
      <?php if ($erro): ?><div class="alerta alerta-erro"><?= e($erro) ?></div><?php endif; ?>
      <form method="POST">
        <div class="campo"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="campo"><label>E-mail</label><input type="email" name="email" required></div>
        <div class="campo"><label>Senha</label><input type="password" name="senha" minlength="6" required></div>
        <button type="submit" class="btn btn-primario" style="width:100%;">Cadastrar</button>
      </form>
      <p style="margin-top:14px;font-size:.9rem;">Já tem conta? <a href="<?= BASE_PATH ?>/login.php" style="color:var(--fio-petroleo)">Entrar</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
