/* cadastro.js */

const form = document.getElementById("form-cadastro");
const msg = document.getElementById("mensagem");
const btn = document.getElementById("btn-cadastrar");

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const nome = document.getElementById("nome").value.trim();
  const email = document.getElementById("email").value.trim();
  const senha = document.getElementById("senha").value;
  const confirmar = document.getElementById("confirmar").value;

  if (nome.length < 3) return mostrarMensagem(msg, "Informe o nome completo (mín. 3 caracteres).", "erro");
  if (!emailValido(email)) return mostrarMensagem(msg, "Informe um e-mail válido.", "erro");
  if (senha.length < 6) return mostrarMensagem(msg, "A senha deve ter pelo menos 6 caracteres.", "erro");
  if (senha !== confirmar) return mostrarMensagem(msg, "As senhas não conferem.", "erro");

  btn.disabled = true;
  try {
    await chamarApi(API.cadastro, "POST", { nome, email, senha });
    location.href = "login.html?cadastro=ok";
  } catch (erro) {
    mostrarMensagem(msg, erro.message, "erro");
  } finally {
    btn.disabled = false;
  }
});