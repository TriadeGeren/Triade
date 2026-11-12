<?php
require_once __DIR__ . '/config.php';
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salva a mensagem no banco para aparecer em Admin > Mensagens.
    $conn->query("CREATE TABLE IF NOT EXISTS mensagens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(150) NOT NULL,
        tipo VARCHAR(60) DEFAULT NULL,
        mensagem TEXT NOT NULL,
        lida TINYINT(1) NOT NULL DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $nomeMsg = mb_substr(trim($_POST['nome'] ?? ''), 0, 120);
    $emailMsg = mb_substr(trim($_POST['email'] ?? ''), 0, 150);
    $tipoMsg = mb_substr(trim($_POST['tipo'] ?? ''), 0, 60);
    $textoMsg = trim($_POST['mensagem'] ?? '');
    if ($nomeMsg !== '' && filter_var($emailMsg, FILTER_VALIDATE_EMAIL) && $textoMsg !== '') {
        $stmtMsg = $conn->prepare('INSERT INTO mensagens (nome, email, tipo, mensagem) VALUES (?, ?, ?, ?)');
        $stmtMsg->bind_param('ssss', $nomeMsg, $emailMsg, $tipoMsg, $textoMsg);
        $stmtMsg->execute();
        $enviado = true;
    }
}

$tituloPagina = 'Contato';
require __DIR__ . '/includes/header.php';
?>
<section class="secao">
  <div class="container pagina-form">
    <div class="card">
      <h2>Solicitar orçamento</h2>
      <p style="color:var(--tinta-suave);font-size:.9rem;">Conte sua ideia — personagem, referência, tema — e retornamos com prazo e valor.</p>

      <?php if ($enviado): ?>
        <div class="alerta alerta-sucesso">Mensagem enviada! Responderemos em breve por e-mail.</div>
      <?php endif; ?>

      <form method="POST">
        <div class="campo"><label>Nome</label><input type="text" name="nome" required></div>
        <div class="campo"><label>E-mail</label><input type="email" name="email" required></div>
        <div class="campo">
          <label>O que você quer encomendar?</label>
          <select name="tipo">
            <option>Sketchbook personalizado</option>
            <option>Amigurumi sob medida</option>
            <option>Desenho / retrato</option>
            <option>Outro</option>
          </select>
        </div>
        <div class="campo"><label>Detalhes</label><textarea name="mensagem" rows="5" required></textarea></div>
        <button type="submit" class="btn btn-primario" style="width:100%;">Enviar</button>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
