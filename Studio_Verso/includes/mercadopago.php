<?php
/**
 * =========================================================
 * Studio Verso — Integração com Mercado Pago (Checkout Pro)
 * =========================================================
 * Usamos cURL puro (sem SDK/Composer) pra funcionar em qualquer
 * hospedagem PHP, mesmo as mais simples.
 *
 * Documentação oficial da API:
 * https://www.mercadopago.com.br/developers/pt/reference
 */

/**
 * Faz uma requisição HTTP para a API do Mercado Pago.
 */
function mpRequisicao(string $metodo, string $caminho, ?array $corpo = null): array {
    $ch = curl_init((getenv('MP_API_BASE') ?: 'https://api.mercadopago.com') . $caminho);

    $headers = [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    if ($corpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo));
    }

    $resposta   = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erroCurl   = curl_error($ch);
    curl_close($ch);

    if ($erroCurl) {
        throw new RuntimeException('Erro de conexão com o Mercado Pago: ' . $erroCurl);
    }

    $dados = json_decode($resposta, true) ?? [];
    return ['status' => $statusCode, 'dados' => $dados];
}

/**
 * Cria uma "preference" (ordem de pagamento) no Mercado Pago para um pedido
 * e devolve a URL de checkout para redirecionar o cliente.
 *
 * @param int   $pedidoId  ID do pedido já salvo na tabela `pedidos`
 * @param array $itens     Itens do carrinho: [['nome'=>..,'preco'=>..,'qtd'=>..], ...]
 * @param array $comprador Dados do cliente: ['nome'=>..,'email'=>..]
 */
function mpCriarPreferencia(int $pedidoId, array $itens, array $comprador): array {
    $itensMp = array_map(function ($item) {
        return [
            'title'       => (string)$item['nome'],
            'quantity'    => (int)$item['qtd'],
            'unit_price'  => (float)$item['preco'],
            'currency_id' => 'BRL',
        ];
    }, $itens);

    $corpo = [
        'items' => $itensMp,
        'payer' => [
            'name'  => $comprador['nome'] ?? '',
            'email' => $comprador['email'] ?? '',
        ],
        // external_reference é o "elo" entre o pedido do seu banco e o pagamento do Mercado Pago
        'external_reference' => (string)$pedidoId,
        'back_urls' => [
            'success' => URL_SITE . '/pedido_retorno.php?pedido=' . $pedidoId,
            'pending' => URL_SITE . '/pedido_retorno.php?pedido=' . $pedidoId,
            'failure' => URL_SITE . '/pedido_retorno.php?pedido=' . $pedidoId,
        ],
        'auto_return' => 'approved',
        'statement_descriptor' => 'STUDIOVERSO',
        // Meios de pagamento: nada é excluído, então o cliente vê TUDO que a sua conta aceita
        // (Pix, cartão de crédito e débito, boleto, saldo Mercado Pago...).
        // Só limitamos o número máximo de parcelas no cartão (padrão 6; mude no segredos.php com MP_PARCELAS_MAX).
        'payment_methods' => [
            'installments' => max(1, min(12, (int)segredo('MP_PARCELAS_MAX', 6))),
        ],
    ];
    // O Mercado Pago chama essa URL sozinho quando o status muda (só em hospedagem que aceita webhook)
    if (MP_USAR_WEBHOOK) {
        $corpo['notification_url'] = URL_SITE . '/webhook_mercadopago.php';
    }

    $resp = mpRequisicao('POST', '/checkout/preferences', $corpo);

    if ($resp['status'] >= 300) {
        throw new RuntimeException('Não foi possível criar o pagamento no Mercado Pago: ' . json_encode($resp['dados']));
    }

    return $resp['dados']; // contém 'id', 'init_point', 'sandbox_init_point', etc.
}

/**
 * Busca no Mercado Pago os detalhes de um pagamento pelo ID.
 * Usado pelo webhook para confirmar o status real (nunca confie só no que
 * vem na URL de retorno do navegador).
 */
function mpBuscarPagamento(string $paymentId): array {
    $resp = mpRequisicao('GET', '/v1/payments/' . urlencode($paymentId));
    if ($resp['status'] >= 300) {
        throw new RuntimeException('Não foi possível consultar o pagamento no Mercado Pago.');
    }
    return $resp['dados'];
}


/** Traduz o status do Mercado Pago para o status usado no nosso banco. */
function mpStatusInterno(string $statusMp): string {
    return match ($statusMp) {
        'approved' => 'aprovado',
        'rejected', 'cancelled' => 'recusado',
        'refunded', 'charged_back' => 'estornado',
        default => 'pendente',   // pending, in_process, authorized...
    };
}

/**
 * Confere no Mercado Pago o pagamento de UM pedido e atualiza o banco.
 * Funciona sem webhook: é a nossa "ligação" para perguntar "o cliente pagou?".
 * Devolve o status interno atual do pedido (ou null se não achou o pedido).
 */
function mpSincronizarPedido(mysqli $conn, int $pedidoId): ?string {
    $stmt = $conn->prepare('SELECT total, status_pagamento FROM pedidos WHERE id = ?');
    $stmt->bind_param('i', $pedidoId);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    if (!$pedido) return null;

    $resp = mpRequisicao('GET', '/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . $pedidoId);
    if ($resp['status'] >= 300) {
        throw new RuntimeException('Mercado Pago não respondeu (verifique o Access Token).');
    }
    $pagamentos = $resp['dados']['results'] ?? [];
    if (!$pagamentos) return $pedido['status_pagamento'];   // cliente ainda não tentou pagar

    // Se algum pagamento foi aprovado, ele vale; senão vale o mais recente.
    $escolhido = $pagamentos[0];
    foreach ($pagamentos as $pg) {
        if (($pg['status'] ?? '') === 'approved') { $escolhido = $pg; break; }
    }
    $novo = mpStatusInterno((string)($escolhido['status'] ?? 'pending'));

    // Segurança: só aprova se o valor pago cobre o total do pedido.
    if ($novo === 'aprovado' && (float)($escolhido['transaction_amount'] ?? 0) + 0.01 < (float)$pedido['total']) {
        error_log("Pedido $pedidoId: valor pago diferente do total, não aprovado.");
        $novo = 'pendente';
    }

    $paymentId = (string)($escolhido['id'] ?? '');
    if ($novo !== $pedido['status_pagamento']) {
        $up = $conn->prepare('UPDATE pedidos SET status_pagamento = ?, mp_payment_id = ? WHERE id = ?');
        $up->bind_param('ssi', $novo, $paymentId, $pedidoId);
        $up->execute();
    }
    return $novo;
}
