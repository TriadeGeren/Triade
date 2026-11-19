-- =====================================================================
-- ELETRO INSTAL — DADOS DE EXEMPLO (OPCIONAL)
-- Importe DEPOIS de eletro_instal.sql, para a loja não ficar vazia.
-- Produtos/serviços fictícios: edite ou exclua pelo painel Admin.
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO produtos (categoria_id, sku, nome, slug, descricao_curta, descricao, marca, preco, preco_promocional, custo, destaque, ativo, total_vendido) VALUES
(1,'CAB-FLEX-25-100','Cabo Flexível 2,5 mm² 750 V — Rolo 100 m','cabo-flexivel-2-5-mm2-750-v-rolo-100-m-cabflex25100','Cabo de cobre flexível, isolação antichama, ideal para circuitos de tomadas e iluminação.','Cabo de cobre flexível, isolação antichama, ideal para circuitos de tomadas e iluminação.

Especificações
• Marca: VoltPro
• Código (SKU): CAB-FLEX-25-100
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','VoltPro',289.9,259.9,170,1,1,37),
(1,'CAB-FLEX-40-100','Cabo Flexível 4 mm² 750 V — Rolo 100 m','cabo-flexivel-4-mm2-750-v-rolo-100-m-cabflex40100','Condutor de cobre flexível 4 mm² para chuveiros, ar-condicionado e circuitos de maior carga.','Condutor de cobre flexível 4 mm² para chuveiros, ar-condicionado e circuitos de maior carga.

Especificações
• Marca: VoltPro
• Código (SKU): CAB-FLEX-40-100
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','VoltPro',449.9,NULL,270,1,1,34),
(1,'CAB-PP-3X15-50','Cabo PP 3×1,5 mm² — Rolo 50 m','cabo-pp-31-5-mm2-rolo-50-m-cabpp3x1550','Cabo PP 3 vias com capa reforçada para extensões e ligações de equipamentos.','Cabo PP 3 vias com capa reforçada para extensões e ligações de equipamentos.

Especificações
• Marca: TecnoCabo
• Código (SKU): CAB-PP-3X15-50
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','TecnoCabo',329.0,NULL,200,0,1,0),
(2,'DIS-MONO-32','Disjuntor Monopolar 32 A Curva C','disjuntor-monopolar-32-a-curva-c-dismono32','Proteção contra sobrecarga e curto-circuito. Padrão DIN, 3 kA.','Proteção contra sobrecarga e curto-circuito. Padrão DIN, 3 kA.

Especificações
• Marca: EletroMax
• Código (SKU): DIS-MONO-32
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','EletroMax',18.9,NULL,9,1,1,28),
(2,'DIS-BI-40','Disjuntor Bipolar 40 A Curva C','disjuntor-bipolar-40-a-curva-c-disbi40','Disjuntor bipolar DIN para chuveiros e circuitos de 220 V.','Disjuntor bipolar DIN para chuveiros e circuitos de 220 V.

Especificações
• Marca: EletroMax
• Código (SKU): DIS-BI-40
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','EletroMax',54.9,49.9,28,0,1,0),
(2,'DR-25-30','Interruptor Diferencial Residual DR 25 A / 30 mA','interruptor-diferencial-residual-dr-25-a-30-ma-dr2530','Protege pessoas contra choques elétricos. Exigido pela NBR 5410 em áreas molhadas.','Protege pessoas contra choques elétricos. Exigido pela NBR 5410 em áreas molhadas.

Especificações
• Marca: EletroMax
• Código (SKU): DR-25-30
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','EletroMax',189.9,NULL,105,1,1,22),
(2,'DPS-45KA','Protetor de Surto DPS 45 kA — Classe II','protetor-de-surto-dps-45-ka-classe-ii-dps45ka','Protege equipamentos eletrônicos contra surtos e descargas atmosféricas indiretas.','Protege equipamentos eletrônicos contra surtos e descargas atmosféricas indiretas.

Especificações
• Marca: EletroMax
• Código (SKU): DPS-45KA
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','EletroMax',79.9,NULL,41,0,1,0),
(3,'QD-12-EMB','Quadro de Distribuição 12 Disjuntores — Embutir','quadro-de-distribuicao-12-disjuntores-embutir-qd12emb','Quadro em chapa com barramento, porta e trilho DIN. Acabamento branco.','Quadro em chapa com barramento, porta e trilho DIN. Acabamento branco.

Especificações
• Marca: Instalux
• Código (SKU): QD-12-EMB
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',134.9,NULL,72,0,1,0),
(3,'QD-18-SOB','Quadro de Distribuição 18 Disjuntores — Sobrepor','quadro-de-distribuicao-18-disjuntores-sobrepor-qd18sob','Quadro de sobrepor com barramentos de fase, neutro e terra incluídos.','Quadro de sobrepor com barramentos de fase, neutro e terra incluídos.

Especificações
• Marca: Instalux
• Código (SKU): QD-18-SOB
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',169.9,NULL,92,1,1,13),
(3,'QD-BAR-12','Barramento de Neutro/Terra 12 Furos','barramento-de-neutro-terra-12-furos-qdbar12','Barramento de cobre eletrolítico com isolador, 12 conexões.','Barramento de cobre eletrolítico com isolador, 12 conexões.

Especificações
• Marca: Instalux
• Código (SKU): QD-BAR-12
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',22.9,NULL,11,0,1,0),
(4,'LED-BULB-12W','Lâmpada LED Bulbo 12 W E27 6500 K','lampada-led-bulbo-12-w-e27-6500-k-ledbulb12w','Luz branca fria, baixo consumo e longa vida útil (25.000 h). Bivolt.','Luz branca fria, baixo consumo e longa vida útil (25.000 h). Bivolt.

Especificações
• Marca: Lumina
• Código (SKU): LED-BULB-12W
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Lumina',12.9,9.9,5.5,1,1,7),
(4,'LED-PAN-18','Painel LED Embutir 18 W Quadrado','painel-led-embutir-18-w-quadrado-ledpan18','Painel slim de embutir com luz neutra 4000 K. Acabamento uniforme sem ofuscamento.','Painel slim de embutir com luz neutra 4000 K. Acabamento uniforme sem ofuscamento.

Especificações
• Marca: Lumina
• Código (SKU): LED-PAN-18
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Lumina',39.9,NULL,19,0,1,0),
(4,'LED-REF-50','Refletor LED 50 W IP66','refletor-led-50-w-ip66-ledref50','Refletor para áreas externas, resistente à chuva e poeira. Luz branca fria.','Refletor para áreas externas, resistente à chuva e poeira. Luz branca fria.

Especificações
• Marca: Lumina
• Código (SKU): LED-REF-50
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Lumina',89.9,NULL,46,1,1,1),
(4,'LED-FITA-5M','Fita LED 12 V 5 m com Fonte','fita-led-12-v-5-m-com-fonte-ledfita5m','Fita LED autoadesiva 5 m com fonte bivolt para decoração e iluminação indireta.','Fita LED autoadesiva 5 m com fonte bivolt para decoração e iluminação indireta.

Especificações
• Marca: Lumina
• Código (SKU): LED-FITA-5M
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Lumina',69.9,NULL,34,0,1,0),
(5,'TOM-2P-10A','Tomada 2P+T 10 A Padrão NBR 14136 (Módulo)','tomada-2p-t-10-a-padrao-nbr-14136-modulo-tom2p10a','Tomada de embutir padrão brasileiro. Contatos em liga de cobre.','Tomada de embutir padrão brasileiro. Contatos em liga de cobre.

Especificações
• Marca: Instalux
• Código (SKU): TOM-2P-10A
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',9.9,NULL,4,0,1,0),
(5,'INT-SIMP','Interruptor Simples 10 A (Módulo)','interruptor-simples-10-a-modulo-intsimp','Interruptor simples de embutir, acionamento suave e silencioso.','Interruptor simples de embutir, acionamento suave e silencioso.

Especificações
• Marca: Instalux
• Código (SKU): INT-SIMP
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',8.9,NULL,3.8,0,1,0),
(5,'TOM-USB-DUPLA','Tomada com Carregador USB Duplo 2,1 A','tomada-com-carregador-usb-duplo-2-1-a-tomusbdupla','Tomada 2P+T com duas saídas USB para carregar celular e tablet direto da parede.','Tomada 2P+T com duas saídas USB para carregar celular e tablet direto da parede.

Especificações
• Marca: Instalux
• Código (SKU): TOM-USB-DUPLA
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Instalux',49.9,NULL,24,1,1,0),
(6,'ELE-COR-34','Eletroduto Corrugado 3/4" — Rolo 50 m','eletroduto-corrugado-3-4-rolo-50-m-elecor34','Eletroduto flexível antichama para instalações embutidas em paredes e lajes.','Eletroduto flexível antichama para instalações embutidas em paredes e lajes.

Especificações
• Marca: TubFlex
• Código (SKU): ELE-COR-34
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','TubFlex',69.9,NULL,38,0,1,0),
(6,'ELE-FITA-ISOL','Fita Isolante 19 mm × 20 m','fita-isolante-19-mm-20-m-elefitaisol','Fita isolante antichama 750 V, boa aderência e flexibilidade.','Fita isolante antichama 750 V, boa aderência e flexibilidade.

Especificações
• Marca: TubFlex
• Código (SKU): ELE-FITA-ISOL
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','TubFlex',7.9,NULL,3.2,0,1,0),
(6,'ELE-ABR-200','Abraçadeira Nylon 200 mm — Pacote 100 un','abracadeira-nylon-200-mm-pacote-100-un-eleabr200','Abraçadeiras de nylon para organização de cabos e chicotes.','Abraçadeiras de nylon para organização de cabos e chicotes.

Especificações
• Marca: TubFlex
• Código (SKU): ELE-ABR-200
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','TubFlex',14.9,NULL,6.5,0,1,0),
(7,'FER-ALIC-8','Alicate Universal Isolado 1000 V 8"','alicate-universal-isolado-1000-v-8-feralic8','Aço forjado, cabos isolados 1000 V. Ideal para cortar, descascar e prender.','Aço forjado, cabos isolados 1000 V. Ideal para cortar, descascar e prender.

Especificações
• Marca: Ferrotec
• Código (SKU): FER-ALIC-8
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Ferrotec',59.9,NULL,29,1,1,0),
(7,'FER-MULTI','Multímetro Digital CAT III','multimetro-digital-cat-iii-fermulti','Mede tensão, corrente, resistência e continuidade. Display retroiluminado.','Mede tensão, corrente, resistência e continuidade. Display retroiluminado.

Especificações
• Marca: Ferrotec
• Código (SKU): FER-MULTI
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Ferrotec',129.9,109.9,68,1,1,0),
(7,'FER-KIT-6','Kit Chaves Isoladas 1000 V — 6 peças','kit-chaves-isoladas-1000-v-6-pecas-ferkit6','Chaves de fenda e Phillips com isolação 1000 V e ponta temperada.','Chaves de fenda e Phillips com isolação 1000 V e ponta temperada.

Especificações
• Marca: Ferrotec
• Código (SKU): FER-KIT-6
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Ferrotec',119.9,NULL,61,0,1,0),
(8,'SOL-PAIN-550','Painel Solar Monocristalino 550 W','painel-solar-monocristalino-550-w-solpain550','Módulo fotovoltaico de alta eficiência, 144 células half-cut. Garantia de 12 anos.','Módulo fotovoltaico de alta eficiência, 144 células half-cut. Garantia de 12 anos.

Especificações
• Marca: SolarPrime
• Código (SKU): SOL-PAIN-550
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','SolarPrime',799.0,NULL,520,1,1,0),
(8,'SOL-MICRO-15','Microinversor Solar 1,5 kW','microinversor-solar-1-5-kw-solmicro15','Microinversor on-grid com monitoramento Wi-Fi e proteção IP67.','Microinversor on-grid com monitoramento Wi-Fi e proteção IP67.

Especificações
• Marca: SolarPrime
• Código (SKU): SOL-MICRO-15
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','SolarPrime',1490.0,NULL,990,0,1,0),
(8,'AUT-INT-WIFI','Interruptor Inteligente Wi-Fi 1 Canal','interruptor-inteligente-wi-fi-1-canal-autintwifi','Controle luzes pelo celular ou por voz. Compatível com Alexa e Google Home.','Controle luzes pelo celular ou por voz. Compatível com Alexa e Google Home.

Especificações
• Marca: Smartly
• Código (SKU): AUT-INT-WIFI
• Produto novo, lacrado, com nota fiscal.
• Instalação recomendada por profissional habilitado (NBR 5410).','Smartly',69.9,NULL,33,0,1,0);

INSERT INTO imagens_produtos (produto_id, caminho, alt, principal, ordem)
SELECT id, CONCAT('uploads/produtos/', sku, '.svg'), nome, 1, 1 FROM produtos;

INSERT INTO estoque (produto_id, quantidade, estoque_minimo)
SELECT id, CASE sku WHEN 'CAB-FLEX-25-100' THEN 40 WHEN 'CAB-FLEX-40-100' THEN 22 WHEN 'CAB-PP-3X15-50' THEN 15 WHEN 'DIS-MONO-32' THEN 120 WHEN 'DIS-BI-40' THEN 60 WHEN 'DR-25-30' THEN 18 WHEN 'DPS-45KA' THEN 4 WHEN 'QD-12-EMB' THEN 25 WHEN 'QD-18-SOB' THEN 14 WHEN 'QD-BAR-12' THEN 80 WHEN 'LED-BULB-12W' THEN 300 WHEN 'LED-PAN-18' THEN 90 WHEN 'LED-REF-50' THEN 35 WHEN 'LED-FITA-5M' THEN 28 WHEN 'TOM-2P-10A' THEN 400 WHEN 'INT-SIMP' THEN 350 WHEN 'TOM-USB-DUPLA' THEN 45 WHEN 'ELE-COR-34' THEN 30 WHEN 'ELE-FITA-ISOL' THEN 500 WHEN 'ELE-ABR-200' THEN 150 WHEN 'FER-ALIC-8' THEN 40 WHEN 'FER-MULTI' THEN 20 WHEN 'FER-KIT-6' THEN 12 WHEN 'SOL-PAIN-550' THEN 10 WHEN 'SOL-MICRO-15' THEN 6 WHEN 'AUT-INT-WIFI' THEN 50 ELSE 10 END,
       CASE sku WHEN 'CAB-FLEX-25-100' THEN 8 WHEN 'CAB-FLEX-40-100' THEN 5 WHEN 'CAB-PP-3X15-50' THEN 4 WHEN 'DIS-MONO-32' THEN 30 WHEN 'DIS-BI-40' THEN 15 WHEN 'DR-25-30' THEN 5 WHEN 'DPS-45KA' THEN 5 WHEN 'QD-12-EMB' THEN 6 WHEN 'QD-18-SOB' THEN 4 WHEN 'QD-BAR-12' THEN 20 WHEN 'LED-BULB-12W' THEN 60 WHEN 'LED-PAN-18' THEN 20 WHEN 'LED-REF-50' THEN 8 WHEN 'LED-FITA-5M' THEN 6 WHEN 'TOM-2P-10A' THEN 80 WHEN 'INT-SIMP' THEN 80 WHEN 'TOM-USB-DUPLA' THEN 10 WHEN 'ELE-COR-34' THEN 8 WHEN 'ELE-FITA-ISOL' THEN 100 WHEN 'ELE-ABR-200' THEN 30 WHEN 'FER-ALIC-8' THEN 8 WHEN 'FER-MULTI' THEN 5 WHEN 'FER-KIT-6' THEN 4 WHEN 'SOL-PAIN-550' THEN 3 WHEN 'SOL-MICRO-15' THEN 2 WHEN 'AUT-INT-WIFI' THEN 10 ELSE 5 END
FROM produtos;

INSERT INTO servicos (categoria_id, nome, slug, descricao, tipo_preco, preco_inicial, tempo_estimado_min)
VALUES
((SELECT id FROM categorias_servicos WHERE slug='instalacoes-residenciais'),'Instalação de tomadas e interruptores','instalacao-de-tomadas-e-interruptores','Instalação ou troca de pontos de tomada e interruptores, com teste de funcionamento e organização dos cabos. Material não incluso.','preco_inicial',80,60),
((SELECT id FROM categorias_servicos WHERE slug='instalacoes-residenciais'),'Instalação de chuveiro elétrico','instalacao-de-chuveiro-eletrico','Instalação segura de chuveiro elétrico com verificação da fiação, disjuntor e aterramento conforme a NBR 5410.','preco_inicial',120,90),
((SELECT id FROM categorias_servicos WHERE slug='instalacoes-residenciais'),'Instalação de ventilador de teto','instalacao-de-ventilador-de-teto','Montagem e ligação de ventilador de teto com ou sem luminária, incluindo balanceamento e teste.','preco_inicial',110,90),
((SELECT id FROM categorias_servicos WHERE slug='instalacoes-comerciais'),'Projeto e instalação elétrica comercial','projeto-e-instalacao-eletrica-comercial','Levantamento de carga, projeto, instalação e entrega de laudo para lojas, escritórios e galpões.','sob_orcamento',NULL,NULL),
((SELECT id FROM categorias_servicos WHERE slug='manutencao'),'Manutenção elétrica preventiva e corretiva','manutencao-eletrica-preventiva-e-corretiva','Inspeção de quadros, medição de correntes, reaperto de conexões e correção de falhas em residências e empresas.','preco_inicial',150,120),
((SELECT id FROM categorias_servicos WHERE slug='iluminacao-servicos'),'Projeto de iluminação LED','projeto-de-iluminacao-led','Planejamento e instalação de iluminação LED residencial ou comercial com foco em conforto e economia de energia.','sob_orcamento',NULL,NULL),
((SELECT id FROM categorias_servicos WHERE slug='quadros-eletricos'),'Troca e adequação de quadro de distribuição','troca-e-adequacao-de-quadro-de-distribuicao','Substituição de quadro antigo, instalação de DR e DPS, identificação dos circuitos e balanceamento de cargas.','sob_orcamento',NULL,NULL),
((SELECT id FROM categorias_servicos WHERE slug='diagnostico-tecnico'),'Diagnóstico de curto-circuito e queda de energia','diagnostico-de-curto-circuito-e-queda-de-energia','Localização da origem de desarmes, choques e fugas de corrente com equipamentos de medição, com relatório técnico.','preco_inicial',180,120);

INSERT INTO cupons (codigo, descricao, tipo, valor, valor_minimo_pedido, limite_por_cliente)
VALUES ('BEMVINDO10', '10% de desconto na primeira compra (pedido mínimo R$ 100)', 'percentual', 10, 100, 1);
