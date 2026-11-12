<?php
// Ajuda a achar o problema. Use: diagnostico.php?k=SUA_SETUP_KEY  — APAGUE depois de usar.
require __DIR__ . "/auth.php";
header("Content-Type: text/plain; charset=utf-8");
if (SETUP_KEY === "TROQUE-ESTA-CHAVE") exit("Defina a SETUP_KEY no config.php e abra: diagnostico.php?k=SUA_CHAVE");
if (!hash_equals(SETUP_KEY, (string)($_GET["k"] ?? ""))) { http_response_code(403); exit("Acesso negado. Use diagnostico.php?k=SUA_SETUP_KEY"); }
echo "PHP: " . PHP_VERSION . "\n";
echo "Pasta: " . __DIR__ . "\n";
echo "DB_PASS: " . (strpos(DB_PASS, "COLE_AQUI") === 0 ? "NAO PREENCHIDO" : "preenchido") . "\n";
try {
    $pdo = db();
    echo "Conexao MySQL: OK\n";
    $n = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    echo "Tabela usuarios: OK ($n usuario(s) cadastrado(s))\n";
} catch (Throwable $e) {
    echo "FALHA: " . preg_replace("/\S+@\S+/", "***", $e->getMessage()) . "\n";
}
foreach (["index.php","login.php","carrinho.php","servicos.php","style.css","script.js","logo.png"] as $f)
    echo str_pad($f, 18) . (file_exists(__DIR__ . "/$f") ? "ok" : "FALTANDO") . "\n";
