<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/mercadopago.php';
require_once __DIR__ . '/includes/personalizacao.php';
garantirTabelasPersonalizacao($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$itensCarrinho = json_decode($_POST['itens_json'] ?? '[]', true);

if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_array($itensCarrinho) || count($itensCarrinho) === 0) {
    die('Dados do pedido inválidos. <a href="' . BASE_PATH . '/finalizar.php">Voltar</a>');
}

/**
 * SEGURANÇA: do navegador só aceitamos "qual produto" e "quantas unidades".
 * Nome e PREÇO vêm sempre do banco — assim ninguém consegue pagar menos mexendo no carrinho.
 */
$itens = [];
$total = 0.0;
$tokensUsados = [];
$stmtProd = $conn->prepare('SELECT id, nome, preco, estoque, sob_encomenda FROM produtos WHERE id = ?');
$stmtPers = $conn->prepare('SELECT id, produto_id, preco_final FROM personalizacoes WHERE token = ? AND pedido_id IS NULL');
foreach ($itensCarrinho as $item) {
    $produtoId = (int)($item['id'] ?? 0);
    $qtd = (int)($item['qtd'] ?? 0);
    $token = is_string($item['pers'] ?? null) ? $item['pers'] : null;
    if ($produtoId <= 0 || $qtd < 1 || $qtd > 50) {
        die('Carrinho inválido. <a href="' . BASE_PATH . '/finalizar.php">Voltar</a>');
    }
    $stmtProd->bind_param('i', $produtoId);
    $stmtProd->execute();
    $prod = $stmtProd->get_result()->fetch_assoc();
    if (!$prod) {
        die('Um dos produtos do carrinho não existe mais. <a href="' . BASE_PATH . '/index.php">Voltar à loja</a>');
    }

    $persId = null;
    $preco = (float)$prod['preco'];
    $nomeItem = $prod['nome'];
    if ($token !== null) {
        // Item personalizado: o preço é o que o SERVIDOR calculou e guardou (nunca o do navegador)
        if (!preg_match('/^[a-f0-9]{32}$/', $token) || isset($tokensUsados[$token])) {
            die('Personalização inválida. <a href="' . BASE_PATH . '/finalizar.php">Voltar</a>');
        }
        $stmtPers->bind_param('s', $token);
        $stmtPers->execute();
        $pers = $stmtPers->get_result()->fetch_assoc();
        if (!$pers || (int)$pers['produto_id'] !== $produtoId) {
            die('Uma personalização do carrinho expirou ou já foi usada. Refaça a personalização. <a href="' . BASE_PATH . '/produto.php?id=' . $produtoId . '">Voltar ao produto</a>');
        }
        $tokensUsados[$token] = true;
        $persId = (int)$pers['id'];
        $preco = (float)$pers['preco_final'];
        $nomeItem = $prod['nome'] . ' (personalizado)';
        $qtd = 1;
    }

    if (!(int)$prod['sob_encomenda'] && $qtd > (int)$prod['estoque']) {
        die('Estoque insuficiente para "' . e($prod['nome']) . '". <a href="' . BASE_PATH . '/finalizar.php">Voltar</a>');
    }
    $itens[] = ['id' => $produtoId, 'nome' => $nomeItem, 'preco' => $preco, 'qtd' => $qtd, 'pers_id' => $persId];
    $total += $preco * $qtd;
}
$total = round($total, 2);

$usuario = usuarioLogado();
$usuarioId = $usuario['id'] ?? null;

$conn->begin_transaction();
try {
    // 1) Salva o pedido no banco como "aguardando pagamento"
    $stmt = $conn->prepare(
        "INSERT INTO pedidos (usuario_id, nome_cliente, email_cliente, whatsapp_cliente, total, status, status_pagamento)
         VALUES (?, ?, ?, ?, ?, 'novo', 'pendente')"
    );
    $stmt->bind_param('isssd', $usuarioId, $nome, $email, $whatsapp, $total);
    $stmt->execute();
    $pedidoId = $conn->insert_id;

    $stmtItem = $conn->prepare(
        "INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, personalizacao_id) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($itens as $item) {
        $produtoId = (int)$item['id'];
        $qtd = (int)$item['qtd'];
        $preco = (float)$item['preco'];
        $persId = $item['pers_id'];
        $stmtItem->bind_param('iiidi', $pedidoId, $produtoId, $qtd, $preco, $persId);
        $stmtItem->execute();
        if ($persId) {
            $conn->query('UPDATE personalizacoes SET pedido_id = ' . (int)$pedidoId . ' WHERE id = ' . (int)$persId . ' AND pedido_id IS NULL');
        }
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    die('Não foi possível registrar o pedido. Tente novamente. <a href="' . BASE_PATH . '/finalizar.php">Voltar</a>');
}

// 2) Cria a cobrança no Mercado Pago e redireciona o cliente pro checkout
try {
    $preferencia = mpCriarPreferencia($pedidoId, $itens, ['nome' => $nome, 'email' => $email]);

    // Guarda o id da preferência pra referência futura
    $stmtUp = $conn->prepare("UPDATE pedidos SET mp_preference_id = ? WHERE id = ?");
    $stmtUp->bind_param('si', $preferencia['id'], $pedidoId);
    $stmtUp->execute();

    // Use 'init_point' em produção. Enquanto estiver testando com credenciais
    // de TESTE, o Mercado Pago só libera o 'sandbox_init_point'.
    $urlCheckout = $preferencia['init_point'] ?? $preferencia['sandbox_init_point'];

    header('Location: ' . $urlCheckout);
    exit;
} catch (Throwable $e) {
    // Se o Mercado Pago falhar, o pedido já está salvo — mostramos um aviso
    // e deixamos o cliente falar com você pelo WhatsApp como plano B.
    $tituloPagina = 'Pedido registrado';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="secao">
      <div class="container pagina-form">
        <div class="card" style="text-align:center;">
          <h2>Pedido #<?= (int)$pedidoId ?> registrado!</h2>
          <div class="alerta alerta-erro">
            Não conseguimos abrir o pagamento online agora. Chame a gente no WhatsApp que finalizamos por lá.
          </div>
          <a href="https://wa.me/5585996198403?text=Ol%C3%A1!%20Fiz%20o%20pedido%20%23<?= (int)$pedidoId ?>%20no%20site." class="btn btn-primario">Falar no WhatsApp</a>
        </div>
      </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}
