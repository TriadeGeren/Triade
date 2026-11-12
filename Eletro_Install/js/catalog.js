/* ==========================================================================
   ELECTRO INSTALL — catálogo de serviços (preços de mercado, mão de obra)
   Fonte única de dados usada por produtos.html, index.html e carrinho.html
   ========================================================================== */

const CATALOG = [
  {
    id: "split-12k",
    cat: "climatizacao",
    catLabel: "Climatização",
    icon: "❄️",
    name: "Instalação de Ar-Condicionado Split até 12.000 BTUs",
    desc: "Instalação completa de unidade evaporadora e condensadora, com fixação de suportes e teste de gás.",
    specs: ["Até 3m de tubulação inclusos", "Voltagem: 127V/220V", "Garantia: 90 dias"],
    price: 380
  },
  {
    id: "split-18-24k",
    cat: "climatizacao",
    catLabel: "Climatização",
    icon: "❄️",
    name: "Instalação de Ar-Condicionado Split 18.000 a 24.000 BTUs",
    desc: "Instalação de splits de maior capacidade, incluindo suporte reforçado para condensadora.",
    specs: ["Até 3m de tubulação inclusos", "Voltagem: 220V", "Garantia: 90 dias"],
    price: 480
  },
  {
    id: "split-manutencao",
    cat: "climatizacao",
    catLabel: "Climatização",
    icon: "🧰",
    name: "Manutenção e Higienização de Ar-Condicionado Split",
    desc: "Limpeza de filtros, serpentina e dreno, com checagem de gás e desempenho.",
    specs: ["Duração média: 1h", "Recomendado a cada 6 meses"],
    price: 150
  },
  {
    id: "ventilador-teto",
    cat: "ventilacao",
    catLabel: "Ventilação",
    icon: "🌀",
    name: "Instalação de Ventilador de Teto",
    desc: "Fixação de gancho, montagem das pás e instalação elétrica com controle de velocidade.",
    specs: ["Requer laje ou forro resistente", "Garantia: 90 dias"],
    price: 110
  },
  {
    id: "exaustor",
    cat: "ventilacao",
    catLabel: "Ventilação",
    icon: "💨",
    name: "Instalação de Exaustor",
    desc: "Instalação de exaustor de parede ou teto para banheiro ou cozinha, com ligação elétrica.",
    specs: ["Inclui furação para duto", "Garantia: 90 dias"],
    price: 95
  },
  {
    id: "lustre",
    cat: "iluminacao",
    catLabel: "Iluminação",
    icon: "💡",
    name: "Instalação de Lustre / Pendente",
    desc: "Fixação e ligação elétrica de lustres, pendentes e luminárias decorativas.",
    specs: ["Altura padrão de pé-direito", "Garantia: 60 dias"],
    price: 90
  },
  {
    id: "spot-embutido",
    cat: "iluminacao",
    catLabel: "Iluminação",
    icon: "🔆",
    name: "Instalação de Spot / Luminária de Embutir (unidade)",
    desc: "Corte, fixação e ligação de spots de embutir em gesso ou laje.",
    specs: ["Preço por unidade", "Pedido mínimo: 4 unidades"],
    price: 45
  },
  {
    id: "arandela",
    cat: "iluminacao",
    catLabel: "Iluminação",
    icon: "🕯️",
    name: "Instalação de Arandela",
    desc: "Instalação de arandelas de parede com fiação embutida ou aparente.",
    specs: ["Garantia: 60 dias"],
    price: 60
  },
  {
    id: "chuveiro-eletrico",
    cat: "aquecimento",
    catLabel: "Aquecimento",
    icon: "🚿",
    name: "Instalação de Chuveiro Elétrico",
    desc: "Substituição ou instalação de chuveiro elétrico com checagem de fiação e disjuntor.",
    specs: ["Recomendado disjuntor exclusivo", "Garantia: 90 dias"],
    price: 85
  },
  {
    id: "aquecedor-boiler",
    cat: "aquecimento",
    catLabel: "Aquecimento",
    icon: "🔥",
    name: "Instalação de Aquecedor Elétrico / Boiler",
    desc: "Instalação de boiler elétrico de acumulação com dimensionamento de circuito dedicado.",
    specs: ["Requer ponto elétrico dedicado", "Garantia: 90 dias"],
    price: 250
  },
  {
    id: "quadro-12",
    cat: "quadros",
    catLabel: "Quadros e Painéis",
    icon: "🧯",
    name: "Instalação de Quadro de Disjuntores (até 12 circuitos)",
    desc: "Montagem de quadro, distribuição de disjuntores e organização de fiação.",
    specs: ["Inclui barramento e DR", "Garantia: 180 dias"],
    price: 320
  },
  {
    id: "quadro-24",
    cat: "quadros",
    catLabel: "Quadros e Painéis",
    icon: "🧯",
    name: "Instalação de Quadro de Disjuntores (até 24 circuitos)",
    desc: "Montagem de quadro de maior porte para residências com muitos circuitos.",
    specs: ["Inclui barramento e DR", "Garantia: 180 dias"],
    price: 480
  },
  {
    id: "aterramento",
    cat: "quadros",
    catLabel: "Quadros e Painéis",
    icon: "⏚",
    name: "Instalação de Aterramento Residencial",
    desc: "Instalação de haste de aterramento e integração ao quadro de distribuição.",
    specs: ["Conforme NBR 5410", "Garantia: 180 dias"],
    price: 350
  },
  {
    id: "camera-seguranca",
    cat: "seguranca",
    catLabel: "Segurança e Automação",
    icon: "📷",
    name: "Instalação de Câmera de Segurança (unidade)",
    desc: "Fixação, passagem de cabo e configuração básica de câmera IP ou analógica.",
    specs: ["Preço por unidade", "Configuração de app inclusa"],
    price: 140
  },
  {
    id: "interfone",
    cat: "seguranca",
    catLabel: "Segurança e Automação",
    icon: "☎️",
    name: "Instalação de Interfone",
    desc: "Instalação de unidade interna e externa com fiação e testes de comunicação.",
    specs: ["Garantia: 90 dias"],
    price: 180
  },
  {
    id: "motor-portao",
    cat: "seguranca",
    catLabel: "Segurança e Automação",
    icon: "🚪",
    name: "Instalação de Motor de Portão Eletrônico",
    desc: "Instalação de motor basculante ou deslizante, com programação de controles.",
    specs: ["2 controles inclusos", "Garantia: 180 dias"],
    price: 420
  },
  {
    id: "sensor-presenca",
    cat: "seguranca",
    catLabel: "Segurança e Automação",
    icon: "🛰️",
    name: "Instalação de Sensor de Presença",
    desc: "Instalação de sensor de presença para iluminação automática de ambientes.",
    specs: ["Garantia: 90 dias"],
    price: 70
  },
  {
    id: "ponto-tomada",
    cat: "tomadas",
    catLabel: "Tomadas e Pontos",
    icon: "🔌",
    name: "Instalação de Ponto de Tomada Novo",
    desc: "Abertura de rasgo, passagem de fiação e instalação de nova tomada 127V/220V.",
    specs: ["Preço por ponto", "Acabamento em massa não incluso"],
    price: 95
  },
  {
    id: "tomada-220",
    cat: "tomadas",
    catLabel: "Tomadas e Pontos",
    icon: "🔌",
    name: "Instalação de Tomada 220V (Ar-Condicionado / Forno)",
    desc: "Instalação de ponto de força dedicado para equipamentos de maior consumo.",
    specs: ["Inclui disjuntor exclusivo", "Garantia: 90 dias"],
    price: 120
  },
  {
    id: "troca-tomada",
    cat: "tomadas",
    catLabel: "Tomadas e Pontos",
    icon: "🔧",
    name: "Troca de Tomada / Interruptor",
    desc: "Substituição de tomadas e interruptores danificados ou desatualizados.",
    specs: ["Preço por unidade", "Garantia: 60 dias"],
    price: 40
  },
  {
    id: "padrao-entrada",
    cat: "padrao",
    catLabel: "Padrão de Energia",
    icon: "⚡",
    name: "Instalação de Padrão de Entrada de Energia",
    desc: "Montagem de padrão de entrada conforme normas da concessionária local.",
    specs: ["Projeto sujeito a vistoria", "Garantia: 180 dias"],
    price: 650
  },
  {
    id: "aumento-carga",
    cat: "padrao",
    catLabel: "Padrão de Energia",
    icon: "⚡",
    name: "Aumento de Carga / Padrão Trifásico",
    desc: "Adequação de padrão monofásico para trifásico ou aumento de carga contratada.",
    specs: ["Requer projeto técnico", "Garantia: 180 dias"],
    price: 900
  }
];

