# Eletro Instal — Guia de publicação na InfinityFree

Loja virtual + serviços elétricos + painel administrativo, em **PHP puro + MySQL**
(o único tipo de hospedagem que a InfinityFree oferece; Node.js não roda lá).

## O que tem no pacote

```
htdocs/       ← TUDO que vai para a pasta "htdocs" da InfinityFree
  index.php  catalogo.php  produto.php  carrinho.php  servicos.php
  login.php  registro.php  conta.php   admin.php   termos.php  privacidade.php
  api/           ← API REST em PHP  (config.php é o ÚNICO arquivo que você edita)
  assets/        ← CSS, JS, fontes e ícones (nada depende de CDN externo)
  uploads/produtos/   ← imagens dos produtos (26 de exemplo)
  partials/      ← cabeçalho e rodapé compartilhados
database/
  eletro_instal.sql      ← OBRIGATÓRIO: cria as 40 tabelas e dados base
  produtos_exemplo.sql   ← OPCIONAL: 26 produtos, 8 serviços e 1 cupom de demonstração
  views_opcionais.sql    ← OPCIONAL: o site não depende dele
```

## Passo a passo

### 1. Criar o banco de dados
1. Painel da InfinityFree → sua conta de hospedagem → **MySQL Databases**.
2. Crie um banco (ex.: `loja`). O nome final terá prefixo, ex.: `if0_41234567_loja`.
3. Anote os 4 dados: **host** (ex.: `sql311.infinityfree.com`), **nome do banco**, **usuário** (`if0_41234567`) e **senha** (a da sua conta de hospedagem).

### 2. Importar as tabelas
1. Na mesma tela, clique em **phpMyAdmin** do banco criado.
2. **Selecione o banco** na coluna da esquerda (importante!) → aba **Importar**.
3. Importe `database/eletro_instal.sql`. Depois, se quiser a loja já com produtos de exemplo, importe `database/produtos_exemplo.sql`.
4. Importe **uma vez só**, em banco vazio.

### 3. Configurar a conexão
Abra `htdocs/api/config.php` e preencha `db_host`, `db_name`, `db_user` e `db_pass`.
A chave de segurança dos logins é gerada sozinha no primeiro uso.

### 4. Enviar os arquivos
1. Use o **Online File Manager** ou FTP (FileZilla; dados em *Account Settings → FTP*).
2. Entre na pasta **`htdocs`** e apague o `index2.html` padrão.
3. Envie o **conteúdo** da pasta `htdocs/` do pacote (não a pasta em si). O `index.php` deve ficar direto em `htdocs/`.
4. Confira que os arquivos que começam com ponto (`.htaccess`) subiram — eles protegem as pastas internas.

### 5. Testar
- Abra `https://SEU-DOMINIO/api/health` **no navegador**. Deve aparecer `{"status":"ok","banco":true,...}`.
  - `503` / "não foi possível conectar ao banco" → revise o `config.php`.
- Abra `https://SEU-DOMINIO/` — a loja deve carregar com os produtos.

> A InfinityFree tem uma verificação anti-robô por JavaScript. Ela só funciona em **navegador**;
> `curl`, Postman e similares recebem uma página de verificação em vez do JSON.

### 6. Primeiro acesso ao painel
- Acesse `/login.php` com **admin@eletroinstal.local** / **Admin@Dev2026!**
- O sistema **obriga trocar a senha** no primeiro login (e bloqueia o painel até isso).
- Depois: **Painel → Configurações** → informe o **WhatsApp** da loja (aparece no rodapé e após pedidos) e o frete.
- Para trocar o e-mail do admin: phpMyAdmin → aba SQL →
  `UPDATE usuarios SET email = 'seu@email.com' WHERE id = 1;`

### 7. Ativar HTTPS (recomendado)
No painel da InfinityFree, emita o SSL gratuito. Quando estiver ativo, abra `htdocs/.htaccess` e remova o `#` do bloco "Para forçar HTTPS".

### 8. Antes de divulgar
- Exclua (ou edite) os produtos/serviços de exemplo em **Painel → Produtos / Serviços**.
- Revise `termos.php` e `privacidade.php`: são **modelos** — adapte à sua empresa (de preferência com orientação jurídica).
- Os textos da página inicial (faixa de diferenciais) são editáveis em `index.php`.

