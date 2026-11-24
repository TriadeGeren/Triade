<?php
/**
 * Configuração geral do Studio Verso.
 *
 * ESTE ARQUIVO NÃO GUARDA SENHAS. Os segredos (banco, chave Pix)
 * ficam num arquivo à parte — assim você pode atualizar o site à vontade sem
 * apagar suas credenciais, e nunca corre o risco de publicá-las por engano.
 *
 * Onde o site procura os segredos (usa o primeiro que existir):
 *   1) Variáveis de ambiente (Render, VPS...)
 *   2) Um nível ACIMA da pasta pública (ex.: /home/vol.../studioverso_segredos.php)
 *      -> o mais seguro: nenhum navegador consegue alcançar esse arquivo.
 *   3) segredos.php dentro da pasta do site (use se a opção 2 não for possível).
 * Modelo pronto: segredos.exemplo.php
 */

function segredo(string $chave, $padrao = '') {
    static $arquivo = null;
    if ($arquivo === null) {
        $arquivo = [];
        $candidatos = [
            dirname((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) . '/studioverso_segredos.php',
            __DIR__ . '/segredos.php',
        ];
        foreach ($candidatos as $c) {
            if (is_file($c)) { $arquivo = (array)(require $c); break; }
        }
    }
    $env = getenv($chave);
    if ($env !== false && $env !== '') return trim($env);
    $valor = $arquivo[$chave] ?? $padrao;
    // Espaço ou quebra de linha colados junto do token/senha quebram a autenticação sem dar pista.
    return is_string($valor) ? trim($valor) : $valor;
}

define('DB_HOST', segredo('DB_HOST', 'localhost'));
define('DB_NAME', segredo('DB_NAME'));
define('DB_USER', segredo('DB_USER'));
define('DB_PASS', segredo('DB_PASS'));
define('DB_PORT', (int)segredo('DB_PORT', 3306));
// TiDB Cloud e outros bancos gerenciados exigem conexão criptografada (DB_SSL = true).
define('DB_SSL', filter_var(segredo('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN));

/**
 * Pagamento por Pix (manual, sem API): o cliente paga pela chave da loja e envia o comprovante
 * pelo WhatsApp; você confirma em Admin > Pedidos. Veja includes/pix.php.
 *   PIX_CHAVE  = sua chave Pix (e-mail, CPF/CNPJ, celular no formato +5585999999999 ou chave aleatória)
 *   PIX_NOME   = nome que aparece para quem paga
 *   PIX_CIDADE = cidade do recebedor
 *   WHATSAPP_LOJA = número com DDI+DDD, só dígitos (ex.: 5585999999999)
 */
define('PIX_CHAVE', (string)segredo('PIX_CHAVE', ''));
define('PIX_NOME', (string)segredo('PIX_NOME', 'Studio Verso'));
define('PIX_CIDADE', (string)segredo('PIX_CIDADE', 'Fortaleza'));
define('WHATSAPP_LOJA', preg_replace('/\D+/', '', (string)segredo('WHATSAPP_LOJA', '5585996198403')));

/**
 * Pasta do site dentro do servidor (descoberta sozinha).
 * Raiz -> ''   |   htdocs/Studio_Verso -> '/Studio_Verso'
 */
if (!defined('BASE_PATH')) {
    $raizDoc = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $raizApp = str_replace('\\', '/', (string)realpath(__DIR__));
    define('BASE_PATH', ($raizDoc !== '' && str_starts_with($raizApp, $raizDoc)) ? substr($raizApp, strlen($raizDoc)) : '');
}

/* ---------- Fuso horário ---------- */
// PHP e MySQL no mesmo horário (Brasília/Fortaleza, UTC-3): sem isso o "faturamento de hoje"
// vira o dia às 21h e os horários dos pedidos aparecem trocados.
date_default_timezone_set('America/Fortaleza');

/* ---------- Endurecimento básico ---------- */
// Nunca mostrar erros técnicos ao visitante (eles podem revelar caminhos e dados). Ficam só no log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if ($https) header('Strict-Transport-Security: max-age=15552000');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if (DB_SSL) {
        $conn = mysqli_init();
        $conn->ssl_set(null, null, null, null, null);
        $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null, MYSQLI_CLIENT_SSL);
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    }
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '-03:00'");
} catch (mysqli_sql_exception $e) {
    error_log('Falha ao conectar no banco: ' . $e->getMessage());
    die('Não foi possível conectar ao banco de dados. Verifique os segredos (segredos.php).');
}

// Cookie de sessão protegido: não acessível por JavaScript, só viaja em HTTPS, não vai em links de outros sites.
// Nome próprio + caminho da pasta: assim este sistema NÃO compartilha login/sessão com outros
// sistemas hospedados no mesmo domínio (ex.: /Gelacionado, /Eletro_Install).
session_name('SV_SESSAO');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => BASE_PATH . '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

/** Helper: usuário logado atualmente (ou null) */
function usuarioLogado(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/** Helper: escapar saída HTML rapidamente */
function e(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
