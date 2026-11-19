<?php
/** Tabela de rotas. A ORDEM importa: rotas estáticas antes das dinâmicas. */

$ROUTES = [
    // Sistema
    ['GET',    '/health',                        'health'],
    ['GET',    '/config',                        'config_publica'],

    // Autenticação
    ['POST',   '/auth/login',                    'auth_login'],
    ['POST',   '/auth/register',                 'auth_register'],
    ['GET',    '/auth/me',                       'auth_me'],
    ['POST',   '/auth/change-password',          'auth_change_password'],

    // Loja pública
    ['GET',    '/categorias',                    'categorias_list'],
    ['GET',    '/categorias/(?<slug>[a-z0-9-]+)','categoria_get'],
    ['GET',    '/marcas',                        'marcas_list'],
    ['GET',    '/produtos',                      'produtos_list'],
    ['GET',    '/produtos/destaques',            'produtos_destaques'],
    ['GET',    '/produtos/(?<slug>[a-z0-9-]+)',  'produto_get'],

    // Carrinho e checkout
    ['GET',    '/carrinho',                      'cart_get'],
    ['POST',   '/carrinho/itens',                'cart_add'],
    ['PATCH',  '/carrinho/itens/(?<id>\d+)',     'cart_update'],
    ['DELETE', '/carrinho/itens/(?<id>\d+)',     'cart_remove'],
    ['POST',   '/carrinho/cupom',                'cart_coupon_apply'],
    ['DELETE', '/carrinho/cupom',                'cart_coupon_remove'],
    ['POST',   '/checkout',                      'checkout_create'],

    // Serviços (rotas estáticas ANTES de /servicos/{slug})
    ['GET',    '/servicos/categorias',           'servicos_categorias'],
    ['POST',   '/servicos/solicitacoes',         'solicitacao_criar'],
    ['GET',    '/servicos/solicitacoes',         'solicitacoes_minhas'],
    ['GET',    '/servicos/solicitacoes/(?<id>\d+)', 'solicitacao_get'],
    ['GET',    '/servicos',                      'servicos_list'],
    ['GET',    '/servicos/(?<slug>[a-z0-9-]+)',  'servico_get'],

    // Área do cliente
    ['GET',    '/conta/pedidos',                 'conta_pedidos'],
    ['GET',    '/conta/pedidos/(?<numero>[A-Za-z0-9-]+)', 'conta_pedido'],
    ['GET',    '/conta/enderecos',               'conta_enderecos'],
    ['POST',   '/conta/enderecos',               'conta_endereco_criar'],

    // Admin — dashboard / pedidos / solicitações
    ['GET',    '/admin/dashboard',               'admin_dashboard'],
    ['GET',    '/admin/pedidos',                 'admin_pedidos'],
    ['GET',    '/admin/pedidos/(?<id>\d+)',      'admin_pedido_get'],
    ['PATCH',  '/admin/pedidos/(?<id>\d+)/status','admin_pedido_status'],
    ['GET',    '/admin/solicitacoes',            'admin_solicitacoes'],
    ['GET',    '/admin/solicitacoes/(?<id>\d+)', 'admin_solicitacao_get'],
    ['PATCH',  '/admin/solicitacoes/(?<id>\d+)', 'admin_solicitacao_update'],

    // Admin — catálogo
    ['GET',    '/admin/produtos',                'admin_produtos'],
    ['POST',   '/admin/produtos',                'admin_produto_create'],
    ['GET',    '/admin/produtos/(?<id>\d+)',     'admin_produto_get'],
    ['PUT',    '/admin/produtos/(?<id>\d+)',     'admin_produto_update'],
    ['DELETE', '/admin/produtos/(?<id>\d+)',     'admin_produto_delete'],
    ['POST',   '/admin/produtos/(?<id>\d+)/imagens', 'admin_produto_imagem_upload'],
    ['DELETE', '/admin/imagens/(?<id>\d+)',      'admin_imagem_delete'],
    ['POST',   '/admin/imagens/(?<id>\d+)/principal', 'admin_imagem_principal'],
    ['GET',    '/admin/categorias',              'admin_categorias'],
    ['POST',   '/admin/categorias',              'admin_categoria_save'],
    ['PUT',    '/admin/categorias/(?<id>\d+)',   'admin_categoria_save'],

    // Admin — estoque
    ['GET',    '/admin/estoque/baixo',           'admin_estoque_baixo'],
    ['GET',    '/admin/estoque/(?<id>\d+)/movimentacoes', 'admin_estoque_movs'],
    ['POST',   '/admin/estoque/(?<id>\d+)/ajuste','admin_estoque_ajuste'],

    // Admin — marketing e serviços
    ['GET',    '/admin/cupons',                  'admin_cupons'],
    ['POST',   '/admin/cupons',                  'admin_cupom_save'],
    ['PUT',    '/admin/cupons/(?<id>\d+)',       'admin_cupom_save'],
    ['GET',    '/admin/servicos',                'admin_servicos'],
    ['POST',   '/admin/servicos',                'admin_servico_save'],
    ['PUT',    '/admin/servicos/(?<id>\d+)',     'admin_servico_save'],
    ['DELETE', '/admin/servicos/(?<id>\d+)',     'admin_servico_delete'],

    // Admin — financeiro, clientes, configurações
    ['GET',    '/admin/financeiro',              'admin_financeiro'],
    ['POST',   '/admin/(?<tipo>receitas|despesas)', 'admin_lancamento_create'],
    ['PATCH',  '/admin/(?<tipo>receitas|despesas)/(?<id>\d+)', 'admin_lancamento_status'],
    ['GET',    '/admin/clientes',                'admin_clientes'],
    ['GET',    '/admin/configuracoes',           'admin_config_get'],
    ['PUT',    '/admin/configuracoes',           'admin_config_save'],
];

function request_path()
{
    if (isset($_GET['r']) && $_GET['r'] !== '') return '/' . trim((string) $_GET['r'], '/');   // reserva sem mod_rewrite
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php')), '/');
    if ($base !== '' && strpos($uri, $base) === 0) $uri = substr($uri, strlen($base));
    $uri = preg_replace('#^/index\.php#', '', $uri);
    return '/' . trim(rawurldecode($uri), '/');
}

function dispatch(array $routes)
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {          // PUT/PATCH/DELETE bloqueados por proxy
        $o = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        if (in_array($o, ['PUT', 'PATCH', 'DELETE'], true)) $method = $o;
    }
    $path = request_path();
    $pathOk = false;
    foreach ($routes as $r) {
        if (preg_match('#^' . $r[1] . '$#', $path, $m)) {
            $pathOk = true;
            if ($r[0] !== $method) continue;
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            call_user_func($r[2], $params);
            exit;
        }
    }
    if ($pathOk) fail('Método não permitido.', 405);
    fail('Rota não encontrada.', 404);
}
