/* ==========================================================================
   ELECTRO INSTALL — painel administrativo
   Tudo é salvo no localStorage do navegador (não há backend/servidor).
   ========================================================================== */

document.addEventListener("DOMContentLoaded", () => {
  const session = getSession();
  if(session){
    document.getElementById("admin-welcome").textContent =
      `Bem-vindo(a), ${session.name}. Gerencie o catálogo de serviços e acompanhe os pedidos recebidos.`;
  }

  /* ---------- abas ---------- */
  const tabButtons = document.querySelectorAll(".admin-tabs button");
  tabButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      tabButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      document.querySelectorAll(".admin-panel").forEach(p => p.classList.remove("active"));
      document.getElementById("tab-" + btn.dataset.tab).classList.add("active");
    });
  });

  /* ---------- formulário de novo serviço ---------- */
  const catSelect = document.getElementById("ns-categoria");
  CATEGORIES.filter(c => c.id !== "todos").forEach(c => {
    const opt = document.createElement("option");
    opt.value = c.id;
    opt.textContent = c.label;
    catSelect.appendChild(opt);
  });

  const formNovoServico = document.getElementById("form-novo-servico");

  document.getElementById("btn-novo-servico").addEventListener("click", () => {
    formNovoServico.style.display = "grid";
  });

  document.getElementById("btn-cancelar-servico").addEventListener("click", () => {
    formNovoServico.reset();
    formNovoServico.style.display = "none";
  });

  formNovoServico.addEventListener("submit", (e) => {
    e.preventDefault();
    const catId = document.getElementById("ns-categoria").value;
    const cat = CATEGORIES.find(c => c.id === catId);

    const novoServico = {
      id: "servico-" + Date.now(),
      cat: catId,
      catLabel: cat ? cat.label : catId,
      icon: document.getElementById("ns-icone").value.trim() || "🔧",
      name: document.getElementById("ns-nome").value.trim(),
      desc: document.getElementById("ns-desc").value.trim(),
      specs: [],
      price: parseFloat(document.getElementById("ns-preco").value) || 0
    };

    const catalog = getCatalog();
    catalog.push(novoServico);
    saveCatalog(catalog);

    formNovoServico.reset();
    formNovoServico.style.display = "none";
    renderServicos();
    showToast("Serviço adicionado ao catálogo");
  });

  /* ---------- pedidos ---------- */
  document.getElementById("btn-limpar-pedidos").addEventListener("click", () => {
    if(confirm("Remover todos os pedidos registrados? Esta ação não pode ser desfeita.")){
      localStorage.setItem(ORDERS_KEY, JSON.stringify([]));
      renderPedidos();
    }
  });

  renderServicos();
  renderPedidos();
});

/* ---------- tabela: serviços ---------- */
function renderServicos(){
  const tbody = document.getElementById("tbody-servicos");
  const catalog = getCatalog();

  if(catalog.length === 0){
    tbody.innerHTML = `<tr class="empty-row"><td colspan="5">Nenhum serviço cadastrado.</td></tr>`;
    return;
  }

  tbody.innerHTML = catalog.map(item => `
    <tr data-id="${item.id}">
      <td style="font-size:18px;">${item.icon}</td>
      <td><input type="text" value="${item.name}" data-field="name"></td>
      <td>${item.catLabel}</td>
      <td class="cell-price"><input type="number" value="${item.price}" min="0" step="0.01" data-field="price"></td>
      <td class="cell-actions">
        <button class="icon-btn" title="Salvar alterações" onclick="salvarServico('${item.id}')">💾</button>
        <button class="icon-btn" title="Excluir" onclick="excluirServico('${item.id}')">🗑️</button>
      </td>
    </tr>
  `).join("");
}

function salvarServico(id){
  const row = document.querySelector(`tr[data-id="${id}"]`);
  const catalog = getCatalog();
  const item = catalog.find(p => p.id === id);
  if(!item || !row) return;

  const novoNome = row.querySelector('[data-field="name"]').value.trim();
  const novoPreco = parseFloat(row.querySelector('[data-field="price"]').value);

  if(novoNome) item.name = novoNome;
  if(!isNaN(novoPreco)) item.price = novoPreco;

  saveCatalog(catalog);
  showToast("Serviço atualizado");
}

function excluirServico(id){
  if(!confirm("Excluir este serviço do catálogo?")) return;
  const catalog = getCatalog().filter(p => p.id !== id);
  saveCatalog(catalog);
  renderServicos();
  showToast("Serviço excluído");
}

/* ---------- tabela: pedidos ---------- */
function renderPedidos(){
  const tbody = document.getElementById("tbody-pedidos");
  const orders = getOrders();

  if(orders.length === 0){
    tbody.innerHTML = `<tr class="empty-row"><td colspan="8">Nenhum pedido registrado ainda.</td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(o => `
    <tr>
      <td class="mono">${o.id}</td>
      <td>${new Date(o.date).toLocaleString("pt-BR")}</td>
      <td>${o.customerName}</td>
      <td>${o.customerAddress}</td>
      <td>${o.items.length} ite${o.items.length === 1 ? "m" : "ns"}</td>
      <td>${formatBRL(o.total)}</td>
      <td><span class="badge-status ${o.status}">${o.status}</span></td>
      <td class="cell-actions">
        ${o.status !== "concluido" ? `<button class="icon-btn" title="Marcar como concluído" onclick="concluirPedido('${o.id}')">✅</button>` : ""}
        <button class="icon-btn" title="Excluir" onclick="excluirPedido('${o.id}')">🗑️</button>
      </td>
    </tr>
  `).join("");
}

function concluirPedido(id){
  const orders = getOrders();
  const order = orders.find(o => o.id === id);
  if(order) order.status = "concluido";
  localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
  renderPedidos();
}

function excluirPedido(id){
  const orders = getOrders().filter(o => o.id !== id);
  localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
  renderPedidos();
  showToast("Pedido excluído");
}
