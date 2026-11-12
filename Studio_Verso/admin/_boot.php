<?php
/**
 * Núcleo do painel administrativo.
 * Todo arquivo dentro de /admin faz: require __DIR__ . '/_boot.php';
 * Aqui ficam: proteção de acesso, CSRF, mensagens flash, formatadores e o layout.
 */
require_once __DIR__ . '/../config.php';

/* ---------- 1) Proteção: só administrador entra ---------- */
$adminAtual = usuarioLogado();
if (!$adminAtual) {
    header('Location: ' . BASE_PATH . '/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
    exit;
}
// Confere no banco (e não só na sessão) pra que quem perdeu o acesso seja barrado na hora.
$stmtAdm = $conn->prepare('SELECT id, nome, email, is_admin FROM usuarios WHERE id = ?');
$stmtAdm->bind_param('i', $adminAtual['id']);
$stmtAdm->execute();
$adminBanco = $stmtAdm->get_result()->fetch_assoc();
if (!$adminBanco || !(int)$adminBanco['is_admin']) {
    http_response_code(403);
    die('Acesso restrito ao administrador. <a href="' . BASE_PATH . '/login.php">Entrar</a>');
}
$adminAtual = $adminBanco;

/* ---------- 2) Garante tabelas extras do painel (mensagens de contato) ---------- */
$conn->query("CREATE TABLE IF NOT EXISTS mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    tipo VARCHAR(60) DEFAULT NULL,
    mensagem TEXT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ---------- 3) CSRF (impede que outro site dispare ações no seu painel) ---------- */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}
/** Chame no começo de qualquer tratamento de POST. */
function csrfExigir(): void {
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(400);
        die('Sessão expirada ou requisição inválida. Volte e tente de novo.');
    }
}

/* ---------- 4) Mensagens "flash" (aparecem uma vez após redirecionar) ---------- */
function flash(string $tipo, string $texto): void {
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $texto];
}
function redirecionar(string $url): never {
    if (str_starts_with($url, '/')) $url = BASE_PATH . $url;   // funciona também em subpasta
    header('Location: ' . $url);
    exit;
}

/* ---------- 5) Formatadores ---------- */
function brl($valor): string {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}
function dataBr(?string $data, bool $hora = true): string {
    if (!$data) return '—';
    $t = strtotime($data);
    return $t ? date($hora ? 'd/m/Y H:i' : 'd/m/Y', $t) : '—';
}

const STATUS_PEDIDO = [
    'novo' => 'Novo',
    'em_producao' => 'Em produção',
    'enviado' => 'Enviado',
    'concluido' => 'Concluído',
    'cancelado' => 'Cancelado',
];
const STATUS_PAGAMENTO = [
    'pendente' => 'Pendente',
    'aprovado' => 'Aprovado',
    'recusado' => 'Recusado',
    'estornado' => 'Estornado',
];

function badge(string $texto, string $classe): string {
    return '<span class="badge badge-' . e($classe) . '">' . e($texto) . '</span>';
}
function badgeStatus(string $s): string {
    return badge(STATUS_PEDIDO[$s] ?? $s, 'st-' . $s);
}
function badgePagamento(string $s): string {
    return badge(STATUS_PAGAMENTO[$s] ?? $s, 'pg-' . $s);
}

/** Converte "1.234,50" ou "99,90" ou "99.90" em número. */
function lerPreco(string $txt): float {
    $txt = trim($txt);
    if (str_contains($txt, ',')) {
        $txt = str_replace('.', '', $txt);   // ponto = separador de milhar
        $txt = str_replace(',', '.', $txt);  // vírgula = decimal
    }
    return is_numeric($txt) ? round((float)$txt, 2) : -1.0;
}

/** Gera um "slug" (ex.: "Desenhos por encomenda" -> "desenhos-por-encomenda"). */
function slugify(string $texto): string {
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $t = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $t), '-'));
    return $t !== '' ? $t : 'categoria';
}

