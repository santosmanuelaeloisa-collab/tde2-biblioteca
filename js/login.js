/* login.js */

const form = document.getElementById("form-login");
const msg = document.getElementById("mensagem");
const btn = document.getElementById("btn-entrar");

// Mensagem vinda do cadastro (login.html?cadastro=ok)
if (new URLSearchParams(location.search).get("cadastro") === "ok") {
  mostrarMensagem(msg, "Cadastro realizado! Faça login para continuar.");
}

// Se já estiver logado, vai direto para os livros
chamarApi(API.sessao, "GET").then(() => { location.href = "livros.html"; }).catch(() => {});

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("email").value.trim();
  const senha = document.getElementById("senha").value;

  if (!emailValido(email)) return mostrarMensagem(msg, "Informe um e-mail válido.", "erro");
  if (senha.length < 6) return mostrarMensagem(msg, "A senha deve ter pelo menos 6 caracteres.", "erro");

  btn.disabled = true;
  try {
    await chamarApi(API.login, "POST", { email, senha });
    location.href = "livros.html";
  } catch (erro) {
    mostrarMensagem(msg, erro.message, "erro");
  } finally {
    btn.disabled = false;
  }
});