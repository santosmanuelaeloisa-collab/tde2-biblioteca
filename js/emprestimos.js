/* emprestimos.js — meus empréstimos */

const msg = document.getElementById("mensagem");
const lista = document.getElementById("lista");

let emprestimos = [];

async function carregar() {
  try {
    const r = await chamarApi(API.emprestimos, "GET");
    emprestimos = r.dados || [];
    renderizar();
  } catch (erro) {
    tratarErro(msg, erro);
  }
}

function renderizar() {
  if (emprestimos.length === 0) {
    lista.innerHTML = `<tr><td colspan="6" class="vazio">Você ainda não tem empréstimos.</td></tr>`;
    return;
  }

  lista.innerHTML = emprestimos.map((e) => {
    const devolvido = e.data_devolucao !== null;
    const status = devolvido
      ? `<span class="selo devolvido">Devolvido</span>`
      : `<span class="selo aberto">Em aberto</span>`;
    // Em aberto -> pode devolver. Devolvido -> pode excluir o registro.
    const acao = devolvido
      ? `<button class="btn btn-pequeno btn-perigo" data-acao="excluir" data-id="${esc(e.id)}">Excluir registro</button>`
      : `<button class="btn btn-pequeno" data-acao="devolver" data-id="${esc(e.id)}">Devolver</button>`;

    return `<tr>
      <td>${esc(e.titulo)}</td>
      <td>${esc(e.autor)}</td>
      <td>${formatarData(e.data_emprestimo)}</td>
      <td>${formatarData(e.data_devolucao)}</td>
      <td>${status}</td>
      <td class="acoes">${acao}</td>
    </tr>`;
  }).join("");
}

lista.addEventListener("click", async (e) => {
  const btn = e.target.closest("button[data-acao]");
  if (!btn) return;
  const id = Number(btn.dataset.id);

  if (btn.dataset.acao === "excluir" && !confirm("Excluir este registro de empréstimo?")) return;

  try {
    const r = await chamarApi(API.emprestimos, "POST", { acao: btn.dataset.acao, id });
    mostrarMensagem(msg, r.mensagem);
    await carregar();
  } catch (erro) {
    tratarErro(msg, erro);
  }
});

(async () => {
  const usuario = await protegerPagina("emprestimos.html");
  if (usuario) carregar();
})();