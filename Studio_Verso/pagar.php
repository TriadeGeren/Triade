<?php
/**
 * Link antigo "pagar o pedido": agora o pagamento é por Pix e fica na tela do pedido.
 */
require_once __DIR__ . '/config.php';
header('Location: ' . BASE_PATH . '/pedido_retorno.php?pedido=' . (int)($_GET['pedido'] ?? 0));
exit;
