<?php
/**
 * ELETRO INSTAL — Configuração da API (ÚNICO arquivo que você precisa editar)
 *
 * Na InfinityFree: painel (Client Area) → "MySQL Databases".
 * Copie os 4 dados que aparecem lá (host, nome, usuário, senha).
 */
return [
    // Host do MySQL: NÃO é "localhost". Ex.: sql311.infinityfree.com
    'db_host' => 'sqlXXX.infinityfree.com',

    // Nome do banco, com o prefixo da conta. Ex.: if0_41234567_loja
    'db_name' => 'if0_XXXXXXXX_loja',

    // Usuário do MySQL. Ex.: if0_41234567
    'db_user' => 'if0_XXXXXXXX',

    // Senha da sua conta de hospedagem (vPanel password)
    'db_pass' => 'SUA_SENHA_AQUI',

    // Deixe vazio: o sistema gera uma chave segura sozinho (api/storage/jwt.key).
    'jwt_secret' => '',

    // true só para diagnosticar erro 500; volte para false depois.
    'debug' => false,

    'timezone' => 'America/Fortaleza',
];
