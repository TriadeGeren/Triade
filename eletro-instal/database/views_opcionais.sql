-- ELETRO INSTAL — VIEWS OPCIONAIS (o site NÃO depende delas)
-- Útil só se você quiser consultar relatórios direto no phpMyAdmin.
-- Se a hospedagem não permitir views, ignore este arquivo.

SET NAMES utf8mb4;

-- =====================================================================
-- 11. VIEWS (OPCIONAIS — a API não depende delas; consultas são feitas direto nas tabelas)
-- =====================================================================

CREATE OR REPLACE VIEW vw_estoque_baixo AS
SELECT p.id, p.sku, p.nome, e.quantidade, e.estoque_minimo
FROM produtos p
JOIN estoque e ON e.produto_id = p.id
WHERE p.excluido_em IS NULL AND p.ativo = 1 AND e.quantidade <= e.estoque_minimo;

CREATE OR REPLACE VIEW vw_faturamento_mensal AS
SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes,
       COUNT(*)   AS total_pedidos,
       SUM(total) AS faturamento
FROM pedidos
WHERE status <> 'cancelado'
GROUP BY DATE_FORMAT(criado_em, '%Y-%m');

CREATE OR REPLACE VIEW vw_resumo_financeiro_mensal AS
SELECT m.mes,
       COALESCE(r.recebido, 0)  AS receita_recebida,
       COALESCE(d.pago, 0)      AS despesas_pagas,
       COALESCE(r.recebido, 0) - COALESCE(d.pago, 0) AS resultado
FROM (
  SELECT DATE_FORMAT(recebida_em, '%Y-%m') AS mes FROM receitas WHERE status = 'recebida'
  UNION
  SELECT DATE_FORMAT(paga_em, '%Y-%m') FROM despesas WHERE status = 'paga'
) m
LEFT JOIN (
  SELECT DATE_FORMAT(recebida_em, '%Y-%m') AS mes, SUM(valor) AS recebido
  FROM receitas WHERE status = 'recebida' GROUP BY 1
) r ON r.mes = m.mes
LEFT JOIN (
  SELECT DATE_FORMAT(paga_em, '%Y-%m') AS mes, SUM(valor) AS pago
  FROM despesas WHERE status = 'paga' GROUP BY 1
) d ON d.mes = m.mes;

