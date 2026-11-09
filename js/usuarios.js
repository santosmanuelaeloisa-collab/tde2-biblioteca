/* usuarios.js — gerenciamento de usuários (somente admin) */

const msg = document.getElementById("mensagem");
const lista = document.getElementById("lista");
const cardForm = document.getElementById("card-form");
const form = document.getElementById("form-usuario");
const btnSalvar = document.getElementById("btn-salvar");

let usuarios = [];
let logado = null;

async function carregar() {
  try {
    const r = await chamarApi(API.usuarios, "GET");
    usuarios = r.dados || [];
    renderizar();
  } catch (erro) {
    tratarErro(msg, erro);
  }
}

function renderizar() {
  if (usuarios.length === 0) {
    lista.innerHTML = `<tr><td colspan="5" class="vazio">Nenhum usuário encontrado.</td></tr>`;
    return;
  }
  lista.innerHTML = usuarios.map((u) => `
    <tr>
      <td>${esc(u.nome)}</td>
      <td>${esc(u.email)}</td>
      <td>${u.perfil === "admin" ? "Administrador" : "Membro"}</td>
      <td>${formatarData(u.criado_em)}</td>
      <td class="acoes">
        <button class="btn btn-pequeno btn-sec" data-acao="editar" data-id="${esc(u.id)}">Editar</button>
        <button class="btn btn-pequeno btn-perigo" data-acao="excluir" data-id="${esc(u.id)}">Excluir</button>
      </td>
    </tr>`).join("");
}

function fecharFormulario() {
  form.reset();
  cardForm.hidden = true;
}

lista.addEventListener("click", async (e) => {
  const btn = e.target.closest("button[data-acao]");
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const u = usuarios.find((x) => Number(x.id) === id);
  if (!u) return;

  if (btn.dataset.acao === "editar") {
    document.getElementById("usuario-id").value = u.id;
    document.getElementById("nome").value = u.nome;
    document.getElementById("email").value = u.email;
    document.getElementById("perfil").value = u.perfil;
    cardForm.hidden = false;
    window.scrollTo({ top: 0, behavior: "smooth" });
  } else {
    if (!confirm(`Excluir o usuário "${u.nome}"?`)) return;
    try {
      const r = await chamarApi(API.usuarios, "POST", { acao: "excluir", id });
      mostrarMensagem(msg, r.mensagem);
      await carregar();
    } catch (erro) {
      tratarErro(msg, erro);
    }
  }
});

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const corpo = {
    acao: "editar",
    id: Number(document.getElementById("usuario-id").value),
    nome: document.getElementById("nome").value.trim(),
    email: document.getElementById("email").value.trim(),
    perfil: document.getElementById("perfil").value,
  };

  if (!corpo.nome) return mostrarMensagem(msg, "Informe o nome.", "erro");
  if (!emailValido(corpo.email)) return mostrarMensagem(msg, "Informe um e-mail válido.", "erro");

  btnSalvar.disabled = true;
  try {
    const r = await chamarApi(API.usuarios, "POST", corpo);
    mostrarMensagem(msg, r.mensagem);
    fecharFormulario();
    await carregar();
  } catch (erro) {
    tratarErro(msg, erro);
  } finally {
    btnSalvar.disabled = false;
  }
});

document.getElementById("btn-cancelar").addEventListener("click", fecharFormulario);

(async () => {
  logado = await protegerPagina("usuarios.html");
  if (!logado) return;
  if (logado.perfil !== "admin") {
    window.location.href = "livros.html"; // membro não acessa esta tela
    return;
  }
  carregar();
})();