<?php
/**
 * Recebe o formulário de personalização (via JavaScript), valida tudo no servidor,
 * guarda as fotos de referência e devolve o preço final + um código (token) para o carrinho.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/personalizacao.php';
garantirTabelasPersonalizacao($conn);

header('Content-Type: application/json; charset=utf-8');
function responder(array $dados, int $codigo = 200): never {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(['ok' => false, 'erro' => 'Método inválido.'], 405);

$csrf = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_pub']) || !is_string($csrf) || !hash_equals($_SESSION['csrf_pub'], $csrf)) {
    responder(['ok' => false, 'erro' => 'Sessão expirada. Recarregue a página e tente de novo.'], 400);
}

// Limite: 10 personalizações por hora por visitante (evita encher o espaço da hospedagem)
$agora = time();
$_SESSION['pers_hist'] = array_values(array_filter($_SESSION['pers_hist'] ?? [], fn($t) => $t > $agora - 3600));
if (count($_SESSION['pers_hist']) >= 10) {
    responder(['ok' => false, 'erro' => 'Muitas personalizações em pouco tempo. Tente novamente mais tarde.'], 429);
}

$produtoId = (int)($_POST['produto_id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM produtos WHERE id = ?');
$stmt->bind_param('i', $produtoId);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
if (!$produto) responder(['ok' => false, 'erro' => 'Produto não encontrado.'], 404);

$opcoes = opcoesDoProduto($conn, (int)$produto['id'], (int)$produto['categoria_id']);
if (!$opcoes && !$produto['sob_encomenda']) responder(['ok' => false, 'erro' => 'Este produto não tem personalização.'], 400);

$res = processarEscolhas($opcoes, $_POST);
if (isset($res['erro'])) responder(['ok' => false, 'erro' => $res['erro']], 422);

$descricao = trim((string)($_POST['descricao'] ?? ''));
if (mb_strlen($descricao) > 1000) responder(['ok' => false, 'erro' => 'A descrição passou de 1000 letras.'], 422);

/* ---------- Fotos de referência ---------- */
$salvos = [];
$falha = function (string $msg) use (&$salvos) {
    foreach ($salvos as $a) @unlink(pastaReferencias() . '/' . $a);
    responder(['ok' => false, 'erro' => $msg], 422);
};
$permitidos = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
if (!empty($_FILES['refs']['name'][0])) {
    $total = count($_FILES['refs']['name']);
    if ($total > 5) $falha('Envie no máximo 5 imagens de referência.');
    for ($i = 0; $i < $total; $i++) {
        if ($_FILES['refs']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($_FILES['refs']['error'][$i] !== UPLOAD_ERR_OK) $falha('Não consegui receber uma das imagens (ela pode ser grande demais).');
        if ($_FILES['refs']['size'][$i] > 5 * 1024 * 1024) $falha('Cada imagem pode ter no máximo 5 MB.');
        $info = @getimagesize($_FILES['refs']['tmp_name'][$i]);
        if (!$info || !isset($permitidos[$info[2]])) $falha('Envie apenas imagens JPG, PNG ou WEBP.');
        $nome = bin2hex(random_bytes(16)) . '.' . $permitidos[$info[2]];
        if (!move_uploaded_file($_FILES['refs']['tmp_name'][$i], pastaReferencias() . '/' . $nome)) {
            $falha('Não consegui salvar uma das imagens. Tente novamente.');
        }
        $salvos[] = $nome;
    }
}

$precoFinal = round((float)$produto['preco'] + $res['acrescimo'], 2);
$token = bin2hex(random_bytes(16));
$escolhasJson = json_encode($res['escolhas'], JSON_UNESCAPED_UNICODE);
$arquivosJson = json_encode($salvos);

$ins = $conn->prepare('INSERT INTO personalizacoes (token, produto_id, preco_final, escolhas, descricao, arquivos) VALUES (?,?,?,?,?,?)');
$ins->bind_param('sidsss', $token, $produtoId, $precoFinal, $escolhasJson, $descricao, $arquivosJson);
$ins->execute();
$_SESSION['pers_hist'][] = $agora;

// Faxina: personalizações antigas que nunca viraram pedido (7+ dias) saem junto com as fotos
if (random_int(1, 15) === 1) {
    $velhas = $conn->query("SELECT id, arquivos FROM personalizacoes WHERE pedido_id IS NULL AND criado_em < (NOW() - INTERVAL 7 DAY) LIMIT 50")->fetch_all(MYSQLI_ASSOC);
    foreach ($velhas as $v) {
        apagarArquivosPersonalizacao($v['arquivos']);
        $conn->query('DELETE FROM personalizacoes WHERE id = ' . (int)$v['id']);
    }
}

responder([
    'ok' => true,
    'token' => $token,
    'preco' => $precoFinal,
    'resumo' => resumoEscolhas($res['escolhas']) . ($salvos ? ($res['escolhas'] ? ' · ' : '') . count($salvos) . ' foto(s) de referência' : ''),
]);
