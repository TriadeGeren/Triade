/* ==========================================================================
   ELECTRO INSTALL — autenticação simples (100% no navegador)
   Não existe backend neste projeto, então as "senhas" abaixo servem só
   para demonstração. Se algum dia este site ganhar um servidor de verdade,
   troque este arquivo por uma chamada de login real.
   ========================================================================== */

const AUTH_KEY = "electroinstall_session";

const USERS = [
  { user: "admin",   pass: "admin123",   role: "admin",   name: "Administrador" },
  { user: "cliente", pass: "cliente123", role: "cliente", name: "Cliente" }
];

function login(user, pass, remember){
  const found = USERS.find(u => u.user === user.trim().toLowerCase() && u.pass === pass);
  if(!found) return null;
  const session = { user: found.user, name: found.name, role: found.role };
  const store = remember ? localStorage : sessionStorage;
  store.setItem(AUTH_KEY, JSON.stringify(session));
  return session;
}

function getSession(){
  try{
    const raw = sessionStorage.getItem(AUTH_KEY) || localStorage.getItem(AUTH_KEY);
    return raw ? JSON.parse(raw) : null;
  }catch(e){
    return null;
  }
}

function logout(){
  sessionStorage.removeItem(AUTH_KEY);
  localStorage.removeItem(AUTH_KEY);
  window.location.href = "login.html";
}

/* Chame no topo de cada página protegida.
   requireAuth()        -> exige apenas estar logado (qualquer papel)
   requireAuth("admin") -> exige estar logado como administrador          */
function requireAuth(requiredRole){
  const session = getSession();
  if(!session){
    window.location.href = "login.html";
    return null;
  }
  if(requiredRole && session.role !== requiredRole){
    window.location.href = "index-E.html";
    return null;
  }
  return session;
}
