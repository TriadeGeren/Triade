<?php
require __DIR__ . "/auth.php";
iniciar_sessao();
header("Cache-Control: no-store, no-cache, must-revalidate");

if (esta_logado()) { header("Location: " . PAGINA_INICIAL); exit; }

// Para fechar o cadastro no futuro, adicione no config.php:  define("CADASTRO_ABERTO", false);
$aberto = defined("CADASTRO_ABERTO") ? CADASTRO_ABERTO : true;

$erro = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario    = trim($_POST["usuario"] ?? "");
    $senha      = (string)($_POST["senha"] ?? "");
    $confirmar  = (string)($_POST["confirmar"] ?? "");
    $_SESSION["cadastros"] = ($_SESSION["cadastros"] ?? 0) + 1;

    if (!$aberto)                                            $erro = "O cadastro está fechado no momento.";
    elseif (!csrf_valido($_POST["csrf"] ?? null))            $erro = "Sessão expirada. Recarregue a página e tente novamente.";
    elseif ($_SESSION["cadastros"] > 10)                     $erro = "Muitas tentativas. Tente novamente mais tarde.";
    elseif (!preg_match("/^[A-Za-z0-9_.-]{3,50}$/", $usuario)) $erro = "Usuário: 3 a 50 caracteres (letras, números, _ . -).";
    elseif (strlen($senha) < 8)                              $erro = "A senha precisa ter pelo menos 8 caracteres.";
    elseif (strlen($senha) > 72)                             $erro = "A senha pode ter no máximo 72 caracteres.";
    elseif (!hash_equals($senha, $confirmar))                $erro = "As senhas não conferem.";
    else {
        try {
            $pdo = db();
            $pdo->prepare("INSERT INTO usuarios (usuario, senha_hash) VALUES (?, ?)")
                ->execute([$usuario, password_hash($senha, PASSWORD_DEFAULT)]);
            // já entra logado após criar a conta
            session_regenerate_id(true);
            $_SESSION["usuario_id"]    = (int)$pdo->lastInsertId();
            $_SESSION["usuario_nome"]  = $usuario;
            $_SESSION["ultimo_acesso"] = time();
            $_SESSION["cadastros"]     = 0;
            header("Location: " . PAGINA_INICIAL);
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $erro = "Esse usuário já existe. Escolha outro.";
            } else {
                $erro = (strpos(DB_PASS, "COLE_AQUI") === 0)
                    ? "Falta preencher a senha (DB_PASS) no config.php."
                    : "Erro ao conectar ao banco de dados. Verifique o config.php (abra diagnostico.php).";
            }
        } catch (Throwable $e) {
            $erro = "Erro inesperado. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar conta — Gelacionado</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container">
    <a href="login.php" class="brand">
      <img src="logo.png" alt="Gelacionado" class="imagem_logo">
      <span>Gelacionado<small>CLIMATIZAÇÃO &amp; AUTOMAÇÃO</small></span>
    </a>
  </div>
</header>

<main>
  <div class="login-gate">
    <div class="login-box">
      <img src="logo.png" alt="Gelacionado" style="width:72px; height:72px; margin:0 auto 14px; display:block;">
      <h2 style="margin-bottom:6px;">Criar conta</h2>
      <p style="font-size:.9rem; margin-bottom:22px;">Escolha um usuário e uma senha para acessar o site.</p>

      <form method="post" action="cadastro.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <div class="form-row" style="text-align:left;">
          <label for="usuario">Usuário</label>
          <input type="text" id="usuario" name="usuario" required minlength="3" maxlength="50" autocomplete="username" autofocus value="<?= h($_POST["usuario"] ?? "") ?>">
        </div>

        <div class="form-row" style="text-align:left;">
          <label for="senha">Senha (mín. 8 caracteres)</label>
          <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password" placeholder="••••••••">
        </div>

        <div class="form-row" style="text-align:left;">
          <label for="confirmar">Confirmar senha</label>
          <input type="password" id="confirmar" name="confirmar" required minlength="8" autocomplete="new-password" placeholder="••••••••">
          <?php if ($erro): ?>
            <div class="field-error" style="display:block;"><?= h($erro) ?></div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Criar conta</button>
      </form>

      <p style="font-size:.88rem; margin-top:18px;">Já tem conta? <a href="login.php" style="color:var(--ice);">Entrar</a></p>
    </div>
  </div>
</main>

</body>
</html>
