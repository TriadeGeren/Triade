<?php
/** Entrega uma foto de referência de cliente — só para administradores (o _boot.php barra os demais). */
require __DIR__ . '/_boot.php';
require_once __DIR__ . '/../includes/personalizacao.php';

$linha = consultar('SELECT arquivos FROM personalizacoes WHERE id = ?', 'i', [(int)($_GET['id'] ?? 0)])[0] ?? null;
$arquivos = $linha ? (array)json_decode((string)$linha['arquivos'], true) : [];
$nome = $arquivos[(int)($_GET['i'] ?? 0)] ?? null;

if (!is_string($nome) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nome) || !is_file(pastaReferencias() . '/' . $nome)) {
    http_response_code(404);
    exit('Imagem não encontrada.');
}
$tipos = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
header('Content-Type: ' . $tipos[pathinfo($nome, PATHINFO_EXTENSION)]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
if (isset($_GET['baixar'])) header('Content-Disposition: attachment; filename="referencia-' . (int)$_GET['id'] . '-' . ((int)($_GET['i'] ?? 0) + 1) . '.' . pathinfo($nome, PATHINFO_EXTENSION) . '"');
readfile(pastaReferencias() . '/' . $nome);
