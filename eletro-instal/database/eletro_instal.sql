-- =====================================================================
-- ELETRO INSTAL — Esquema do banco de dados  (versão InfinityFree)
-- MariaDB 10.x / MySQL 5.7+ | InnoDB | utf8mb4
-- NÃO cria o banco: crie-o no painel da InfinityFree, abra o phpMyAdmin,
-- SELECIONE o banco na barra lateral e use a aba "Importar".
-- Importe UMA única vez, em banco vazio.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. SEGURANÇA, USUÁRIOS E RBAC
-- =====================================================================

CREATE TABLE papeis (
  id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(30)  NOT NULL,
  nome        VARCHAR(60)  NOT NULL,
  descricao   VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_papeis_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissoes (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(60)  NOT NULL,          -- ex.: produtos.editar
  modulo      VARCHAR(40)  NOT NULL,
  descricao   VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_permissoes_slug (slug),
  KEY idx_permissoes_modulo (modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE papel_permissoes (
  papel_id      TINYINT UNSIGNED  NOT NULL,
  permissao_id  SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (papel_id, permissao_id),
  CONSTRAINT fk_pp_papel     FOREIGN KEY (papel_id)     REFERENCES papeis (id)     ON DELETE CASCADE,
  CONSTRAINT fk_pp_permissao FOREIGN KEY (permissao_id) REFERENCES permissoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  papel_id              TINYINT UNSIGNED NOT NULL,
  nome                  VARCHAR(120) NOT NULL,
  email                 VARCHAR(160) NOT NULL,
  senha_hash            VARCHAR(255) NOT NULL,          -- password_hash()
  status                ENUM('ativo','bloqueado','inativo') NOT NULL DEFAULT 'ativo',
  trocar_senha          TINYINT(1) NOT NULL DEFAULT 0,  -- força troca no 1º acesso
  email_verificado_em   DATETIME NULL,
  ultimo_login_em       DATETIME NULL,
  ultimo_login_ip       VARBINARY(16) NULL,
  criado_em             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  excluido_em           DATETIME NULL,                  -- soft delete (LGPD: anonimização)
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_papel (papel_id),
  KEY idx_usuarios_status (status),
  CONSTRAINT fk_usuarios_papel FOREIGN KEY (papel_id) REFERENCES papeis (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tentativas_login (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email       VARCHAR(160) NOT NULL,
  ip          VARBINARY(16) NOT NULL,
  sucesso     TINYINT(1) NOT NULL DEFAULT 0,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tl_email_data (email, criado_em),
  KEY idx_tl_ip_data (ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recuperacoes_senha (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  BIGINT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,                        -- SHA-256 do token enviado por e-mail
  expira_em   DATETIME NOT NULL,
  usado_em    DATETIME NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_rs_token (token_hash),
  KEY idx_rs_usuario (usuario_id),
  CONSTRAINT fk_rs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id          BIGINT UNSIGNED NOT NULL,
  tipo_pessoa         ENUM('fisica','juridica') NOT NULL DEFAULT 'fisica',
  cpf_cnpj            VARCHAR(18) NULL,
  telefone            VARCHAR(20) NULL,
  whatsapp            VARCHAR(20) NULL,
  data_nascimento     DATE NULL,
  aceite_termos_em    DATETIME NULL,                    -- LGPD
  aceite_privacidade_em DATETIME NULL,                  -- LGPD
  aceita_marketing    TINYINT(1) NOT NULL DEFAULT 0,    -- LGPD: consentimento específico
  criado_em           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_usuario (usuario_id),
  UNIQUE KEY uq_clientes_cpf_cnpj (cpf_cnpj),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE funcionarios (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id    BIGINT UNSIGNED NOT NULL,
  cargo         VARCHAR(80) NULL,
  telefone      VARCHAR(20) NULL,
  data_admissao DATE NULL,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_funcionarios_usuario (usuario_id),
  CONSTRAINT fk_funcionarios_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enderecos (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id    BIGINT UNSIGNED NOT NULL,
  apelido       VARCHAR(40) NULL,                       -- "Casa", "Obra", "Escritório"
  destinatario  VARCHAR(120) NOT NULL,
  cep           CHAR(8) NOT NULL,
  logradouro    VARCHAR(160) NOT NULL,
  numero        VARCHAR(20) NOT NULL,
  complemento   VARCHAR(80) NULL,
  bairro        VARCHAR(80) NOT NULL,
  cidade        VARCHAR(80) NOT NULL,
  uf            CHAR(2) NOT NULL,
  padrao        TINYINT(1) NOT NULL DEFAULT 0,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_enderecos_cliente (cliente_id),
  CONSTRAINT fk_enderecos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. CATÁLOGO E ESTOQUE
-- =====================================================================

CREATE TABLE categorias (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pai_id      INT UNSIGNED NULL,
  nome        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  descricao   VARCHAR(255) NULL,
  icone       VARCHAR(60) NULL,
  ordem       SMALLINT NOT NULL DEFAULT 0,
  ativo       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug),
  KEY idx_categorias_pai (pai_id),
  CONSTRAINT fk_categorias_pai FOREIGN KEY (pai_id) REFERENCES categorias (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produtos (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id       INT UNSIGNED NOT NULL,
  sku                VARCHAR(40) NOT NULL,
  nome               VARCHAR(160) NOT NULL,
  slug               VARCHAR(180) NOT NULL,
  descricao_curta    VARCHAR(255) NULL,
  descricao          TEXT NULL,
  marca              VARCHAR(80) NULL,
  preco              DECIMAL(10,2) NOT NULL,
  preco_promocional  DECIMAL(10,2) NULL,
  promocao_inicio    DATETIME NULL,
  promocao_fim       DATETIME NULL,
  custo              DECIMAL(10,2) NULL,                 -- base do cálculo de lucro
  peso_kg            DECIMAL(8,3) NULL,                  -- cálculo de frete
  altura_cm          DECIMAL(6,2) NULL,
  largura_cm         DECIMAL(6,2) NULL,
  comprimento_cm     DECIMAL(6,2) NULL,
  destaque           TINYINT(1) NOT NULL DEFAULT 0,
  ativo              TINYINT(1) NOT NULL DEFAULT 1,
  total_vendido      INT UNSIGNED NOT NULL DEFAULT 0,    -- popularidade (atualizado no pedido pago)
  media_avaliacoes   DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  total_avaliacoes   INT UNSIGNED NOT NULL DEFAULT 0,
  meta_titulo        VARCHAR(70) NULL,                   -- SEO
  meta_descricao     VARCHAR(160) NULL,                  -- SEO
  criado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  excluido_em        DATETIME NULL,                      -- soft delete (preserva histórico de pedidos)
  PRIMARY KEY (id),
  UNIQUE KEY uq_produtos_sku (sku),
  UNIQUE KEY uq_produtos_slug (slug),
  KEY idx_produtos_categoria (categoria_id),
  KEY idx_produtos_listagem (ativo, excluido_em, criado_em),
  KEY idx_produtos_destaque (destaque, ativo),
  KEY idx_produtos_popularidade (total_vendido),
  FULLTEXT KEY ft_produtos_busca (nome, descricao_curta, marca),
  CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
  CONSTRAINT ck_produtos_preco CHECK (preco >= 0),
  CONSTRAINT ck_produtos_promo CHECK (preco_promocional IS NULL OR (preco_promocional >= 0 AND preco_promocional < preco))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE imagens_produtos (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  produto_id  BIGINT UNSIGNED NOT NULL,
  caminho     VARCHAR(255) NOT NULL,
  alt         VARCHAR(160) NULL,
  principal   TINYINT(1) NOT NULL DEFAULT 0,
  ordem       SMALLINT NOT NULL DEFAULT 0,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_imgprod_produto (produto_id, ordem),
  CONSTRAINT fk_imgprod_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estoque (
  produto_id      BIGINT UNSIGNED NOT NULL,
  quantidade      INT NOT NULL DEFAULT 0,
  estoque_minimo  INT NOT NULL DEFAULT 5,
  atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (produto_id),
  CONSTRAINT fk_estoque_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE,
  CONSTRAINT ck_estoque_qtd CHECK (quantidade >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimentacoes_estoque (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  produto_id       BIGINT UNSIGNED NOT NULL,
  tipo             ENUM('entrada','saida','ajuste','venda','cancelamento') NOT NULL,
  quantidade       INT NOT NULL,                         -- sempre positiva; o tipo define o sentido
  saldo_anterior   INT NOT NULL,
  saldo_posterior  INT NOT NULL,
  motivo           VARCHAR(255) NULL,
  pedido_id        BIGINT UNSIGNED NULL,
  usuario_id       BIGINT UNSIGNED NULL,
  criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mov_produto_data (produto_id, criado_em),
  KEY idx_mov_pedido (pedido_id),
  KEY idx_mov_usuario (usuario_id),
  CONSTRAINT fk_mov_produto FOREIGN KEY (produto_id) REFERENCES produtos (id),
  CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. CUPONS E PROMOÇÕES
-- =====================================================================

CREATE TABLE cupons (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo           VARCHAR(40) NOT NULL,
  descricao        VARCHAR(160) NULL,
  tipo             ENUM('percentual','valor_fixo','frete_gratis') NOT NULL,
  valor            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  valor_minimo_pedido DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  limite_total     INT UNSIGNED NULL,                    -- NULL = ilimitado
  limite_por_cliente INT UNSIGNED NOT NULL DEFAULT 1,
  total_usos       INT UNSIGNED NOT NULL DEFAULT 0,
  inicio_em        DATETIME NULL,
  fim_em           DATETIME NULL,
  ativo            TINYINT(1) NOT NULL DEFAULT 1,
  criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cupons_codigo (codigo),
  CONSTRAINT ck_cupons_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. CARRINHO
-- =====================================================================

CREATE TABLE carrinhos (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id    BIGINT UNSIGNED NULL,                    -- NULL = visitante
  token_sessao  CHAR(64) NULL,                           -- carrinho de visitante
  cupom_id      INT UNSIGNED NULL,
  status        ENUM('ativo','convertido','abandonado') NOT NULL DEFAULT 'ativo',
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_carrinhos_cliente (cliente_id, status),
  KEY idx_carrinhos_token (token_sessao),
  CONSTRAINT fk_carrinhos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
  CONSTRAINT fk_carrinhos_cupom   FOREIGN KEY (cupom_id)   REFERENCES cupons (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE itens_carrinho (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  carrinho_id  BIGINT UNSIGNED NOT NULL,
  produto_id   BIGINT UNSIGNED NOT NULL,
  quantidade   INT UNSIGNED NOT NULL DEFAULT 1,
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_itenscarrinho (carrinho_id, produto_id),
  KEY idx_itenscarrinho_produto (produto_id),
  CONSTRAINT fk_itenscarrinho_carrinho FOREIGN KEY (carrinho_id) REFERENCES carrinhos (id) ON DELETE CASCADE,
  CONSTRAINT fk_itenscarrinho_produto  FOREIGN KEY (produto_id)  REFERENCES produtos (id) ON DELETE CASCADE,
  CONSTRAINT ck_itenscarrinho_qtd CHECK (quantidade > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. PEDIDOS, PAGAMENTOS E ENTREGAS
-- =====================================================================

CREATE TABLE pedidos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero            VARCHAR(20) NOT NULL,                -- ex.: EI-2026-000123
  cliente_id        BIGINT UNSIGNED NOT NULL,
  endereco_id       BIGINT UNSIGNED NULL,
  -- Snapshot do endereço (o cadastro pode mudar depois)
  entrega_destinatario VARCHAR(120) NULL,
  entrega_cep       CHAR(8) NULL,
  entrega_logradouro VARCHAR(160) NULL,
  entrega_numero    VARCHAR(20) NULL,
  entrega_complemento VARCHAR(80) NULL,
  entrega_bairro    VARCHAR(80) NULL,
  entrega_cidade    VARCHAR(80) NULL,
  entrega_uf        CHAR(2) NULL,
  cupom_id          INT UNSIGNED NULL,
  subtotal          DECIMAL(10,2) NOT NULL,
  desconto          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  frete             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total             DECIMAL(10,2) NOT NULL,
  status            ENUM('aguardando_pagamento','pagamento_aprovado','em_preparacao',
                         'enviado','em_transporte','entregue','cancelado')
                    NOT NULL DEFAULT 'aguardando_pagamento',
  observacoes       VARCHAR(500) NULL,
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pedidos_numero (numero),
  KEY idx_pedidos_cliente (cliente_id, criado_em),
  KEY idx_pedidos_status_data (status, criado_em),
  KEY idx_pedidos_data (criado_em),
  CONSTRAINT fk_pedidos_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes (id),
  CONSTRAINT fk_pedidos_endereco FOREIGN KEY (endereco_id) REFERENCES enderecos (id) ON DELETE SET NULL,
  CONSTRAINT fk_pedidos_cupom    FOREIGN KEY (cupom_id)    REFERENCES cupons (id) ON DELETE SET NULL,
  CONSTRAINT ck_pedidos_total CHECK (total >= 0 AND subtotal >= 0 AND desconto >= 0 AND frete >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE itens_pedido (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id        BIGINT UNSIGNED NOT NULL,
  produto_id       BIGINT UNSIGNED NULL,
  nome_produto     VARCHAR(160) NOT NULL,                -- snapshot
  sku              VARCHAR(40) NOT NULL,                 -- snapshot
  preco_unitario   DECIMAL(10,2) NOT NULL,               -- preço efetivamente cobrado
  custo_unitario   DECIMAL(10,2) NULL,                   -- snapshot para cálculo de lucro
  quantidade       INT UNSIGNED NOT NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_itenspedido_pedido (pedido_id),
  KEY idx_itenspedido_produto (produto_id),
  CONSTRAINT fk_itenspedido_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos (id) ON DELETE CASCADE,
  CONSTRAINT fk_itenspedido_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE SET NULL,
  CONSTRAINT ck_itenspedido_qtd CHECK (quantidade > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedidos_historico_status (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id      BIGINT UNSIGNED NOT NULL,
  status_anterior VARCHAR(30) NULL,
  status_novo    VARCHAR(30) NOT NULL,
  observacao     VARCHAR(255) NULL,
  usuario_id     BIGINT UNSIGNED NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_phs_pedido (pedido_id, criado_em),
  CONSTRAINT fk_phs_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos (id) ON DELETE CASCADE,
  CONSTRAINT fk_phs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cupons_uso (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cupom_id    INT UNSIGNED NOT NULL,
  cliente_id  BIGINT UNSIGNED NOT NULL,
  pedido_id   BIGINT UNSIGNED NOT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuponsuso_pedido (pedido_id),
  KEY idx_cuponsuso_cupom_cliente (cupom_id, cliente_id),
  CONSTRAINT fk_cuponsuso_cupom   FOREIGN KEY (cupom_id)   REFERENCES cupons (id),
  CONSTRAINT fk_cuponsuso_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id),
  CONSTRAINT fk_cuponsuso_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagamentos: NUNCA armazenar PAN, CVV ou validade. Apenas referências do gateway.
CREATE TABLE pagamentos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id         BIGINT UNSIGNED NULL,
  solicitacao_id    BIGINT UNSIGNED NULL,                -- pagamento de serviço (opcional)
  gateway           VARCHAR(30) NOT NULL DEFAULT 'mercadopago',
  gateway_id        VARCHAR(80) NULL,                    -- ID da transação no gateway
  metodo            ENUM('pix','cartao_credito','cartao_debito','boleto','dinheiro','transferencia') NOT NULL,
  parcelas          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  cartao_bandeira   VARCHAR(20) NULL,                    -- permitido: bandeira
  cartao_ultimos4   CHAR(4) NULL,                        -- permitido: 4 últimos dígitos
  valor             DECIMAL(10,2) NOT NULL,
  status            ENUM('pendente','aprovado','recusado','estornado','cancelado','expirado') NOT NULL DEFAULT 'pendente',
  pago_em           DATETIME NULL,
  payload_webhook   JSON NULL,                           -- resposta do gateway (sem dados sensíveis)
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pagamentos_gateway (gateway, gateway_id),
  KEY idx_pagamentos_pedido (pedido_id),
  KEY idx_pagamentos_solicitacao (solicitacao_id),
  KEY idx_pagamentos_status_data (status, criado_em),
  CONSTRAINT fk_pagamentos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE SET NULL,
  CONSTRAINT ck_pagamentos_valor CHECK (valor >= 0)
  -- (origem pedido OU solicitação validada no PaymentService: o MySQL 8 proíbe CHECK em colunas com FK SET NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entregas (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pedido_id         BIGINT UNSIGNED NOT NULL,
  transportadora    VARCHAR(80) NULL,
  servico           VARCHAR(60) NULL,                    -- PAC, SEDEX, retirada...
  codigo_rastreio   VARCHAR(60) NULL,
  status            ENUM('pendente','preparando','postado','em_transporte','saiu_para_entrega','entregue','falha','devolvido')
                    NOT NULL DEFAULT 'pendente',
  prazo_estimado_dias SMALLINT UNSIGNED NULL,
  enviado_em        DATETIME NULL,
  entregue_em       DATETIME NULL,
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_entregas_pedido (pedido_id),
  KEY idx_entregas_rastreio (codigo_rastreio),
  CONSTRAINT fk_entregas_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. SERVIÇOS ELÉTRICOS
-- =====================================================================

CREATE TABLE categorias_servicos (
  id        SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome      VARCHAR(100) NOT NULL,
  slug      VARCHAR(120) NOT NULL,
  ordem     SMALLINT NOT NULL DEFAULT 0,
  ativo     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_catserv_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE servicos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id      SMALLINT UNSIGNED NOT NULL,
  nome              VARCHAR(160) NOT NULL,
  slug              VARCHAR(180) NOT NULL,
  descricao         TEXT NULL,
  imagem            VARCHAR(255) NULL,
  tipo_preco        ENUM('preco_inicial','sob_orcamento') NOT NULL DEFAULT 'sob_orcamento',
  preco_inicial     DECIMAL(10,2) NULL,
  tempo_estimado_min INT UNSIGNED NULL,
  disponivel        TINYINT(1) NOT NULL DEFAULT 1,
  meta_titulo       VARCHAR(70) NULL,
  meta_descricao    VARCHAR(160) NULL,
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  excluido_em       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicos_slug (slug),
  KEY idx_servicos_categoria (categoria_id),
  KEY idx_servicos_listagem (disponivel, excluido_em),
  CONSTRAINT fk_servicos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_servicos (id),
  CONSTRAINT ck_servicos_preco CHECK (tipo_preco = 'sob_orcamento' OR preco_inicial IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes_servicos (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  protocolo         VARCHAR(20) NOT NULL,                -- ex.: SV-2026-000045
  cliente_id        BIGINT UNSIGNED NOT NULL,
  servico_id        BIGINT UNSIGNED NOT NULL,
  endereco_id       BIGINT UNSIGNED NULL,
  descricao_cliente TEXT NOT NULL,
  data_preferida    DATE NULL,
  periodo_preferido ENUM('manha','tarde','noite','qualquer') NOT NULL DEFAULT 'qualquer',
  valor_orcado      DECIMAL(10,2) NULL,
  prazo_validade_orcamento DATE NULL,
  status            ENUM('solicitado','em_analise','orcamento_enviado','orcamento_aprovado',
                         'agendado','em_execucao','concluido','recusado','cancelado')
                    NOT NULL DEFAULT 'solicitado',
  observacoes_internas TEXT NULL,
  responsavel_id    BIGINT UNSIGNED NULL,                -- funcionário responsável
  criado_em         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_solserv_protocolo (protocolo),
  KEY idx_solserv_cliente (cliente_id, criado_em),
  KEY idx_solserv_servico (servico_id),
  KEY idx_solserv_status_data (status, criado_em),
  CONSTRAINT fk_solserv_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes (id),
  CONSTRAINT fk_solserv_servico  FOREIGN KEY (servico_id)  REFERENCES servicos (id),
  CONSTRAINT fk_solserv_endereco FOREIGN KEY (endereco_id) REFERENCES enderecos (id) ON DELETE SET NULL,
  CONSTRAINT fk_solserv_resp     FOREIGN KEY (responsavel_id) REFERENCES funcionarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pagamentos
  ADD CONSTRAINT fk_pagamentos_solicitacao FOREIGN KEY (solicitacao_id)
  REFERENCES solicitacoes_servicos (id) ON DELETE SET NULL;

CREATE TABLE solicitacoes_historico (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  solicitacao_id BIGINT UNSIGNED NOT NULL,
  status_anterior VARCHAR(30) NULL,
  status_novo    VARCHAR(30) NOT NULL,
  observacao     VARCHAR(255) NULL,
  visivel_cliente TINYINT(1) NOT NULL DEFAULT 1,
  usuario_id     BIGINT UNSIGNED NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_solhist_solicitacao (solicitacao_id, criado_em),
  CONSTRAINT fk_solhist_solicitacao FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes_servicos (id) ON DELETE CASCADE,
  CONSTRAINT fk_solhist_usuario     FOREIGN KEY (usuario_id)     REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agendamentos (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  solicitacao_id  BIGINT UNSIGNED NOT NULL,
  funcionario_id  BIGINT UNSIGNED NULL,
  inicio_em       DATETIME NOT NULL,
  fim_em          DATETIME NOT NULL,
  status          ENUM('agendado','confirmado','concluido','cancelado','reagendado') NOT NULL DEFAULT 'agendado',
  observacoes     VARCHAR(255) NULL,
  criado_em       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_agend_solicitacao (solicitacao_id),
  KEY idx_agend_funcionario_data (funcionario_id, inicio_em),
  KEY idx_agend_inicio (inicio_em),
  CONSTRAINT fk_agend_solicitacao FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes_servicos (id) ON DELETE CASCADE,
  CONSTRAINT fk_agend_funcionario FOREIGN KEY (funcionario_id) REFERENCES funcionarios (id) ON DELETE SET NULL,
  CONSTRAINT ck_agend_periodo CHECK (fim_em > inicio_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. AVALIAÇÕES E FAVORITOS
-- =====================================================================

CREATE TABLE avaliacoes (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id  BIGINT UNSIGNED NOT NULL,
  produto_id  BIGINT UNSIGNED NULL,
  servico_id  BIGINT UNSIGNED NULL,
  pedido_id   BIGINT UNSIGNED NULL,                      -- garante compra verificada
  nota        TINYINT UNSIGNED NOT NULL,
  titulo      VARCHAR(100) NULL,
  comentario  TEXT NULL,
  status      ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_aval_produto (produto_id, status),
  KEY idx_aval_servico (servico_id, status),
  KEY idx_aval_cliente (cliente_id),
  UNIQUE KEY uq_aval_cliente_produto (cliente_id, produto_id),
  UNIQUE KEY uq_aval_cliente_servico (cliente_id, servico_id),
  CONSTRAINT fk_aval_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
  CONSTRAINT fk_aval_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE,
  CONSTRAINT fk_aval_servico FOREIGN KEY (servico_id) REFERENCES servicos (id) ON DELETE CASCADE,
  CONSTRAINT fk_aval_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos (id) ON DELETE SET NULL,
  CONSTRAINT ck_aval_nota CHECK (nota BETWEEN 1 AND 5)
  -- (produto XOR serviço validado no ReviewService: o MySQL 8 proíbe CHECK em colunas com FK CASCADE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favoritos (
  cliente_id  BIGINT UNSIGNED NOT NULL,
  produto_id  BIGINT UNSIGNED NOT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cliente_id, produto_id),
  KEY idx_fav_produto (produto_id),
  CONSTRAINT fk_fav_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. NOTIFICAÇÕES E CENTRAL DE ATENDIMENTO
-- =====================================================================

CREATE TABLE notificacoes (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  BIGINT UNSIGNED NOT NULL,
  tipo        VARCHAR(40) NOT NULL,                      -- pedido, pagamento, servico, estoque, sistema
  titulo      VARCHAR(120) NOT NULL,
  mensagem    VARCHAR(500) NULL,
  link        VARCHAR(255) NULL,
  lida_em     DATETIME NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_usuario_lida (usuario_id, lida_em, criado_em),
  CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chamados (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  protocolo     VARCHAR(20) NOT NULL,
  cliente_id    BIGINT UNSIGNED NULL,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL,
  assunto       VARCHAR(160) NOT NULL,
  status        ENUM('aberto','em_atendimento','respondido','fechado') NOT NULL DEFAULT 'aberto',
  atendente_id  BIGINT UNSIGNED NULL,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_chamados_protocolo (protocolo),
  KEY idx_chamados_status (status, criado_em),
  CONSTRAINT fk_chamados_cliente   FOREIGN KEY (cliente_id)   REFERENCES clientes (id) ON DELETE SET NULL,
  CONSTRAINT fk_chamados_atendente FOREIGN KEY (atendente_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chamados_mensagens (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  chamado_id  BIGINT UNSIGNED NOT NULL,
  usuario_id  BIGINT UNSIGNED NULL,                      -- NULL = visitante
  mensagem    TEXT NOT NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_chmsg_chamado (chamado_id, criado_em),
  CONSTRAINT fk_chmsg_chamado FOREIGN KEY (chamado_id) REFERENCES chamados (id) ON DELETE CASCADE,
  CONSTRAINT fk_chmsg_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. FINANCEIRO
-- Faturamento  = soma de pedidos não cancelados (venda emitida)
-- Receita      = dinheiro efetivamente recebido (receitas / pagamentos aprovados)
-- Lucro        = receita recebida − despesas (− custo dos produtos vendidos)
-- =====================================================================

CREATE TABLE categorias_financeiras (
  id     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tipo   ENUM('receita','despesa') NOT NULL,
  nome   VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_catfin (tipo, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receitas (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id   SMALLINT UNSIGNED NULL,
  descricao      VARCHAR(200) NOT NULL,
  valor          DECIMAL(12,2) NOT NULL,
  status         ENUM('a_receber','recebida','cancelada') NOT NULL DEFAULT 'a_receber',
  vencimento     DATE NULL,
  recebida_em    DATE NULL,
  pagamento_id   BIGINT UNSIGNED NULL,
  pedido_id      BIGINT UNSIGNED NULL,
  solicitacao_id BIGINT UNSIGNED NULL,
  usuario_id     BIGINT UNSIGNED NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_receitas_status_data (status, recebida_em),
  KEY idx_receitas_vencimento (vencimento),
  KEY idx_receitas_pedido (pedido_id),
  CONSTRAINT fk_receitas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_financeiras (id) ON DELETE SET NULL,
  CONSTRAINT fk_receitas_pagamento FOREIGN KEY (pagamento_id) REFERENCES pagamentos (id) ON DELETE SET NULL,
  CONSTRAINT fk_receitas_pedido    FOREIGN KEY (pedido_id)    REFERENCES pedidos (id) ON DELETE SET NULL,
  CONSTRAINT fk_receitas_solic     FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes_servicos (id) ON DELETE SET NULL,
  CONSTRAINT fk_receitas_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT ck_receitas_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE despesas (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id   SMALLINT UNSIGNED NULL,
  descricao      VARCHAR(200) NOT NULL,
  fornecedor     VARCHAR(120) NULL,
  valor          DECIMAL(12,2) NOT NULL,
  status         ENUM('a_pagar','paga','cancelada') NOT NULL DEFAULT 'a_pagar',
  vencimento     DATE NULL,
  paga_em        DATE NULL,
  usuario_id     BIGINT UNSIGNED NULL,
  criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_despesas_status_data (status, paga_em),
  KEY idx_despesas_vencimento (vencimento),
  CONSTRAINT fk_despesas_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_financeiras (id) ON DELETE SET NULL,
  CONSTRAINT fk_despesas_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios (id) ON DELETE SET NULL,
  CONSTRAINT ck_despesas_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 10. AUDITORIA, LOGS, LGPD E CONFIGURAÇÕES
-- =====================================================================

CREATE TABLE logs_auditoria (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id   BIGINT UNSIGNED NULL,
  acao         VARCHAR(60) NOT NULL,                     -- ex.: produto.atualizado
  entidade     VARCHAR(60) NULL,
  entidade_id  BIGINT UNSIGNED NULL,
  dados_antes  JSON NULL,
  dados_depois JSON NULL,
  ip           VARBINARY(16) NULL,
  user_agent   VARCHAR(255) NULL,
  criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_usuario_data (usuario_id, criado_em),
  KEY idx_audit_entidade (entidade, entidade_id),
  KEY idx_audit_acao_data (acao, criado_em),
  CONSTRAINT fk_audit_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_erros (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nivel       ENUM('warning','error','critical') NOT NULL DEFAULT 'error',
  mensagem    VARCHAR(500) NOT NULL,
  arquivo     VARCHAR(255) NULL,
  linha       INT UNSIGNED NULL,
  contexto    JSON NULL,
  url         VARCHAR(255) NULL,
  usuario_id  BIGINT UNSIGNED NULL,
  criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logerr_nivel_data (nivel, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacoes_lgpd (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id    BIGINT UNSIGNED NOT NULL,
  tipo          ENUM('acesso','correcao','exclusao','portabilidade','revogar_consentimento') NOT NULL,
  descricao     VARCHAR(500) NULL,
  status        ENUM('aberta','em_andamento','concluida','negada') NOT NULL DEFAULT 'aberta',
  resposta      VARCHAR(500) NULL,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  concluido_em  DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_lgpd_usuario (usuario_id),
  KEY idx_lgpd_status (status, criado_em),
  CONSTRAINT fk_lgpd_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuracoes (
  chave       VARCHAR(60) NOT NULL,
  valor       TEXT NULL,
  descricao   VARCHAR(200) NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 12. DADOS INICIAIS (estruturais — não são dados de vendas)
-- =====================================================================

INSERT INTO papeis (id, slug, nome, descricao) VALUES
  (1, 'administrador', 'Administrador', 'Acesso total ao sistema'),
  (2, 'funcionario',   'Funcionário',   'Acesso operacional conforme permissões'),
  (3, 'cliente',       'Cliente',       'Acesso à loja e à área do cliente');

INSERT INTO permissoes (slug, modulo, descricao) VALUES
  ('dashboard.ver',        'dashboard',   'Ver dashboard'),
  ('produtos.ver',         'produtos',    'Listar produtos'),
  ('produtos.criar',       'produtos',    'Cadastrar produtos'),
  ('produtos.editar',      'produtos',    'Editar produtos'),
  ('produtos.excluir',     'produtos',    'Excluir produtos'),
  ('estoque.ver',          'estoque',     'Ver estoque e movimentações'),
  ('estoque.ajustar',      'estoque',     'Ajustar estoque'),
  ('categorias.gerenciar', 'categorias',  'Gerenciar categorias'),
  ('pedidos.ver',          'pedidos',     'Ver pedidos'),
  ('pedidos.atualizar',    'pedidos',     'Atualizar status de pedidos'),
  ('clientes.ver',         'clientes',    'Ver clientes'),
  ('clientes.gerenciar',   'clientes',    'Editar clientes e permissões'),
  ('servicos.ver',         'servicos',    'Ver serviços e solicitações'),
  ('servicos.gerenciar',   'servicos',    'Gerenciar serviços e orçamentos'),
  ('agenda.gerenciar',     'servicos',    'Gerenciar agendamentos'),
  ('cupons.gerenciar',     'marketing',   'Gerenciar cupons e promoções'),
  ('avaliacoes.moderar',   'marketing',   'Moderar avaliações'),
  ('financeiro.ver',       'financeiro',  'Ver financeiro'),
  ('financeiro.gerenciar', 'financeiro',  'Lançar receitas e despesas'),
  ('relatorios.exportar',  'relatorios',  'Exportar relatórios'),
  ('usuarios.gerenciar',   'usuarios',    'Gerenciar funcionários e acessos'),
  ('auditoria.ver',        'usuarios',    'Ver logs de auditoria'),
  ('atendimento.gerenciar','atendimento', 'Responder chamados'),
  ('configuracoes.editar', 'sistema',     'Editar configurações');

-- Administrador: todas as permissões
INSERT INTO papel_permissoes (papel_id, permissao_id)
SELECT 1, id FROM permissoes;

-- Funcionário: operação do dia a dia (sem financeiro, usuários, auditoria e configurações)
INSERT INTO papel_permissoes (papel_id, permissao_id)
SELECT 2, id FROM permissoes
WHERE slug IN ('dashboard.ver','produtos.ver','produtos.criar','produtos.editar','estoque.ver',
               'estoque.ajustar','pedidos.ver','pedidos.atualizar','clientes.ver','servicos.ver',
               'servicos.gerenciar','agenda.gerenciar','avaliacoes.moderar','atendimento.gerenciar');

INSERT INTO categorias (nome, slug, icone, ordem) VALUES
  ('Cabos e Fios',              'cabos-e-fios',              'fa-plug',            1),
  ('Disjuntores e Proteção',    'disjuntores-e-protecao',    'fa-shield-halved',   2),
  ('Quadros e Painéis',         'quadros-e-paineis',         'fa-table-cells',     3),
  ('Iluminação',                'iluminacao',                'fa-lightbulb',       4),
  ('Tomadas e Interruptores',   'tomadas-e-interruptores',   'fa-toggle-on',       5),
  ('Eletrodutos e Acessórios',  'eletrodutos-e-acessorios',  'fa-grip-lines',      6),
  ('Ferramentas',               'ferramentas',               'fa-screwdriver-wrench', 7),
  ('Automação e Energia Solar', 'automacao-e-energia-solar', 'fa-solar-panel',     8);

INSERT INTO categorias_servicos (nome, slug, ordem) VALUES
  ('Instalações Residenciais', 'instalacoes-residenciais', 1),
  ('Instalações Comerciais',   'instalacoes-comerciais',   2),
  ('Manutenção',               'manutencao',               3),
  ('Iluminação',               'iluminacao-servicos',      4),
  ('Quadros Elétricos',        'quadros-eletricos',        5),
  ('Diagnóstico Técnico',      'diagnostico-tecnico',      6);

INSERT INTO categorias_financeiras (tipo, nome) VALUES
  ('receita', 'Venda de produtos'),
  ('receita', 'Prestação de serviços'),
  ('receita', 'Outras receitas'),
  ('despesa', 'Compra de mercadorias'),
  ('despesa', 'Salários e encargos'),
  ('despesa', 'Impostos'),
  ('despesa', 'Aluguel e utilidades'),
  ('despesa', 'Marketing'),
  ('despesa', 'Frete e logística'),
  ('despesa', 'Outras despesas');

INSERT INTO configuracoes (chave, valor, descricao) VALUES
  ('loja_nome',            'Eletro Instal', 'Nome da empresa'),
  ('loja_whatsapp',        '',              'WhatsApp para atendimento (somente números, com DDI)'),
  ('loja_email_contato',   '',              'E-mail de contato'),
  ('frete_gratis_acima',   '0',             'Valor mínimo para frete grátis (0 = desativado)'),
  ('frete_fixo',          '0',             'Valor fixo de frete (0 = grátis)'),
  ('login_max_tentativas', '5',             'Tentativas de login antes do bloqueio temporário'),
  ('login_bloqueio_min',   '15',            'Minutos de bloqueio após exceder tentativas');

-- ---------------------------------------------------------------------
-- USUÁRIO ADMINISTRADOR — APENAS AMBIENTE DE DESENVOLVIMENTO
--   E-mail: admin@eletroinstal.local
--   Senha:  Admin@Dev2026!
--   trocar_senha = 1  → a aplicação deve exigir nova senha no 1º acesso.
--   REMOVER/ALTERAR este usuário antes de qualquer implantação em produção.
-- ---------------------------------------------------------------------
INSERT INTO usuarios (papel_id, nome, email, senha_hash, status, trocar_senha, email_verificado_em)
VALUES (1, 'Administrador (DEV)', 'admin@eletroinstal.local',
        '$2y$12$BJRmrXY4RKazJrdYQF8nN.v0p4sCFP7XH6qzuQ4j74KXVmdARDkSi',
        'ativo', 1, NOW());

INSERT INTO funcionarios (usuario_id, cargo)
SELECT id, 'Administrador' FROM usuarios WHERE email = 'admin@eletroinstal.local';