## Como o site funciona (resumo)
- **Cliente:** catálogo com filtros → carrinho (visitante ou logado) → cupom → checkout com endereço (ViaCEP opcional) → pedido. O **pagamento é combinado com a loja** (PIX/transferência/dinheiro); a loja aprova no painel.
- **Painel:** dashboard (faturamento, pedidos, estoque baixo, financeiro), pedidos, produtos (com upload de imagens), estoque (com histórico), serviços (solicitações + catálogo), clientes, cupons, financeiro, categorias e configurações. Menus aparecem conforme o perfil (Administrador vê tudo; Funcionário não vê financeiro, cupons, categorias e configurações).
- **Regras automáticas:** venda baixa o estoque; cancelamento devolve; aprovar pagamento lança a receita e atualiza "mais vendidos"; toda ação administrativa fica em `logs_auditoria`.

## Limites da hospedagem gratuita (importante)
- **Sem e-mails:** a InfinityFree bloqueia `mail()`. Por isso não há e-mail de confirmação nem "esqueci minha senha". Para redefinir a senha de um cliente, gere um hash e atualize pelo phpMyAdmin, ou peça ao cliente para criar nova conta.
- **Sem gateway de pagamento** (Pix automático/cartão): exigiria webhooks e certificados que o plano gratuito não suporta de forma confiável. A estrutura (`pagamentos`) está pronta para integrar depois, em outra hospedagem.
- Limites de uso da conta gratuita (hits/dia, CPU) valem para a loja; para tráfego maior, migre para um plano pago — o pacote funciona igual.

## Solução de problemas
| Sintoma | Causa provável / solução |
|---|---|
| Página em branco / erro 500 | No `config.php` coloque `'debug' => true`, recarregue `/api/health`, leia o erro e volte para `false`. |
| "Não foi possível conectar ao banco" | Host/usuário/senha/nome do banco errados em `config.php` (o host **não** é `localhost`). |
| Produtos não aparecem | `produtos_exemplo.sql` não importado, ou importado em banco errado. |
| "O servidor não respondeu corretamente" | Verificação anti-robô da InfinityFree: abra o site no navegador e recarregue. |
| Imagens quebradas | Confirme que a pasta `uploads/produtos/` foi enviada por completo. |
| Login volta para a tela de login | Limpe os dados do site no navegador; confira se a hora/data do dispositivo está correta. |
| "Muitas tentativas" no login | Proteção de força bruta: aguarde 15 minutos. |

## Problemas do projeto original que foram corrigidos
| Área | Problema | Correção |
|---|---|---|
| Banco | `CREATE DATABASE`/`USE` (não permitido), collation do MySQL 8 (a InfinityFree usa MariaDB), sem produtos, acentos corrompiam ao importar | SQL compatível, `SET NAMES utf8mb4`, views opcionais, dados de exemplo |
| Backend | Node.js (não roda na InfinityFree); `server.js` quebrado (rotas sem import); PHP existente cobria ~30% | Backend completo reescrito em PHP, sem Composer |
| Segurança | `SELECT p.*` expunha o **custo** dos produtos no site público; JWT_SECRET padrão; sem troca obrigatória de senha; XSS por `innerHTML` | Colunas explícitas; chave gerada automaticamente; troca forçada (também no servidor); todo texto é escapado |
| Lógica | Promoção ignorava as datas; checkout sem endereço; estoque sem trava (venda acima do saldo); nº de pedido por timestamp; rota `/servicos/solicitacoes` colidia com `/servicos/:slug`; carrinho logado não funcionava | Todos corrigidos e cobertos por teste |
| Frontend | API fixa em `localhost:3000`; imagens do `via.placeholder.com` (fora do ar); `prompt()` para cadastrar produto; template React/Vite sem uso | Caminhos relativos; imagens próprias; formulários reais; template removido |
| Hospedagem | Header `Authorization` removido por hosts CGI; PUT/DELETE bloqueados | Reserva `X-Auth-Token` e `X-HTTP-Method-Override` |

## Como foi testado
Ambiente local com **MariaDB 10.11 + PHP 8.3**: 142 testes automatizados (API: estoque, cupom, cancelamento, permissões, token adulterado, upload malicioso; e interface: execução do JavaScript real de todas as páginas) + 17 de permissões. **Não foi possível** testar em navegador visual nem na própria InfinityFree, nem em PHP 7.4 (o código evita recursos do PHP 8). Se algo se comportar diferente aí, o passo "Solução de problemas" cobre os casos mais prováveis.
