# Studio Verso — o que foi corrigido

## Pagamento (Mercado Pago) — por que não completava
1. **`URL_SITE` terminava em `/index.php`** (segredos.php). Os links de volta do Mercado Pago saíam como
   `.../Studio_Verso/index.php/pedido_retorno.php?pedido=N`, que abre a página inicial em vez da tela de
   confirmação — o pedido nunca era conferido e ficava "pendente". Corrigido no `segredos.php` e, além disso,
   o `config.php` agora remove sozinho qualquer `/arquivo.php` colado no fim.
2. O retorno agora consulta o pagamento direto pelo `payment_id` que o Mercado Pago manda (a busca por
   `external_reference` pode demorar alguns segundos) e a página se atualiza sozinha enquanto estiver pendente.
3. Erros do Mercado Pago agora vão para o log de erros (antes eram engolidos), com o motivo exato.
4. Colunas `mp_preference_id`, `mp_payment_id` e `estoque_baixado` são criadas sozinhas se faltarem.
5. `auto_return` só é enviado com https; token/URL com espaço ou quebra de linha são limpos.
6. Novo: **`pagar.php`** reabre o pagamento de um pedido existente (antes "Tentar pagar novamente" mandava para
   um carrinho vazio e as personalizações já estavam "usadas").
7. Novo: **Admin > Diagnóstico do pagamento** (verifica URL, token, conta, colunas e cria um pagamento de teste).

## Outros bugs
- Carrinho era apagado ao clicar em "Ir para pagamento" (cliente perdia tudo se voltasse sem pagar) — agora só
  é limpo na página de retorno. Botão não permite mais clique duplo (pedido duplicado).
- **Estoque nunca era descontado** depois da venda — agora desconta uma única vez quando o pagamento é aprovado.
- Um pedido aprovado (inclusive à mão) não volta a "pendente/recusado" por causa de outra tentativa falha.
- "Faturamento de hoje" virava o dia às 21h (PHP/MySQL em UTC) — fuso fixado em America/Fortaleza.
- "Pedidos novos" contava checkouts abandonados — agora conta só pedidos pagos.
- `finalizar.php` quebrava com carrinho corrompido no navegador.

## Segurança
- Novo `.htaccess` na raiz bloqueia `segredos.php`, `.md`, `.sql`, `.log`.
- **Troque as credenciais** (token do Mercado Pago e senha do banco): elas estavam dentro do zip enviado.
- Troque a senha padrão `admin123` do admin se ainda não trocou.

---
# Revisão de novembro/2026

## Erros corrigidos
- **`finalizar.php`**: o script da página declarava `esc` e conflitava com o `script.js`, que então não carregava — o carrinho lateral e o contador ficavam mortos na tela de finalizar. Agora o código da página roda isolado.
- **Menu do celular**: o botão ☰ não fazia nada; agora abre e fecha o menu.
- **`login.php` / `cadastro.php`**: links "Cadastre-se" e "Entrar" usavam uma cor (`--fio-petroleo`) que não existe; passam a usar o roxo da marca.
- **Admin > Mensagens**: "Marcar como não lida" voltava a marcar como lida na hora. Corrigido.
- **Produto sem estoque** que tinha personalização ainda deixava adicionar ao carrinho; agora o formulário some e o servidor também recusa.
- **Contato**: se algum campo fosse inválido, o formulário recarregava sem avisar nada; agora mostra o aviso.
- Página do produto: categoria, preço e "sob encomenda" estavam sem estilo.

## Modo claro/escuro
- Alertas, selos de status, botões, tabelas e variação do faturamento agora têm cores próprias no escuro (antes ficavam claros demais ou ilegíveis).
- Sem escolha salva, o site segue o tema do aparelho e acompanha se ele mudar; a escolha manual continua guardada.
- Cor da barra do navegador no celular acompanha o tema; transição suave ao trocar.

---
# Troca do pagamento: Mercado Pago → Pix manual
- Removidos: `includes/mercadopago.php`, `webhook_mercadopago.php`, `admin/diagnostico_mp.php` e o item "Diagnóstico do pagamento" do menu.
- **Novo** `includes/pix.php`: gera o Pix Copia e Cola (padrão do Banco Central, com CRC16) já com o valor do pedido.
- **Novo** `includes/pedidos.php`: `baixarEstoquePedido()` e a coluna de controle de estoque (não dependem mais de pagamento online).
- `processar_pedido.php`: salva o pedido e leva o cliente para `pedido_retorno.php`, que mostra QR Code, código Pix, chave e botão de WhatsApp. `pagar.php` só redireciona para lá.
- Só quem fez o pedido (mesmo navegador), o dono da conta ou um admin abre a tela do pedido.
- Admin > Pedidos: botão **✔ Confirmar pagamento** (marca como aprovado e desconta o estoque).
- `segredos.php`: o token do Mercado Pago foi removido. **Preencha `PIX_CHAVE`** com a sua chave Pix.
- As colunas `mp_*` do banco ficam como estão (não atrapalham).
