/* ==========================================================================
   ELECTRO INSTALL — carrinho (localStorage) + utilidades globais
   ========================================================================== */

const CART_KEY = "electroinstall_cart";
const WHATSAPP_NUMBER = "5585985132769"; // TODO: substituir pelo número real (DDI+DDD+numero)

function getCart(){
  try{
    const raw = localStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : {};
  }catch(e){
    return {};
  }
}

function saveCart(cart){
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartCount();
}

function addToCart(id, qty = 1){
  const cart = getCart();
  cart[id] = (cart[id] || 0) + qty;
  saveCart(cart);
  const item = getCatalog().find(p => p.id === id);
  showToast(item ? `${item.name} adicionado ao carrinho` : "Item adicionado ao carrinho");
}

function setQty(id, qty){
  const cart = getCart();
  if(qty <= 0){
    delete cart[id];
  }else{
    cart[id] = qty;
  }
  saveCart(cart);
  if(typeof renderCartPage === "function") renderCartPage();
}

function removeFromCart(id){
  const cart = getCart();
  delete cart[id];
  saveCart(cart);
  if(typeof renderCartPage === "function") renderCartPage();
}

function cartTotalItems(){
  const cart = getCart();
  return Object.values(cart).reduce((sum, q) => sum + q, 0);
}

function cartTotalValue(){
  const cart = getCart();
  const catalog = getCatalog();
  return Object.entries(cart).reduce((sum, [id, qty]) => {
    const item = catalog.find(p => p.id === id);
    return sum + (item ? item.price * qty : 0);
  }, 0);
}

function updateCartCount(){
  document.querySelectorAll("[data-cart-count]").forEach(el => {
    el.textContent = cartTotalItems();
  });
}

/* ---------- toast ---------- */
let toastTimer = null;
function showToast(message){
  let toast = document.querySelector(".toast");
  if(!toast){
    toast = document.createElement("div");
    toast.className = "toast";
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2600);
}

/* ---------- checkout via WhatsApp ---------- */
function buildWhatsAppMessage(customerName, customerAddress){
  const cart = getCart();
  const entries = Object.entries(cart);
  if(entries.length === 0) return null;

  let lines = [];
  lines.push("Olá, Electro Install! Gostaria de solicitar o seguinte orçamento/serviço:");
  lines.push("");
  entries.forEach(([id, qty]) => {
    const item = CATALOG.find(p => p.id === id);
    if(!item) return;
    lines.push(`• ${qty}x ${item.name} — ${formatBRL(item.price * qty)}`);
  });
  lines.push("");
  lines.push(`Total estimado: ${formatBRL(cartTotalValue())}`);
  if(customerName) lines.push(`Nome: ${customerName}`);
  if(customerAddress) lines.push(`Endereço/Bairro: ${customerAddress}`);
  lines.push("");
  lines.push("Aguardo confirmação de data e horário para a visita técnica.");
  return lines.join("\n");
}

function sendCheckoutToWhatsApp(customerName, customerAddress){
  const message = buildWhatsAppMessage(customerName, customerAddress);
  if(!message){
    showToast("Seu carrinho está vazio");
    return;
  }
  saveOrder(customerName, customerAddress);
  const url = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
  window.open(url, "_blank");
}

/* ---------- pedidos (para a tabela do painel admin) ---------- */
const ORDERS_KEY = "electroinstall_orders";

function getOrders(){
  try{
    const raw = localStorage.getItem(ORDERS_KEY);
    return raw ? JSON.parse(raw) : [];
  }catch(e){
    return [];
  }
}

function saveOrder(customerName, customerAddress){
  const cart = getCart();
  const entries = Object.entries(cart);
  if(entries.length === 0) return;

  const catalog = getCatalog();
  const items = entries.map(([id, qty]) => {
    const item = catalog.find(p => p.id === id);
    return item ? { id, name: item.name, qty, price: item.price } : null;
  }).filter(Boolean);

  const order = {
    id: "PED-" + Date.now(),
    date: new Date().toISOString(),
    customerName: customerName || "(não informado)",
    customerAddress: customerAddress || "(não informado)",
    items,
    total: cartTotalValue(),
    status: "novo"
  };

  const orders = getOrders();
  orders.unshift(order);
  localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
}

/* ---------- nav mobile ---------- */
function initNavToggle(){
  const toggle = document.querySelector(".nav-toggle");
  const nav = document.querySelector(".main-nav");
  if(!toggle || !nav) return;
  toggle.addEventListener("click", () => {
    nav.classList.toggle("open");
    const expanded = nav.classList.contains("open");
    toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
  });
  nav.querySelectorAll("a").forEach(a => a.addEventListener("click", () => nav.classList.remove("open")));
}

document.addEventListener("DOMContentLoaded", () => {
  updateCartCount();
  initNavToggle();
});
