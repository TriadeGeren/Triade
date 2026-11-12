// =========================================================
// Studio Verso — carrinho (localStorage), drawer, interações
// =========================================================
const CHAVE_CARRINHO = 'studioverso_carrinho';

function lerCarrinho() {
  try {
    return JSON.parse(localStorage.getItem(CHAVE_CARRINHO)) || [];
  } catch {
    return [];
  }
}

function salvarCarrinho(itens) {
  localStorage.setItem(CHAVE_CARRINHO, JSON.stringify(itens));
  renderizarCarrinho();
}

// Identifica o item: produto comum = id; produto personalizado = id + código da personalização
function chaveItem(i) { return i.id + ':' + (i.pers || ''); }

function esc(t) {
  return String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function adicionarAoCarrinho(produto) {
  const itens = lerCarrinho();
  // Item personalizado é sempre único (1 peça, com as suas escolhas). Item comum soma a quantidade.
  const existente = produto.pers ? null : itens.find(i => !i.pers && i.id === produto.id);
  if (existente) {
    existente.qtd += 1;
  } else {
    itens.push({ ...produto, qtd: 1 });
  }
  salvarCarrinho(itens);
  abrirCarrinho();
}

function alterarQtd(chave, delta) {
  let itens = lerCarrinho();
  itens = itens
    .map(i => chaveItem(i) === chave && !i.pers ? { ...i, qtd: i.qtd + delta } : i)
    .filter(i => i.qtd > 0);
  salvarCarrinho(itens);
}

function removerItem(chave) {
  salvarCarrinho(lerCarrinho().filter(i => chaveItem(i) !== chave));
}

function formatarPreco(valor) {
  return 'R$ ' + valor.toFixed(2).replace('.', ',');
}

function renderizarCarrinho() {
  const itens = lerCarrinho();
  const lista = document.getElementById('listaCarrinho');
  const subtotalEl = document.getElementById('subtotalCarrinho');
  const badge = document.getElementById('qtdCarrinho');
  if (!lista) return;

  const qtdTotal = itens.reduce((s, i) => s + i.qtd, 0);
  badge.textContent = qtdTotal;

  if (itens.length === 0) {
    lista.innerHTML = '<p style="color:var(--tinta-suave)">Seu carrinho está vazio.</p>';
    subtotalEl.textContent = formatarPreco(0);
    return;
  }

  lista.innerHTML = itens.map(item => {
    const chave = esc(chaveItem(item));
    const controles = item.pers
      ? `<button class="remover" onclick="removerItem('${chave}')">remover</button>`
      : `<div class="qtd">
          <button onclick="alterarQtd('${chave}', -1)">−</button>
          <span>${item.qtd}</span>
          <button onclick="alterarQtd('${chave}', 1)">+</button>
        </div>`;
    return `
    <div class="item-carrinho">
      <img src="${esc(item.imagem)}" alt="${esc(item.nome)}">
      <div style="flex:1">
        <strong>${esc(item.nome)}</strong>
        ${item.resumo ? `<div class="resumo">${esc(item.resumo)}</div>` : ''}
        <div>${formatarPreco(item.preco)}</div>
        ${controles}
      </div>
    </div>`;
  }).join('');

  const subtotal = itens.reduce((s, i) => s + i.qtd * i.preco, 0);
  subtotalEl.textContent = formatarPreco(subtotal);
}

function abrirCarrinho() {
  document.getElementById('drawerCarrinho')?.classList.add('aberto');
  document.getElementById('overlay')?.classList.add('aberto');
}
function fecharCarrinho() {
  document.getElementById('drawerCarrinho')?.classList.remove('aberto');
  document.getElementById('overlay')?.classList.remove('aberto');
}

document.addEventListener('DOMContentLoaded', () => {
  renderizarCarrinho();

  document.getElementById('btnAbrirCarrinho')?.addEventListener('click', abrirCarrinho);
  document.getElementById('btnFecharCarrinho')?.addEventListener('click', fecharCarrinho);
  document.getElementById('overlay')?.addEventListener('click', fecharCarrinho);

  document.querySelectorAll('.btn-add-carrinho').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.hasAttribute('disabled')) return;
      adicionarAoCarrinho({
        id: parseInt(btn.dataset.id, 10),
        nome: btn.dataset.nome,
        preco: parseFloat(btn.dataset.preco),
        imagem: btn.dataset.imagem,
      });
    });
  });
});
