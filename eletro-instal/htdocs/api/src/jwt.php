<?php
/** JWT HS256 mínimo, sem dependências. */

function jwt_secret()
{
    $s = (string) cfg('jwt_secret', '');
    if (strlen($s) >= 32) return $s;

    $file = EI_API_DIR . '/storage/jwt.key';
    if (is_file($file)) {
        $s = trim((string) file_get_contents($file));
        if (strlen($s) >= 32) return $s;
    }
    $s = bin2hex(random_bytes(32));
    if (!is_dir(dirname($file))) @mkdir(dirname($file), 0755, true);
    if (@file_put_contents($file, $s, LOCK_EX) === false) {
        fail('Não foi possível gerar a chave de segurança. Defina "jwt_secret" em api/config.php.', 500);
    }
    return $s;
}

function b64u($d)    { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function b64u_d($d)  { return base64_decode(strtr($d, '-_', '+/')); }

function jwt_encode(array $payload, $ttl = 604800)
{
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;
    $h = b64u(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $p = b64u(json_encode($payload));
    return "$h.$p." . b64u(hash_hmac('sha256', "$h.$p", jwt_secret(), true));
}

function jwt_decode($token)
{
    $parts = explode('.', (string) $token);
    if (count($parts) !== 3) return null;
    list($h, $p, $s) = $parts;
    if (!hash_equals(b64u(hash_hmac('sha256', "$h.$p", jwt_secret(), true)), $s)) return null;
    $data = json_decode(b64u_d($p), true);
    if (!is_array($data) || ($data['exp'] ?? 0) < time()) return null;
    return $data;
}