/** Executa uma consulta com parâmetros e devolve todas as linhas. */
function consultar(string $sql, string $tipos = '', array $params = []): array {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ---------- 6) Layout (menu lateral + topo) ---------- */
function adminHeader(string $titulo, string $ativo = ''): void {
    global $conn, $adminAtual;
    $naoLidas = (int)$conn->query('SELECT COUNT(*) FROM mensagens WHERE lida = 0')->fetch_row()[0];
    $pendentes = (int)$conn->query("SELECT COUNT(*) FROM pedidos WHERE status = 'novo'")->fetch_row()[0];

    $menu = [
        'dashboard'  => [BASE_PATH . '/admin/index.php', '🏠', 'Visão geral', 0],
        'faturamento'=> [BASE_PATH . '/admin/faturamento.php', '📈', 'Faturamento', 0],
        'pedidos'    => [BASE_PATH . '/admin/pedidos.php', '🧾', 'Pedidos', $pendentes],
        'produtos'   => [BASE_PATH . '/admin/produtos.php', '📓', 'Produtos', 0],
        'categorias' => [BASE_PATH . '/admin/categorias.php', '🏷️', 'Categorias', 0],
        'personalizacao' => [BASE_PATH . '/admin/personalizacao.php', '✨', 'Personalização', 0],
        'clientes'   => [BASE_PATH . '/admin/clientes.php', '👥', 'Clientes e usuários', 0],
        'mensagens'  => [BASE_PATH . '/admin/mensagens.php', '💌', 'Mensagens', $naoLidas],
        'conta'      => [BASE_PATH . '/admin/conta.php', '🔑', 'Minha conta', 0],
    ];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?> — Admin Studio Verso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_PATH ?>/admin/admin.css">
<link rel="icon" href="<?= BASE_PATH ?>/assets/img/LogoStudioVersoOficial.jpeg">
<script>(function(){try{var t=localStorage.getItem('sv_tema');if(!t&&window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)t='escuro';if(t==='escuro')document.documentElement.setAttribute('data-tema','escuro');}catch(e){}})();</script>
<script src="<?= BASE_PATH ?>/assets/js/tema.js" defer></script>
</head>
<body class="admin">
<aside class="adm-lateral" id="admLateral">
  <a href="<?= BASE_PATH ?>/admin/index.php" class="adm-logo">
    <img src="<?= BASE_PATH ?>/assets/img/LogoStudioVersoOficial.jpeg" alt="">
    <span>Studio <b>Verso</b><small>painel administrativo</small></span>
  </a>
  <nav>
    <?php foreach ($menu as $chave => [$url, $icone, $rotulo, $contador]): ?>
      <a href="<?= $url ?>" class="<?= $ativo === $chave ? 'ativo' : '' ?>">
        <span class="ico"><?= $icone ?></span><?= e($rotulo) ?>
        <?php if ($contador > 0): ?><em class="contador"><?= $contador ?></em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="adm-lateral-baixo">
    <a href="<?= BASE_PATH ?>/index.php" target="_blank" rel="noopener">↗ Ver a loja</a>
    <a href="<?= BASE_PATH ?>/logout.php">⎋ Sair (<?= e($adminAtual['nome']) ?>)</a>
  </div>
</aside>
<div class="adm-conteudo">
  <header class="adm-topo">
    <button type="button" class="adm-menu-btn" onclick="document.getElementById('admLateral').classList.toggle('aberto')" aria-label="Abrir menu">☰</button>
    <h1><?= e($titulo) ?></h1>
    <button type="button" class="btn-tema" data-alternar-tema title="Mudar para o modo escuro">🌙</button>
  </header>
  <main class="adm-main">
    <?php
    foreach ($_SESSION['flash'] ?? [] as $f) {
        echo '<div class="alerta alerta-' . ($f['tipo'] === 'erro' ? 'erro' : 'sucesso') . '">' . e($f['texto']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function adminFooter(): void {
    ?>
  </main>
</div>
</body>
</html>
    <?php
}

/** Paginação simples: devolve [offset, totalPaginas, paginaAtual]. */
function paginar(int $total, int $porPagina = 20): array {
    $pagina = max(1, (int)($_GET['p'] ?? 1));
    $paginas = max(1, (int)ceil($total / $porPagina));
    $pagina = min($pagina, $paginas);
    return [($pagina - 1) * $porPagina, $paginas, $pagina];
}
function paginacaoHtml(int $paginas, int $pagina): string {
    if ($paginas <= 1) return '';
    $html = '<div class="paginacao">';
    $q = $_GET;
    for ($i = 1; $i <= $paginas; $i++) {
        $q['p'] = $i;
        $html .= '<a class="' . ($i === $pagina ? 'ativo' : '') . '" href="?' . e(http_build_query($q)) . '">' . $i . '</a>';
    }
    return $html . '</div>';
}
