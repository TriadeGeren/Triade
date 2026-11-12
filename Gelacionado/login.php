<?php
require __DIR__ . '/auth.php';
iniciar_sessao();
header('Cache-Control: no-store, no-cache, must-revalidate');

if (esta_logado()) { header('Location: ' . PAGINA_INICIAL); exit; }

$erro = '';
const MAX_TENTATIVAS = 5;      // erros seguidos permitidos por usuário...
const BLOQUEIO_MINUTOS = 15;   // ...depois disso, bloqueia por 15 min

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = (string)($_POST['senha'] ?? '');

    try {
        if (!csrf_valido($_POST['csrf'] ?? null)) {
            $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
        } else {
            $pdo = db();
            $st = $pdo->prepare('SELECT id, usuario, senha_hash, tentativas_falhas,
                                        (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS bloqueado
                                 FROM usuarios WHERE usuario = ? LIMIT 1');
            $st->execute([$usuario]);
            $u = $st->fetch();

            if ($u && $u['bloqueado']) {
                $erro = 'Muitas tentativas. Aguarde ' . BLOQUEIO_MINUTOS . ' minutos e tente de novo.';
            } else {
                // hash falso mantém o tempo de resposta parecido quando o usuário não existe
                $hash = $u['senha_hash'] ?? password_hash('x', PASSWORD_DEFAULT);
                if (password_verify($senha, $hash) && $u) {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id']    = (int)$u['id'];
                    $_SESSION['usuario_nome']  = $u['usuario'];
                    $_SESSION['ultimo_acesso'] = time();
                    $novoHash = password_needs_rehash($hash, PASSWORD_DEFAULT) ? password_hash($senha, PASSWORD_DEFAULT) : $hash;
                    $pdo->prepare('UPDATE usuarios SET senha_hash = ?, tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = ?')
                        ->execute([$novoHash, $u['id']]);
                    header('Location: ' . PAGINA_INICIAL);
                    exit;
                }
                if ($u) {
                    $n = (int)$u['tentativas_falhas'] + 1;
                    if ($n >= MAX_TENTATIVAS) {
                        $pdo->prepare('UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NOW() + INTERVAL ' . BLOQUEIO_MINUTOS . ' MINUTE WHERE id = ?')->execute([$u['id']]);
                    } else {
                        $pdo->prepare('UPDATE usuarios SET tentativas_falhas = ? WHERE id = ?')->execute([$n, $u['id']]);
                    }
                }
                usleep(400000); // pequena pausa para dificultar tentativas em massa
                $erro = 'Usuário ou senha incorretos.';
            }
        }
    } catch (Throwable $e) {
        $erro = (strpos(DB_PASS, 'COLE_AQUI') === 0)
            ? 'Falta preencher a senha (DB_PASS) no config.php.'
            : 'Erro ao conectar ao banco de dados. Verifique o config.php (abra diagnostico.php).';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — Gelacionado</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="<?= PAGINA_LOGIN ?>" class="brand">
      <img src="logo.png" alt="Gelacionado" class="imagem_logo">
      <span>Gelacionado<small>CLIMATIZAÇÃO &amp; AUTOMAÇÃO</small></span>
    </a>
  </div>
</header>

<main>
  <div class="login-gate">
    <div class="login-box">
      <img src="logo.png" alt="Gelacionado" style="width:72px; height:72px; margin:0 auto 14px; display:block;">
      <h2 style="margin-bottom:6px;">Acesso ao site</h2>
      <p style="font-size:.9rem; margin-bottom:22px;">Entre com seu usuário e senha para continuar.</p>

      <form method="post" action="login.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <div class="form-row" style="text-align:left;">
          <label for="usuario">Usuário</label>
          <input type="text" id="usuario" name="usuario" required maxlength="50" autocomplete="username" autofocus value="<?= h($_POST['usuario'] ?? '') ?>">
        </div>

        <div class="form-row" style="text-align:left;">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="••••••••">
          <?php if ($erro): ?>
            <div class="field-error" style="display:block;"><?= h($erro) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Entrar</button>
      </form>

      <p style="font-size:.88rem; margin-top:18px;">Não tem conta? <a href="cadastro.php" style="color:var(--ice);">Criar conta</a></p>
    </div>
  </div>
</main>

</body>
</html>
