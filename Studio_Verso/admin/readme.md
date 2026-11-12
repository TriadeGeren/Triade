# Painel administrativo — Studio Verso

Acesse em **`/admin/`** (ex.: `https://seusite.com/admin/`). Só contas com `is_admin = 1` entram.

## O que tem
| Tela | O que faz |
|---|---|
| Visão geral | Faturamento de hoje e do mês, gráfico de 30 dias, últimos pedidos, estoque baixo |
| Faturamento | Gráficos + filtros (período, pagamento, status, categoria, produto, dia/mês) + exportação **.xlsx** e **.csv** |
| Pedidos | Busca/filtros, detalhe do pedido, mudar status e pagamento, excluir |
| Produtos | Cadastrar, editar, excluir, foto, estoque |
| Categorias | Criar, editar, excluir |
| Personalização | Opções que o cliente escolhe nos produtos sob encomenda (tamanho, cor, nome bordado...) e quanto cada uma acrescenta ao preço |
| Clientes e usuários | Tornar/remover admin, redefinir senha, excluir conta |
| Mensagens | Pedidos de orçamento enviados pelo formulário de contato |
| Minha conta | Trocar a própria senha |

## Como o faturamento é calculado
Soma de `quantidade × preço unitário` dos itens dos pedidos. **Por padrão só entram pedidos com pagamento "Aprovado"** — mude o filtro "Pagamento" para ver pendentes ou todos.

## Exportação
- **Excel (.xlsx)**: 4 abas — Pedidos detalhados, Resumo por dia/mês (com total), Por categoria, Por produto.
- **CSV**: detalhado (1 linha por item) ou resumo. Separador `;` e vírgula decimal (abre direto no Excel em português).
- A exportação respeita exatamente os filtros que estão na tela.
- O `.xlsx` precisa da extensão PHP **zip** (já incluída no Dockerfile).

## Primeiro uso
1. Entre com o admin do `schema.sql` e **troque a senha em "Minha conta"** (a padrão é pública no código).
2. A tabela `mensagens` é criada automaticamente no primeiro acesso ao painel.

## Personalização (sob encomenda)
- Crie as opções em **Personalização**. Pode valer para uma **categoria inteira** ou para **um produto**.
- Preço final = preço do produto + acréscimos escolhidos. O cálculo é feito no servidor.
- Todo produto sob encomenda aceita descrição e até 5 fotos de referência (JPG/PNG/WEBP, 5 MB cada).
- Os pedidos mostram as escolhas e as fotos em **Pedidos > abrir pedido**. As fotos só o admin enxerga.
- As fotos ficam na pasta `referencias/` (criada sozinha). Personalizações que não viraram pedido são apagadas após 7 dias.

## Modo escuro
Botão 🌙/☀️ no topo (site e admin). A escolha fica salva no navegador; na primeira visita segue o tema do aparelho.
