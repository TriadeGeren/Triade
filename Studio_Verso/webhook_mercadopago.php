<?php
/**
 * =========================================================
 * Webhook do Mercado Pago
 * =========================================================
 * O Mercado Pago chama essa URL SOZINHO (servidor a servidor) toda vez
 * que o status de um pagamento muda. É AQUI, e não na tela de retorno do
 * navegador, que você deve confirmar de verdade se o pagamento caiu.
 *
 * Configure essa URL no seu painel do Mercado Pago:
 * https://SEU-DOMINIO/webhook_mercadopago.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/mercadopago.php';

// O Mercado Pago pode mandar o id do pagamento tanto no corpo (POST JSON)
// quanto na query string (?data.id=...&type=payment), dependendo da versão.
$corpo = json_decode(file_get_contents('php://input'), true) ?? [];

$tipo = $corpo['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '';
$paymentId = $corpo['data']['id'] ?? $_GET['data_id'] ?? $_GET['id'] ?? null;

// Só nos interessa notificação de pagamento
if ($tipo !== 'payment' || !$paymentId) {
    http_response_code(200); // sempre responda 200, senão o MP fica reenviando
    exit;
}

try {
    $pagamento = mpBuscarPagamento((string)$paymentId);
    $pedidoId = (int)($pagamento['external_reference'] ?? 0);

    // Em vez de confiar no que chegou, consultamos o Mercado Pago de novo (com valor conferido)
    if ($pedidoId > 0) {
        mpSincronizarPedido($conn, $pedidoId);
    }

    http_response_code(200);
    echo 'ok';
} catch (Throwable $e) {
    // Loga o erro só nos seus logs — nunca exponha detalhes ao Mercado Pago
    error_log('Erro no webhook Mercado Pago: ' . $e->getMessage());
    http_response_code(200); // ainda assim respondemos 200 pra evitar reenvio infinito
}
