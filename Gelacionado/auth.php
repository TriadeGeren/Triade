<?php
require_once __DIR__ . '/config.php';

function iniciar_sessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
              || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
        // cookie só vale dentro desta pasta (não interfere nos outros projetos do domínio)
        $pasta = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "/")), "/") . "/";
        ini_set("session.use_strict_mode", "1");
        ini_set("session.cookie_httponly", "1");
        ini_set("session.cookie_samesite", "Lax");
        session_name("GELSESS");
        session_set_cookie_params(0, $pasta, "", $https, true);
        session_start();
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}

function esta_logado(): bool {
    iniciar_sessao();
    if (empty($_SESSION['usuario_id'])) return false;
    if (time() - ($_SESSION['ultimo_acesso'] ?? 0) > SESSAO_EXPIRA_SEGUNDOS) {
        encerrar_sessao();
        return false;
    }
    $_SESSION['ultimo_acesso'] = time();
    return true;
}

function require_login(): void {
    // impede que o navegador guarde páginas protegidas em cache (botão "voltar" após sair)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    if (!esta_logado()) {
        header('Location: ' . PAGINA_LOGIN);
        exit;
    }
}

function encerrar_sessao(): void {
    iniciar_sessao();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], true);
    }
    session_destroy();
}

function csrf_token(): string {
    iniciar_sessao();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_valido(?string $t): bool {
    iniciar_sessao();
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