const CATEGORIES = [
  { id: "todos", label: "Todos" },
  { id: "climatizacao", label: "Climatização" },
  { id: "ventilacao", label: "Ventilação" },
  { id: "iluminacao", label: "Iluminação" },
  { id: "aquecimento", label: "Aquecimento" },
  { id: "quadros", label: "Quadros e Painéis" },
  { id: "seguranca", label: "Segurança e Automação" },
  { id: "tomadas", label: "Tomadas e Pontos" },
  { id: "padrao", label: "Padrão de Energia" }
];

function formatBRL(value){
  return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

/* ==========================================================================
   Catálogo editável — o admin.html grava alterações aqui.
   Todas as páginas devem usar getCatalog() em vez de CATALOG diretamente,
   para já enxergarem serviços criados/editados/excluídos pelo admin.
   ========================================================================== */
const CATALOG_KEY = "electroinstall_catalog";

function getCatalog(){
  try{
    const raw = localStorage.getItem(CATALOG_KEY);
    if(raw){
      const parsed = JSON.parse(raw);
      if(Array.isArray(parsed)) return parsed;
    }
  }catch(e){
    console.warn("Catálogo salvo inválido, usando o catálogo padrão.", e);
  }
  return CATALOG;
}

function saveCatalog(list){
  localStorage.setItem(CATALOG_KEY, JSON.stringify(list));
}
