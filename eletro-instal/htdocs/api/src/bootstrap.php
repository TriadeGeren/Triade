<?php
/**
 * Núcleo: configuração, banco (PDO), respostas JSON, entrada, auditoria.
 * Compatível com PHP 7.4+.
 */

define('EI_API_DIR', dirname(__DIR__));
define('EI_ROOT', dirname(EI_API_DIR));      // pasta pública (htdocs)

function cfg($key, $default = null)
{
    static $c = null;
    if ($c === null) {
        $c = is_file(EI_API_DIR . '/config.php') ? (array) require EI_API_DIR . '/config.php' : [];
        $env = ['db_host' => 'EI_DB_HOST', 'db_name' => 'EI_DB_NAME', 'db_user' => 'EI_DB_USER', 'db_pass' => 'EI_DB_PASS'];
        foreach ($env as $k => $e) {
            if (getenv($e) !== false) $c[$k] = getenv($e);
        }
    }
    return array_key_exists($key, $c) ? $c[$key] : $default;
}

/* Fallbacks caso a hospedagem não tenha a extensão mbstring */
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) { $a = preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY) ?: []; return implode('', array_slice($a, $start, $len)); }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return count(preg_split('//u', (string) $s, -1, PREG_SPLIT_NO_EMPTY) ?: []); }
}

date_default_timezone_set((string) cfg('timezone', 'America/Fortaleza'));

/* ------------------------------------------------------------------ banco */

function db()
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 8,
    ]);
    $pdo->exec("SET time_zone = '" . date('P') . "'");
    return $pdo;
}

function q($sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function q_all($sql, array $params = [])  { return q($sql, $params)->fetchAll(); }
function q_one($sql, array $params = [])  { $r = q($sql, $params)->fetch(); return $r === false ? null : $r; }
function q_val($sql, array $params = [])  { $r = q($sql, $params)->fetchColumn(); return $r === false ? null : $r; }

/** Configurações da tabela `configuracoes` (cache por requisição). */
function setting($key, $default = null)
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (q_all('SELECT chave, valor FROM configuracoes') as $r) $all[$r['chave']] = $r['valor'];
        } catch (Throwable $e) { /* tabela ausente: usa defaults */ }
    }
    return (isset($all[$key]) && $all[$key] !== '') ? $all[$key] : $default;
}

/* ---------------------------------------------------------------- resposta */

function out($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function fail($msg, $code = 400, array $extra = [])
{
    out(array_merge(['error' => $msg], $extra), $code);
}

/* ----------------------------------------------------------------- entrada */

function body()
{
    static $b = null;
    if ($b !== null) return $b;
    $raw = file_get_contents('php://input');
    $j = $raw !== '' && $raw !== false ? json_decode($raw, true) : null;
    $b = is_array($j) ? $j : (is_array($_POST) ? $_POST : []);
    return $b;
}

function in($key, $default = null)
{
    $b = body();
    return array_key_exists($key, $b) ? $b[$key] : $default;
}

function str_in($key, $max = 255)
{
    $v = in($key);
    if ($v === null) return null;
    $v = trim((string) $v);
    return $v === '' ? null : mb_substr($v, 0, $max);
}

function qs($key, $default = null)
{
    return isset($_GET[$key]) && $_GET[$key] !== '' ? $_GET[$key] : $default;
}

function page_params($defLimit = 20, $maxLimit = 100)
{
    $page  = max(1, (int) qs('page', 1));
    $limit = min($maxLimit, max(1, (int) qs('limit', $defLimit)));
    return [$page, $limit, ($page - 1) * $limit];
}

function paginacao($page, $limit, $total)
{
    return ['page' => $page, 'limit' => $limit, 'total' => (int) $total, 'pages' => max(1, (int) ceil($total / $limit))];
}

function client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function ip_bin()
{
    $b = @inet_pton(client_ip());
    return $b === false ? "\0\0\0\0" : $b;
}

function money($v)  { return round((float) $v, 2); }

function slugify($text)
{
    $text = (string) $text;
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t === false || $t === '') $t = $text;
    } else {
        $t = $text;
    }
    // Reserva (e complemento) para acentos do português, caso o iconv falhe ou não exista
    $t = strtr($t, ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
                    'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
                    'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','É'=>'E','Ê'=>'E','Í'=>'I','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ç'=>'C','²'=>'2','³'=>'3','×'=>'x']);
    $t = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $t));
    return trim($t, '-') ?: 'item';
}

/** Converte "2026-05-01T10:00" (datetime-local) em "2026-05-01 10:00:00" */
function dt_in($v)
{
    if (!$v) return null;
    $ts = strtotime(str_replace('T', ' ', (string) $v));
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/* --------------------------------------------------------------- auditoria */

function audit($userId, $acao, $entidade = null, $entidadeId = null, $antes = null, $depois = null)
{
    try {
        q('INSERT INTO logs_auditoria (usuario_id, acao, entidade, entidade_id, dados_antes, dados_depois, ip, user_agent)
           VALUES (?,?,?,?,?,?,?,?)', [
            $userId, $acao, $entidade, $entidadeId,
            $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            $depois === null ? null : json_encode($depois, JSON_UNESCAPED_UNICODE),
            ip_bin(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) { /* auditoria nunca derruba a requisição */ }
}

function log_error($msg, $e = null)
{
    try {
        q('INSERT INTO logs_erros (nivel, mensagem, arquivo, linha, url) VALUES (?,?,?,?,?)', [
            'error', mb_substr($msg, 0, 500),
            $e ? mb_substr(basename($e->getFile()), 0, 255) : null,
            $e ? $e->getLine() : null,
            mb_substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
        ]);
    } catch (Throwable $x) { /* ignora */ }
}

/** Expressão SQL do preço realmente válido agora (respeita datas da promoção). */
function sql_preco($alias = 'p')
{
    return "CASE WHEN $alias.preco_promocional IS NOT NULL
                  AND ($alias.promocao_inicio IS NULL OR $alias.promocao_inicio <= NOW())
                  AND ($alias.promocao_fim IS NULL OR $alias.promocao_fim >= NOW())
            THEN $alias.preco_promocional ELSE $alias.preco END";
}
