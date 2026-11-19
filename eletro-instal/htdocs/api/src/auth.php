<?php
/** Autenticação (JWT) e autorização (RBAC). */

function request_token()
{
    // 1) Authorization: Bearer ...   2) reserva: X-Auth-Token (hospedagens que removem Authorization)
    $cands = [
        $_SERVER['HTTP_AUTHORIZATION'] ?? '',
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
    ];
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strtolower($k) === 'authorization') $cands[] = $v;
        }
    }
    foreach ($cands as $c) {
        if ($c && preg_match('/Bearer\s+(\S+)/i', $c, $m)) return $m[1];
    }
    $x = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
    return $x !== '' ? $x : null;
}

/**
 * Retorna o usuário logado.
 * $required = false  → retorna null se não houver login (rotas com login opcional)
 * $allowPwdChange    → permite usuário com "trocar senha" pendente (rotas de conta)
 */
function current_user($required = true, $allowPwdChange = false)
{
    static $cache = [];
    $token = request_token();
    $key = (string) $token;
    if (!isset($cache[$key])) {
        $u = null;
        if ($token && ($payload = jwt_decode($token)) && isset($payload['userId'])) {
            $u = q_one("SELECT u.id, u.papel_id, u.nome, u.email, u.status, u.trocar_senha, p.slug AS papel
                        FROM usuarios u JOIN papeis p ON p.id = u.papel_id
                        WHERE u.id = ? AND u.status = 'ativo' AND u.excluido_em IS NULL", [$payload['userId']]);
        }
        $cache[$key] = $u;
    }
    $u = $cache[$key];
    if (!$u) {
        if ($required) fail($token ? 'Sessão expirada. Faça login novamente.' : 'Faça login para continuar.', 401);
        return null;
    }
    if ($required && !$allowPwdChange && (int) $u['trocar_senha'] === 1) {
        fail('Você precisa definir uma nova senha antes de continuar.', 403, ['codigo' => 'TROCAR_SENHA']);
    }
    return $u;
}

function user_permissions($user)
{
    if ((int) $user['papel_id'] === 1) {
        return array_column(q_all('SELECT slug FROM permissoes ORDER BY slug'), 'slug');
    }
    return array_column(q_all('SELECT p.slug FROM papel_permissoes pp JOIN permissoes p ON p.id = pp.permissao_id
                               WHERE pp.papel_id = ? ORDER BY p.slug', [$user['papel_id']]), 'slug');
}

function require_perm($user, $slug)
{
    if ((int) $user['papel_id'] === 1) return;
    $ok = q_val('SELECT 1 FROM papel_permissoes pp JOIN permissoes p ON p.id = pp.permissao_id
                 WHERE pp.papel_id = ? AND p.slug = ?', [$user['papel_id'], $slug]);
    if (!$ok) fail('Você não tem permissão para esta ação.', 403);
}

/** Atalho para rotas administrativas. */
function admin_user($perm)
{
    $u = current_user(true);
    if (!in_array((int) $u['papel_id'], [1, 2], true)) fail('Acesso restrito à equipe.', 403);
    require_perm($u, $perm);
    return $u;
}

function cliente_id_of($userId)
{
    $id = q_val('SELECT id FROM clientes WHERE usuario_id = ?', [$userId]);
    return $id ? (int) $id : null;
}

function user_payload($u)
{
    return [
        'id'           => (int) $u['id'],
        'nome'         => $u['nome'],
        'email'        => $u['email'],
        'papel_id'     => (int) $u['papel_id'],
        'papel'        => $u['papel'] ?? null,
        'trocar_senha' => (bool) $u['trocar_senha'],
        'permissoes'   => user_permissions($u),
    ];
}

function valid_password($s)
{
    return strlen($s) >= 8 && preg_match('/[A-Za-z]/', $s) && preg_match('/\d/', $s);
}

/* ---------------------------------------------------------------- handlers */

function auth_login()
{
    $email = strtolower(trim((string) in('email', '')));
    $senha = (string) in('senha', '');
    if ($email === '' || $senha === '') fail('Informe e-mail e senha.');

    $max = max(1, (int) setting('login_max_tentativas', 5));
    $min = max(1, (int) setting('login_bloqueio_min', 15));

    // Bloqueio temporário por e-mail e por IP (só conta falhas após o último sucesso)
    $falhasEmail = (int) q_val("SELECT COUNT(*) FROM tentativas_login t
        WHERE t.email = ? AND t.sucesso = 0 AND t.criado_em > DATE_SUB(NOW(), INTERVAL $min MINUTE)
          AND t.criado_em > COALESCE((SELECT MAX(x.criado_em) FROM tentativas_login x WHERE x.email = ? AND x.sucesso = 1), '1970-01-01')",
        [$email, $email]);
    $falhasIp = (int) q_val("SELECT COUNT(*) FROM tentativas_login WHERE ip = ? AND sucesso = 0
        AND criado_em > DATE_SUB(NOW(), INTERVAL $min MINUTE)", [ip_bin()]);
    if ($falhasEmail >= $max || $falhasIp >= $max * 4) {
        fail("Muitas tentativas. Aguarde $min minutos e tente novamente.", 429);
    }

    $user = q_one("SELECT u.id, u.papel_id, u.nome, u.email, u.senha_hash, u.status, u.trocar_senha, p.slug AS papel
                   FROM usuarios u JOIN papeis p ON p.id = u.papel_id
                   WHERE u.email = ? AND u.excluido_em IS NULL", [$email]);

    // Sempre executa password_verify (evita revelar se o e-mail existe pelo tempo de resposta)
    $hash = $user ? $user['senha_hash'] : '$2y$12$abcdefghijklmnopqrstuuMq5Y1m8mC8VQb8s0kC2hQ0uZ0yq6B6.';
    $ok = password_verify($senha, $hash) && $user && $user['status'] === 'ativo';

    q('INSERT INTO tentativas_login (email, ip, sucesso) VALUES (?,?,?)', [mb_substr($email, 0, 160), ip_bin(), $ok ? 1 : 0]);
    if (!$ok) fail('E-mail ou senha incorretos.', 401);

    q('UPDATE usuarios SET ultimo_login_em = NOW(), ultimo_login_ip = ? WHERE id = ?', [ip_bin(), $user['id']]);
    if (password_needs_rehash($user['senha_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        q('UPDATE usuarios SET senha_hash = ? WHERE id = ?', [password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]), $user['id']]);
    }

    out(['token' => jwt_encode(['userId' => (int) $user['id'], 'papelId' => (int) $user['papel_id']]), 'user' => user_payload($user)]);
}

function auth_register()
{
    $nome  = str_in('nome', 120);
    $email = strtolower((string) str_in('email', 160));
    $senha = (string) in('senha', '');
    $tel   = preg_replace('/[^0-9+() -]/', '', (string) in('telefone', ''));

    if (!$nome || mb_strlen($nome) < 3) fail('Informe seu nome completo.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Informe um e-mail válido.');
    if (!valid_password($senha)) fail('A senha deve ter no mínimo 8 caracteres, com letras e números.');
    if (!in('aceite')) fail('É necessário aceitar os Termos de Uso e a Política de Privacidade.');

    if (q_val('SELECT id FROM usuarios WHERE email = ?', [$email])) fail('Este e-mail já está cadastrado.', 409);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        q('INSERT INTO usuarios (papel_id, nome, email, senha_hash, status, trocar_senha, email_verificado_em)
           VALUES (3, ?, ?, ?, ?, 0, NOW())', [$nome, $email, password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]), 'ativo']);
        $uid = (int) $pdo->lastInsertId();
        q('INSERT INTO clientes (usuario_id, tipo_pessoa, telefone, aceite_termos_em, aceite_privacidade_em, aceita_marketing)
           VALUES (?, ?, ?, NOW(), NOW(), ?)', [$uid, 'fisica', $tel ?: null, in('marketing') ? 1 : 0]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    $u = ['id' => $uid, 'papel_id' => 3, 'nome' => $nome, 'email' => $email, 'trocar_senha' => 0, 'papel' => 'cliente'];
    out(['token' => jwt_encode(['userId' => $uid, 'papelId' => 3]), 'user' => user_payload($u)], 201);
}

function auth_me()
{
    $u = current_user(true, true);
    out(['user' => user_payload($u)]);
}

function auth_change_password()
{
    $u = current_user(true, true);
    $atual = (string) in('senha_atual', '');
    $nova  = (string) in('nova_senha', '');
    if (!valid_password($nova)) fail('A nova senha deve ter no mínimo 8 caracteres, com letras e números.');
    $hash = q_val('SELECT senha_hash FROM usuarios WHERE id = ?', [$u['id']]);
    if (!password_verify($atual, $hash)) fail('A senha atual está incorreta.', 403);
    if (password_verify($nova, $hash)) fail('A nova senha precisa ser diferente da atual.');
    q('UPDATE usuarios SET senha_hash = ?, trocar_senha = 0 WHERE id = ?', [password_hash($nova, PASSWORD_BCRYPT, ['cost' => 12]), $u['id']]);
    audit($u['id'], 'usuario.senha_alterada', 'usuarios', $u['id']);
    $u['trocar_senha'] = 0;
    out(['message' => 'Senha alterada com sucesso.', 'user' => user_payload($u)]);
}
