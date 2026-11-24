<?php
/**
 * Funções de pedido que não dependem de nenhum meio de pagamento.
 */

/**
 * Garante a coluna que controla a baixa de estoque (cria sozinha se faltar).
 * Pedidos antigos ficam marcados como já tratados.
 */
function pedidosGarantirColunas(mysqli $conn): void {
    static $feito = false;
    if ($feito) return;
    $feito = true;
    try {
        $tem = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos' AND COLUMN_NAME = 'estoque_baixado'")->fetch_row()[0];
        if (!(int)$tem) {
            $conn->query('ALTER TABLE pedidos ADD COLUMN estoque_baixado TINYINT(1) NOT NULL DEFAULT 0');
            $conn->query('UPDATE pedidos SET estoque_baixado = 1');
        }
    } catch (Throwable $e) {
        error_log('Não consegui ajustar a tabela pedidos: ' . $e->getMessage());
    }
}

/**
 * Desconta do estoque os produtos de um pedido PAGO (produtos sob encomenda não têm estoque).
 * Só desconta uma vez por pedido (a coluna `estoque_baixado` controla isso).
 */
function baixarEstoquePedido(mysqli $conn, int $pedidoId): void {
    pedidosGarantirColunas($conn);
    $marca = $conn->prepare('UPDATE pedidos SET estoque_baixado = 1 WHERE id = ? AND estoque_baixado = 0');
    $marca->bind_param('i', $pedidoId);
    $marca->execute();
    if ($marca->affected_rows !== 1) return;   // já descontado antes (ou pedido inexistente)

    $conn->query(
        'UPDATE produtos pr
         JOIN (SELECT produto_id, SUM(quantidade) AS q FROM pedido_itens WHERE pedido_id = ' . (int)$pedidoId . ' GROUP BY produto_id) x
           ON x.produto_id = pr.id
         SET pr.estoque = CASE WHEN pr.estoque > x.q THEN pr.estoque - x.q ELSE 0 END
         WHERE pr.sob_encomenda = 0'
    );
}
