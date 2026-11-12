/* =========================================================
   GELACIONADO — script.js
   Catálogo (localStorage), Carrinho (localStorage),
   Painel Admin (CRUD) e integração com WhatsApp.
   ========================================================= */

(function () {
  "use strict";

  /* ---------------------------------------------------------
     CONSTANTES E CHAVES DE STORAGE
  --------------------------------------------------------- */
  const LS_PRODUCTS = "gelacionado_products";
  const LS_CART = "gelacionado_cart";
  const LS_ADMIN_SESSION = "gelacionado_admin_session";
  const WHATSAPP_NUMBER = "5585991025669"; // (85) 9 9102-5669
  const ADMIN_PASSWORD = "gelacionado2025"; // gate simples, apenas client-side

  /* Catálogo padrão de fábrica — usado na primeira visita para
     popular o localStorage. O admin pode editar/remover/adicionar
     livremente a partir daí. */
  const DEFAULT_PRODUCTS = [
    {
      id: "srv-001",
      category: "servico",
      name: "Instalação de Split Inverter",
      description:
        "Instalação completa de ar-condicionado split inverter até 12.000 BTUs, incluindo suporte, dreno e teste de carga de gás.",
      price: 480,
      icon: "❄️",
      specs: [
        { l: "Capacidade", v: "até 12.000 BTUs" },
        { l: "Prazo médio", v: "1 dia útil" },
      ],
    },
    {
      id: "srv-002",
      category: "servico",
      name: "Instalação Split Inverter 18k–24k",
      description:
        "Instalação de equipamentos de maior capacidade, com fixação reforçada de condensadora e tubulação de cobre isolada.",
      price: 690,
      icon: "❄️",
      specs: [
        { l: "Capacidade", v: "18.000 a 24.000 BTUs" },
        { l: "Prazo médio", v: "1 dia útil" },
      ],
    },
    {
      id: "srv-003",
      category: "servico",
      name: "Higienização Completa",
      description:
        "Limpeza profunda de evaporadora, condensadora, filtros e dreno, eliminando fungos, bactérias e maus odores.",
      price: 150,
      icon: "🧽",
      specs: [
        { l: "Duração", v: "40 a 60 min" },
        { l: "Recomendado", v: "a cada 6 meses" },
      ],
    },
    {
      id: "srv-004",
      category: "servico",
      name: "Manutenção Preventiva",
      description:
        "Checagem geral de gás, elétrica, pressão e desempenho para evitar falhas e prolongar a vida útil do equipamento.",
      price: 190,
      icon: "🛠️",
      specs: [
        { l: "Inclui", v: "gás + elétrica" },
        { l: "Recomendado", v: "anual" },
      ],
    },
    {
      id: "srv-005",
      category: "servico",
      name: "Recarga de Gás Refrigerante",
      description:
        "Identificação de vazamento, vácuo do sistema e recarga de gás R410A/R22 conforme especificação do fabricante.",
      price: 260,
      icon: "🌡️",
      specs: [
        { l: "Gás", v: "R410A / R22" },
        { l: "Garantia", v: "90 dias" },
      ],
    },
    {
      id: "srv-006",
      category: "servico",
      name: "Instalação de VRF / Multi-Split",
      description:
        "Projeto e instalação de sistemas multi-split ou VRF para climatização de múltiplos ambientes com uma única condensadora.",
      price: 1450,
      icon: "🏢",
      specs: [
        { l: "Ambientes", v: "3 a 6 evaporadoras" },
        { l: "Prazo médio", v: "2 a 3 dias" },
      ],
    },
    {
      id: "pnl-001",
      category: "painel",
      name: "Painel Frost One",
      description:
        "Central de controle touch para climatização, com agendamento por ambiente e leitura de temperatura em tempo real.",
      price: 890,
      icon: "🎛️",
      specs: [
        { l: "Tela", v: "4.3\" touch capacitiva" },
        { l: "Conectividade", v: "Wi-Fi 2.4GHz" },
        { l: "Integrações", v: "Alexa / Google Home" },
      ],
    },
    {
      id: "pnl-002",
      category: "painel",
      name: "Painel Frost Pro Multi-Zona",
      description:
        "Controle centralizado de até 8 zonas de climatização e iluminação, com cenas automáticas e sensor de presença.",
      price: 1690,
      icon: "🧠",
      specs: [
        { l: "Zonas", v: "até 8" },
        { l: "Sensores", v: "presença + luminosidade" },
        { l: "App", v: "iOS / Android" },
      ],
    },
    {
      id: "pnl-003",
      category: "painel",
      name: "Sensor Inteligente de Ambiente",
      description:
        "Sensor compacto de temperatura, umidade e qualidade do ar que alimenta automações do painel e alerta variações.",
      price: 320,
      icon: "📡",
      specs: [
        { l: "Mede", v: "temp. / umidade / CO₂" },
        { l: "Alcance", v: "até 40m do painel" },
      ],
    },
    {
      id: "pnl-004",
      category: "painel",
      name: "Kit Automação Comercial",
      description:
        "Solução para lojas e escritórios: controle remoto de climatização por setor, relatórios de consumo e acesso multiusuário.",
      price: 2450,
      icon: "🏬",
      specs: [
        { l: "Usuários", v: "ilimitados" },
        { l: "Relatórios", v: "consumo mensal" },
        { l: "Suporte", v: "prioritário" },
      ],
    },
  ];

  /* ---------------------------------------------------------
     STORAGE HELPERS
  --------------------------------------------------------- */
  function readJSON(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      console.error("Erro ao ler localStorage:", key, e);
      return fallback;
    }
  }

  function writeJSON(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
      console.error("Erro ao gravar localStorage:", key, e);
    }
  }

  function getProducts() {
    let products = readJSON(LS_PRODUCTS, null);
    if (!products) {
      products = DEFAULT_PRODUCTS;
      writeJSON(LS_PRODUCTS, products);
    }
    return products;
  }

  function saveProducts(products) {
    writeJSON(LS_PRODUCTS, products);
  }

  function getCart() {
    return readJSON(LS_CART, []);
  }

  function saveCart(cart) {
    writeJSON(LS_CART, cart);
    updateCartBadge();
  }

  function formatBRL(value) {
    return value.toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL",
    });
  }

  function uid(prefix) {
    return (
      prefix + "-" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6)
    );
  }

  /* ---------------------------------------------------------
     CARRINHO — API pública usada pelas páginas
  --------------------------------------------------------- */
  function addToCart(productId) {
    const product = getProducts().find((p) => p.id === productId);
    if (!product) return;

    const cart = getCart();
    const existing = cart.find((i) => i.id === productId);
    if (existing) {
      existing.qty += 1;
    } else {
      cart.push({
        id: product.id,
        name: product.name,
        category: product.category,
        price: product.price,
        icon: product.icon || "❄️",
        qty: 1,
      });
    }
    saveCart(cart);
    showToast(`"${product.name}" adicionado ao carrinho.`);
  }

  function updateCartQty(productId, delta) {
    const cart = getCart();
    const item = cart.find((i) => i.id === productId);
    if (!item) return;
    item.qty += delta;
    const newCart = item.qty <= 0 ? cart.filter((i) => i.id !== productId) : cart;
    saveCart(newCart);
    if (typeof window.__renderCartPage === "function") window.__renderCartPage();
  }

  function removeFromCart(productId) {
    const cart = getCart().filter((i) => i.id !== productId);
    saveCart(cart);
    if (typeof window.__renderCartPage === "function") window.__renderCartPage();
  }

  function cartCount() {
    return getCart().reduce((sum, i) => sum + i.qty, 0);
  }

  function cartTotal() {
    return getCart().reduce((sum, i) => sum + i.qty * i.price, 0);
  }

  function updateCartBadge() {
    document.querySelectorAll("[data-cart-count]").forEach((el) => {
      const count = cartCount();
      el.textContent = count;
      el.style.display = count > 0 ? "flex" : "none";
    });
  }

  /* ---------------------------------------------------------
     TOAST DE FEEDBACK
  --------------------------------------------------------- */
  let toastTimer = null;
  function showToast(message) {
    let toast = document.querySelector(".toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "toast";
      toast.innerHTML = '<span class="dot"></span><span class="toast-msg"></span>';
      document.body.appendChild(toast);
    }
    toast.querySelector(".toast-msg").textContent = message;
    toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove("show"), 3200);
  }

  /* ---------------------------------------------------------
     RENDERIZAÇÃO DE CATÁLOGO (servicos.html / paineis-inteligentes.html)
  --------------------------------------------------------- */
  function renderCatalog(containerSelector, category) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    function draw(filter) {
      const products = getProducts().filter((p) => p.category === category);
      const filtered =
        filter && filter !== "todos"
          ? products.filter((p) => p.id.includes(filter))
          : products;

      if (products.length === 0) {
        container.innerHTML =
          '<div class="empty-state">Nenhum item cadastrado nesta categoria no momento.</div>';
        return;
      }

      container.innerHTML = products
        .map(
          (p) => `
        <article class="product-card">
          <div class="product-media">${p.icon || "❄️"}</div>
          <div class="product-body">
            <span class="product-tag">${
              category === "servico" ? "SERVIÇO" : "PAINEL INTELIGENTE"
            }</span>
            <h3>${escapeHTML(p.name)}</h3>
            <p class="desc">${escapeHTML(p.description)}</p>
            ${
              p.specs && p.specs.length
                ? `<ul class="product-specs">${p.specs
                    .map(
                      (s) =>
                        `<li><span>${escapeHTML(s.l)}</span><span>${escapeHTML(
                          s.v
                        )}</span></li>`
                    )
                    .join("")}</ul>`
                : ""
            }
            <div class="product-footer">
              <div class="product-price">${formatBRL(p.price)}<small>valor estimado</small></div>
              <button class="btn btn-primary btn-sm" data-add-to-cart="${p.id}">Adicionar</button>
            </div>
          </div>
        </article>`
        )
        .join("");

      container.querySelectorAll("[data-add-to-cart]").forEach((btn) => {
        btn.addEventListener("click", () => addToCart(btn.getAttribute("data-add-to-cart")));
      });
    }

    draw();
    window.__redrawCatalog = draw;
  }

  function escapeHTML(str) {
    const div = document.createElement("div");
    div.textContent = str == null ? "" : String(str);
    return div.innerHTML;
  }

  /* ---------------------------------------------------------
     PÁGINA DO CARRINHO (carrinho.html)
  --------------------------------------------------------- */
  function initCartPage() {
    const listEl = document.querySelector("[data-cart-list]");
    const totalEl = document.querySelector("[data-cart-total]");
    const subtotalEl = document.querySelector("[data-cart-subtotal]");
    const countEl = document.querySelector("[data-cart-item-count]");
    const form = document.querySelector("#cart-form");
    const waBtn = document.querySelector("#whatsapp-checkout");
    if (!listEl) return;

    function render() {
      const cart = getCart();

      if (cart.length === 0) {
        listEl.innerHTML =
          '<div class="empty-state">Seu carrinho está vazio.<br><br><a class="btn btn-ghost btn-sm" href="servicos.php">Ver serviços</a></div>';
      } else {
        listEl.innerHTML = cart
          .map(
            (item) => `
          <div class="cart-item">
            <div class="cart-item-thumb">${item.icon || "❄️"}</div>
            <div>
              <div class="cart-item-name">${escapeHTML(item.name)}</div>
              <div class="cart-item-cat">${
                item.category === "servico" ? "Serviço" : "Painel inteligente"
              } · ${formatBRL(item.price)} un.</div>
            </div>
            <div class="cart-item-qty">
              <button type="button" data-qty-minus="${item.id}" aria-label="Diminuir quantidade">−</button>
              <span>${item.qty}</span>
              <button type="button" data-qty-plus="${item.id}" aria-label="Aumentar quantidade">+</button>
            </div>
            <div class="cart-item-price">${formatBRL(item.price * item.qty)}</div>
            <button type="button" class="cart-item-remove" data-remove="${item.id}" aria-label="Remover item">✕</button>
          </div>`
          )
          .join("");
      }

      const total = cartTotal();
      const count = cartCount();
      if (totalEl) totalEl.textContent = formatBRL(total);
      if (subtotalEl) subtotalEl.textContent = formatBRL(total);
      if (countEl) countEl.textContent = count;
      if (waBtn) waBtn.disabled = cart.length === 0;

      listEl.querySelectorAll("[data-qty-plus]").forEach((b) =>
        b.addEventListener("click", () => updateCartQty(b.getAttribute("data-qty-plus"), 1))
      );
      listEl.querySelectorAll("[data-qty-minus]").forEach((b) =>
        b.addEventListener("click", () => updateCartQty(b.getAttribute("data-qty-minus"), -1))
      );
      listEl.querySelectorAll("[data-remove]").forEach((b) =>
        b.addEventListener("click", () => removeFromCart(b.getAttribute("data-remove")))
      );

      updateCartBadge();
    }

    window.__renderCartPage = render;
    render();

    if (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        handleWhatsAppCheckout(form);
      });
    }
  }

  function validateField(input) {
    const row = input.closest(".form-row");
    const isValid = input.checkValidity() && input.value.trim() !== "";
    if (row) row.classList.toggle("has-error", !isValid);
    return isValid;
  }

  function handleWhatsAppCheckout(form) {
    const statusEl = form.querySelector(".form-status") || document.querySelector("[data-checkout-status]");
    const cart = getCart();

    const nome = form.querySelector("#cliente-nome");
    const endereco = form.querySelector("#cliente-endereco");
    const ambiente = form.querySelector("#cliente-ambiente");
    const obs = form.querySelector("#cliente-obs");

    const fields = [nome, endereco, ambiente].filter(Boolean);
    let allValid = true;
    fields.forEach((f) => {
      if (!validateField(f)) allValid = false;
    });

    if (cart.length === 0) {
      setStatus(statusEl, "err", "Seu carrinho está vazio. Adicione um serviço ou painel antes de solicitar o orçamento.");
      return;
    }

    if (!allValid) {
      setStatus(statusEl, "err", "Preencha os campos obrigatórios destacados para continuar.");
      return;
    }

    const message = buildWhatsAppMessage({
      nome: nome.value.trim(),
      endereco: endereco.value.trim(),
      ambiente: ambiente.value,
      obs: obs ? obs.value.trim() : "",
      cart,
      total: cartTotal(),
    });

    const url = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
    setStatus(statusEl, "ok", "Pedido preparado! Abrindo o WhatsApp em uma nova aba...");
    window.open(url, "_blank", "noopener,noreferrer");
  }

  function setStatus(el, type, msg) {
    if (!el) return;
    el.textContent = msg;
    el.classList.remove("ok", "err");
    el.classList.add("show", type);
  }

  function buildWhatsAppMessage({ nome, endereco, ambiente, obs, cart, total }) {
    const linhas = cart
      .map((i) => `• ${i.qty}x ${i.name} — ${formatBRL(i.price)} (subtotal: ${formatBRL(i.price * i.qty)})`)
      .join("\n");

    return (
      `Olá, Gelacionado! Gostaria de solicitar um orçamento.\n\n` +
      `*Itens selecionados:*\n${linhas}\n\n` +
      `*Valor total estimado:* ${formatBRL(total)}\n\n` +
      `*Dados do cliente:*\n` +
      `Nome: ${nome}\n` +
      `Endereço/Bairro: ${endereco}\n` +
      `Tipo de ambiente: ${ambiente}\n` +
      (obs ? `Observações: ${obs}\n` : "") +
      `\nAguardo o contato, obrigado!`
    );
  }

  /* ---------------------------------------------------------
     FORMULÁRIO DE CONTATO (contato.html)
  --------------------------------------------------------- */
  function initContactForm() {
    const form = document.querySelector("#contact-form");
    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const statusEl = form.querySelector(".form-status");
      const nome = form.querySelector("#contato-nome");
      const telefone = form.querySelector("#contato-telefone");
      const mensagem = form.querySelector("#contato-mensagem");

      const fields = [nome, telefone, mensagem].filter(Boolean);
      let allValid = true;
      fields.forEach((f) => {
        if (!validateField(f)) allValid = false;
      });

      if (!allValid) {
        setStatus(statusEl, "err", "Preencha os campos destacados para enviarmos sua mensagem.");
        return;
      }

      const message =
        `Olá, Gelacionado! Vim pelo site institucional.\n\n` +
        `Nome: ${nome.value.trim()}\n` +
        `Telefone/Contato: ${telefone.value.trim()}\n\n` +
        `Mensagem: ${mensagem.value.trim()}`;

      const url = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
      setStatus(statusEl, "ok", "Mensagem pronta! Abrindo o WhatsApp em uma nova aba...");
      window.open(url, "_blank", "noopener,noreferrer");
      form.reset();
    });
  }

  /* ---------------------------------------------------------
     PAINEL ADMIN (admin.html)
  --------------------------------------------------------- */
  function initAdmin() {
    const gate = document.querySelector("#admin-login-gate");
    const shell = document.querySelector("#admin-shell");
    if (!gate || !shell) return;

    const loginForm = document.querySelector("#admin-login-form");
    const logoutBtn = document.querySelector("#admin-logout");
    const isLogged = sessionStorage.getItem(LS_ADMIN_SESSION) === "1";

    function showShell() {
      gate.style.display = "none";
      shell.style.display = "grid";
      if (logoutBtn) logoutBtn.style.display = "inline-flex";
      renderAdminTable();
      renderAdminStats();
      renderAdminOverview();
    }
    function showGate() {
      gate.style.display = "flex";
      shell.style.display = "none";
      if (logoutBtn) logoutBtn.style.display = "none";
    }

    if (isLogged) showShell();
    else showGate();

    if (loginForm) {
      loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const pass = loginForm.querySelector("#admin-pass").value;
        const errEl = loginForm.querySelector(".field-error-login");
        if (pass === ADMIN_PASSWORD) {
          sessionStorage.setItem(LS_ADMIN_SESSION, "1");
          showShell();
        } else if (errEl) {
          errEl.style.display = "block";
        }
      });
    }

    if (logoutBtn) {
      logoutBtn.addEventListener("click", function () {
        sessionStorage.removeItem(LS_ADMIN_SESSION);
        showGate();
      });
    }

    /* Modal de produto (criar/editar) */
    const modal = document.querySelector("#product-modal");
    const modalForm = document.querySelector("#product-form");
    const newBtn = document.querySelector("#admin-new-product");
    const closeBtns = document.querySelectorAll("[data-modal-close]");

    function openModal(product) {
      if (!modal) return;
      modal.classList.add("show");
      modalForm.reset();
      modalForm.querySelector("#p-id").value = product ? product.id : "";
      modalForm.querySelector("#p-category").value = product ? product.category : "servico";
      modalForm.querySelector("#p-name").value = product ? product.name : "";
      modalForm.querySelector("#p-desc").value = product ? product.description : "";
      modalForm.querySelector("#p-price").value = product ? product.price : "";
      modalForm.querySelector("#p-icon").value = product ? product.icon || "" : "";
      modal.querySelector(".modal-title").textContent = product
        ? "Editar item do catálogo"
        : "Novo item do catálogo";
    }
    function closeModal() {
      modal.classList.remove("show");
    }

    if (newBtn) newBtn.addEventListener("click", () => openModal(null));
    closeBtns.forEach((b) => b.addEventListener("click", closeModal));
    if (modal) {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
      });
    }

    if (modalForm) {
      modalForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const id = modalForm.querySelector("#p-id").value;
        const products = getProducts();

        const data = {
          category: modalForm.querySelector("#p-category").value,
          name: modalForm.querySelector("#p-name").value.trim(),
          description: modalForm.querySelector("#p-desc").value.trim(),
          price: parseFloat(modalForm.querySelector("#p-price").value) || 0,
          icon: modalForm.querySelector("#p-icon").value.trim() || "❄️",
        };

        if (!data.name || !data.description || !data.price) {
          alert("Preencha nome, descrição e preço para salvar o item.");
          return;
        }

        if (id) {
          const idx = products.findIndex((p) => p.id === id);
          if (idx > -1) products[idx] = { ...products[idx], ...data };
        } else {
          products.push({ id: uid("prod"), specs: [], ...data });
        }

        saveProducts(products);
        closeModal();
        renderAdminTable();
        renderAdminStats();
        renderAdminOverview();
        showToast("Catálogo atualizado com sucesso.");
      });
    }

    function renderAdminStats() {
      const products = getProducts();
      const servicos = products.filter((p) => p.category === "servico").length;
      const paineis = products.filter((p) => p.category === "painel").length;
      const avg =
        products.length > 0
          ? products.reduce((s, p) => s + p.price, 0) / products.length
          : 0;

      const set = (sel, val) => {
        const el = document.querySelector(sel);
        if (el) el.textContent = val;
      };
      set("[data-stat-total]", products.length);
      set("[data-stat-servicos]", servicos);
      set("[data-stat-paineis]", paineis);
      set("[data-stat-avg]", formatBRL(avg));
    }

    /* Visão geral (dashboard) */
    function renderAdminOverview() {
      const products = getProducts();
      const set = (sel, val) => {
        const el = document.querySelector(sel);
        if (el) el.textContent = val;
      };

      const total = products.length;
      const totalValue = products.reduce((s, p) => s + p.price, 0);
      const avg = total > 0 ? totalValue / total : 0;

      set("[data-overview-total]", total);
      set("[data-overview-avg]", formatBRL(avg));
      set("[data-overview-total-value]", formatBRL(totalValue));

      if (total === 0) {
        set("[data-overview-cheapest]", "Nenhum item cadastrado.");
        set("[data-overview-priciest]", "Nenhum item cadastrado.");
        set("[data-overview-latest]", "Nenhum item cadastrado.");
        return;
      }

      const cheapest = products.reduce((a, b) => (b.price < a.price ? b : a));
      const priciest = products.reduce((a, b) => (b.price > a.price ? b : a));
      const latest = products[products.length - 1];

      set("[data-overview-cheapest]", `${cheapest.icon || "❄️"} ${cheapest.name} — ${formatBRL(cheapest.price)}`);
      set("[data-overview-priciest]", `${priciest.icon || "❄️"} ${priciest.name} — ${formatBRL(priciest.price)}`);
      set("[data-overview-latest]", `${latest.icon || "❄️"} ${latest.name} — ${formatBRL(latest.price)}`);
    }

    /* Alternância entre as visões do painel (Produtos & Serviços / Visão geral) */
    const adminNavButtons = document.querySelectorAll("[data-admin-view]");
    const adminViews = {
      produtos: document.querySelector("#admin-view-produtos"),
      geral: document.querySelector("#admin-view-geral"),
    };
    adminNavButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const target = btn.getAttribute("data-admin-view");
        adminNavButtons.forEach((b) => b.classList.toggle("active", b === btn));
        Object.keys(adminViews).forEach((key) => {
          if (adminViews[key]) adminViews[key].style.display = key === target ? "block" : "none";
        });
        if (target === "geral") renderAdminOverview();
      });
    });

    /* Busca e filtro por categoria na tabela de produtos */
    let adminSearchTerm = "";
    let adminCategoryFilter = "todos";
    const adminSearchInput = document.querySelector("#admin-search");
    const adminCategoryPills = document.querySelectorAll("#admin-category-filter [data-filter-category]");

    if (adminSearchInput) {
      adminSearchInput.addEventListener("input", () => {
        adminSearchTerm = adminSearchInput.value.trim().toLowerCase();
        renderAdminTable();
      });
    }
    adminCategoryPills.forEach((pill) => {
      pill.addEventListener("click", () => {
        adminCategoryFilter = pill.getAttribute("data-filter-category");
        adminCategoryPills.forEach((p) => p.classList.toggle("active", p === pill));
        renderAdminTable();
      });
    });

    function renderAdminTable() {
      const tbody = document.querySelector("#admin-table-body");
      if (!tbody) return;
      let products = getProducts();

      if (adminCategoryFilter !== "todos") {
        products = products.filter((p) => p.category === adminCategoryFilter);
      }
      if (adminSearchTerm) {
        products = products.filter(
          (p) =>
            p.name.toLowerCase().includes(adminSearchTerm) ||
            p.description.toLowerCase().includes(adminSearchTerm)
        );
      }

      if (products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5">Nenhum item encontrado para esse filtro.</td></tr>`;
        return;
      }

      tbody.innerHTML = products
        .map(
          (p) => `
        <tr>
          <td>${p.icon || "❄️"}&nbsp;&nbsp;${escapeHTML(p.name)}</td>
          <td><span class="badge badge-${p.category}">${
            p.category === "servico" ? "Serviço" : "Painel"
          }</span></td>
          <td>${escapeHTML(p.description).slice(0, 60)}${p.description.length > 60 ? "…" : ""}</td>
          <td>${formatBRL(p.price)}</td>
          <td>
            <div class="row-actions">
              <button type="button" data-edit="${p.id}">Editar</button>
              <button type="button" class="del" data-del="${p.id}">Remover</button>
            </div>
          </td>
        </tr>`
        )
        .join("");

      tbody.querySelectorAll("[data-edit]").forEach((b) =>
        b.addEventListener("click", () => {
          const p = getProducts().find((x) => x.id === b.getAttribute("data-edit"));
          openModal(p);
        })
      );
      tbody.querySelectorAll("[data-del]").forEach((b) =>
        b.addEventListener("click", () => {
          const id = b.getAttribute("data-del");
          if (confirm("Remover este item do catálogo? Essa ação não pode ser desfeita.")) {
            saveProducts(getProducts().filter((p) => p.id !== id));
            renderAdminTable();
            renderAdminStats();
            renderAdminOverview();
            showToast("Item removido do catálogo.");
          }
        })
      );
    }

    const resetBtn = document.querySelector("#admin-reset-catalog");
    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        if (confirm("Restaurar o catálogo padrão de fábrica? Suas edições serão perdidas.")) {
          saveProducts(DEFAULT_PRODUCTS);
          renderAdminTable();
          renderAdminStats();
          renderAdminOverview();
          showToast("Catálogo restaurado para o padrão.");
        }
      });
    }
  }

  /* ---------------------------------------------------------
     COMPONENTES GLOBAIS (todas as páginas)
  --------------------------------------------------------- */
  function initGlobalUI() {
    // menu mobile
    const toggle = document.querySelector(".nav-toggle");
    const nav = document.querySelector(".main-nav");
    if (toggle && nav) {
      toggle.addEventListener("click", () => nav.classList.toggle("open"));
    }

    // badge do carrinho no header
    updateCartBadge();

    // valida em tempo real qualquer input obrigatório
    document.querySelectorAll("input[required], select[required], textarea[required]").forEach((input) => {
      input.addEventListener("blur", () => validateField(input));
      input.addEventListener("input", () => {
        if (input.closest(".form-row")?.classList.contains("has-error")) validateField(input);
      });
    });
  }

  /* ---------------------------------------------------------
     WIDGET DO HERO (painel demonstrativo, apenas ilustrativo —
     não representa uma leitura real de temperatura do ambiente)
  --------------------------------------------------------- */
  function initHeroGauge() {
    const ring = document.getElementById("gauge-ring");
    const tempEl = document.getElementById("gauge-temp");
    const condEl = document.getElementById("gauge-cond");
    const fanBtn = document.getElementById("gauge-fan");
    const modeLabel = document.getElementById("gauge-mode-label");

    if (!ring || !tempEl || !condEl) return;

    const MIN_TEMP = 16;
    const MAX_TEMP = 30;
    const FAN_SPEEDS = ["Baixa", "Média", "Alta"];
    let temp = 21;
    let fanIndex = 1;
    let dragging = false;

    function render() {
      tempEl.textContent = `${temp}°C`;
      ring.setAttribute("aria-valuenow", String(temp));

      const pct = (temp - MIN_TEMP) / (MAX_TEMP - MIN_TEMP);
      const deg = Math.round(pct * 360);
      ring.style.background = `conic-gradient(var(--ice) 0deg ${deg}deg, var(--border-soft) ${deg}deg 360deg)`;

      if (temp <= 19) {
        condEl.textContent = "Resfriando";
        if (modeLabel) modeLabel.textContent = "Turbo";
      } else if (temp >= 26) {
        condEl.textContent = "Modo suave";
        if (modeLabel) modeLabel.textContent = "Suave";
      } else {
        condEl.textContent = "Confortável";
        if (modeLabel) modeLabel.textContent = "Eco";
      }

      if (fanBtn) fanBtn.textContent = FAN_SPEEDS[fanIndex];
    }

    // calcula a temperatura a partir da posição do ponteiro
    // em relação ao centro do círculo (0° no topo, sentido horário)
    function updateFromPointer(clientX, clientY) {
      const rect = ring.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      const dx = clientX - centerX;
      const dy = clientY - centerY;

      let angle = Math.atan2(dx, -dy) * (180 / Math.PI);
      if (angle < 0) angle += 360;

      const next = Math.round(MIN_TEMP + (angle / 360) * (MAX_TEMP - MIN_TEMP));
      temp = Math.min(MAX_TEMP, Math.max(MIN_TEMP, next));
      render();
    }

    // mouse (desktop): só ajusta enquanto o botão estiver pressionado (clicar e arrastar)
    ring.addEventListener("mousedown", (e) => {
      dragging = true;
      updateFromPointer(e.clientX, e.clientY);
    });
    window.addEventListener("mousemove", (e) => {
      if (!dragging) return;
      updateFromPointer(e.clientX, e.clientY);
    });
    window.addEventListener("mouseup", () => { dragging = false; });

    // toque: acompanha o dedo enquanto arrasta sobre o medidor
    ring.addEventListener(
      "touchstart",
      (e) => {
        dragging = true;
        if (e.touches[0]) updateFromPointer(e.touches[0].clientX, e.touches[0].clientY);
      },
      { passive: true }
    );
    ring.addEventListener(
      "touchmove",
      (e) => {
        if (!dragging || !e.touches[0]) return;
        e.preventDefault();
        updateFromPointer(e.touches[0].clientX, e.touches[0].clientY);
      },
      { passive: false }
    );
    ring.addEventListener("touchend", () => { dragging = false; });

    // acessibilidade: setas do teclado também ajustam
    ring.addEventListener("keydown", (e) => {
      if (e.key === "ArrowUp" || e.key === "ArrowRight") {
        temp = Math.min(MAX_TEMP, temp + 1);
        render();
        e.preventDefault();
      } else if (e.key === "ArrowDown" || e.key === "ArrowLeft") {
        temp = Math.max(MIN_TEMP, temp - 1);
        render();
        e.preventDefault();
      }
    });

    if (fanBtn) {
      fanBtn.addEventListener("click", () => {
        fanIndex = (fanIndex + 1) % FAN_SPEEDS.length;
        render();
      });
    }

    render();
  }

  /* ---------------------------------------------------------
     BOOT
  --------------------------------------------------------- */
  document.addEventListener("DOMContentLoaded", function () {
    getProducts(); // garante seed do catálogo padrão
    initGlobalUI();
    initCartPage();
    initContactForm();
    initAdmin();
    initHeroGauge();

    // catálogos são inicializados pelas próprias páginas via data-attribute
    const svcContainer = document.querySelector("[data-catalog='servico']");
    if (svcContainer) renderCatalog("[data-catalog='servico']", "servico");

    const pnlContainer = document.querySelector("[data-catalog='painel']");
    if (pnlContainer) renderCatalog("[data-catalog='painel']", "painel");
  });

  // expõe para uso pontual inline, se necessário
  window.Gelacionado = {
    addToCart,
    getProducts,
    getCart,
    formatBRL,
  };
})();
