<?php
require_once __DIR__ . '/config.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // Freio contra tentativa de adivinhar senha: 8 erros em 15 min por IP bloqueia por um tempo.
    $conn->query("CREATE TABLE IF NOT EXISTS login_tentativas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (ip, criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("DELETE FROM login_tentativas WHERE criado_em < (NOW() - INTERVAL 1 DAY)");
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '0', 0, 45);
    $stmtT = $conn->prepare("SELECT COUNT(*) FROM login_tentativas WHERE ip = ? AND criado_em > (NOW() - INTERVAL 15 MINUTE)");
    $stmtT->bind_param('s', $ip);
    $stmtT->execute();
    $bloqueado = (int)$stmtT->get_result()->fetch_row()[0] >= 8;

    $stmt = $conn->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();

    if ($bloqueado) {
        $erro = 'Muitas tentativas. Aguarde 15 minutos e tente de novo.';
    } elseif ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        session_regenerate_id(true);   // novo id de sessão após o login (evita roubo de sessão)
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'is_admin' => (int)$usuario['is_admin'],
        ];
        // Se veio do painel (ou é admin), volta pra área de administração
        $next = $_GET['next'] ?? '';
        if ($usuario['is_admin'] && str_starts_with($next, BASE_PATH . '/admin/') && !str_contains($next, '//')) {
            header('Location: ' . $next);
        } elseif ($usuario['is_admin']) {
            header('Location: ' . BASE_PATH . '/admin/index.php');
        } else {
            header('Location: ' . BASE_PATH . '/index.php');
        }
        exit;
    }
    if (!$bloqueado) {
        $stmtF = $conn->prepare('INSERT INTO login_tentativas (ip) VALUES (?)');
        $stmtF->bind_param('s', $ip);
        $stmtF->execute();
    }
    $erro = $bloqueado ? $erro : 'E-mail ou senha incorretos.';
}

$tituloPagina = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<section class="secao">
  <div class="container pagina-form">
    <div class="card">
      <h2>Entrar na sua conta</h2>
      <?php if ($erro): ?><div class="alerta alerta-erro"><?= e($erro) ?></div><?php endif; ?>
      <form method="POST">
        <div class="campo"><label>E-mail</label><input type="email" name="email" required></div>
        <div class="campo"><label>Senha</label><input type="password" name="senha" required></div>
        <button type="submit" class="btn btn-primario" style="width:100%;">Entrar</button>
      </form>
      <p style="margin-top:14px;font-size:.9rem;">Ainda não tem conta? <a href="<?= BASE_PATH ?>/cadastro.php" style="color:var(--fio-petroleo)">Cadastre-se</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
