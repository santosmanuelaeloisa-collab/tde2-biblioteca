/* api.js — funções compartilhadas por todas as páginas */

// ⚠️ Confirmar com a Pessoa 1 os nomes dos arquivos de autenticação
const API = {
  cadastro:    "autenticacao/cadastro.php",
  login:       "autenticacao/login.php",
  logout:      "autenticacao/logout.php",
  sessao:      "autenticacao/sessao.php",   // devolve o usuário logado
  livros:      "API/livros.php",
  emprestimos: "API/emprestimos.php",
  usuarios:    "API/usuarios.php",
};

/**
 * Chamada padrão à API (fetch + JSON).
 * Sucesso -> devolve {mensagem, dados}.
 * Erro (status 4xx/5xx) -> lança Error com o texto de {"erro": "..."}.
 */
async function chamarApi(url, metodo = "GET", corpo = null) {
  const opcoes = { method: metodo, credentials: "same-origin" };
  if (corpo !== null) {
    opcoes.headers = { "Content-Type": "application/json" };
    opcoes.body = JSON.stringify(corpo);
  }

  let resposta;
  try {
    resposta = await fetch(url, opcoes);
  } catch (e) {
    throw new Error("Não foi possível conectar ao servidor. O Apache e o MySQL estão ligados?");
  }

  let dados;
  try {
    dados = await resposta.json();
  } catch (e) {
    throw new Error("Resposta inválida do servidor.");
  }

  if (resposta.status === 401) {
    const erro = new Error(dados.erro || "Você precisa estar logado.");
    erro.status = 401;
    throw erro;
  }
  if (!resposta.ok) {
    const erro = new Error(dados.erro || "Erro ao processar a requisição.");
    erro.status = resposta.status;
    throw erro;
  }
  return dados;
}

/** Mostra mensagem de sucesso ou erro em um elemento .mensagem */
function mostrarMensagem(elemento, texto, tipo = "sucesso") {
  elemento.textContent = texto;
  elemento.className = "mensagem " + tipo;
  elemento.hidden = false;
  clearTimeout(elemento._timer);
  if (tipo === "sucesso") {
    elemento._timer = setTimeout(() => { elemento.hidden = true; }, 4000);
  }
}

/** Escapa HTML (evita XSS ao montar tabelas com innerHTML) */
function esc(valor) {
  return String(valor ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  }[c]));
}

function emailValido(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function formatarData(d) {
  if (!d) return "—";
  const [data] = String(d).split(" ");
  const [a, m, dia] = data.split("-");
  return `${dia}/${m}/${a}`;
}

/**
 * Páginas protegidas: confirma a sessão, monta o menu e devolve o usuário
 * ({id, nome, perfil}). Sem sessão, volta para o login.
 */
async function protegerPagina(paginaAtual) {
  try {
    const r = await chamarApi(API.sessao, "GET");
    const usuario = r.dados;
    montarMenu(paginaAtual, usuario);
    return usuario;
  } catch (e) {
    window.location.href = "login.html";
    return null;
  }
}

function montarMenu(paginaAtual, usuario) {
  const links = [
    ["livros.html", "Livros"],
    ["emprestimos.html", "Meus empréstimos"],
  ];
  if (usuario.perfil === "admin") links.push(["usuarios.html", "Usuários"]);

  document.getElementById("topo").innerHTML = `
    <h1>Biblioteca</h1>
    <nav>
      ${links.map(([href, texto]) =>
        `<a href="${href}" class="${href === paginaAtual ? "ativo" : ""}">${texto}</a>`).join("")}
      <span class="usuario">Olá, ${esc(usuario.nome)}</span>
      <button type="button" class="btn btn-sair btn-pequeno" id="btn-logout">Sair</button>
    </nav>`;
  document.getElementById("btn-logout").addEventListener("click", sair);
}

async function sair() {
  try { await chamarApi(API.logout, "POST"); } catch (e) { /* volta para o login mesmo assim */ }
  window.location.href = "login.html";
}
/** Mostra o erro na tela; se for 401 (sem sessão), volta para o login */
function tratarErro(elemento, erro) {
  if (erro.status === 401) {
    window.location.href = "login.html";
    return;
  }
  mostrarMensagem(elemento, erro.message, "erro");
}