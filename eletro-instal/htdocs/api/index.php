<?php
/**
 * Eletro Instal — API REST (front controller)
 * Todas as rotas passam por aqui (ver .htaccess).
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');

require __DIR__ . '/src/bootstrap.php';

ini_set('display_errors', '0');
error_reporting(cfg('debug') ? E_ALL : (E_ERROR | E_PARSE));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(204); exit; }

foreach (['jwt', 'auth', 'store', 'cart', 'checkout', 'account', 'services', 'admin_ops', 'admin_catalog', 'admin_fin', 'routes'] as $m) {
    require __DIR__ . "/src/$m.php";
}

try {
    dispatch($ROUTES);
} catch (PDOException $e) {
    $conn = in_array((int) $e->getCode(), [1044, 1045, 1049, 2002, 2003, 2006], true) || stripos($e->getMessage(), 'connection') !== false;
    if (!$conn) log_error('PDO: ' . $e->getMessage(), $e);
    fail($conn ? 'Não foi possível conectar ao banco de dados. Confira os dados em api/config.php.' : 'Erro ao acessar o banco de dados.',
         $conn ? 503 : 500, cfg('debug') ? ['detalhe' => $e->getMessage()] : []);
} catch (Throwable $e) {
    log_error(get_class($e) . ': ' . $e->getMessage(), $e);
    fail('Erro interno do servidor.', 500, cfg('debug') ? ['detalhe' => $e->getMessage(), 'arquivo' => basename($e->getFile()) . ':' . $e->getLine()] : []);
}
