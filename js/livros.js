/* livros.js — listagem e gerenciamento de livros */

const msg = document.getElementById("mensagem");
const form = document.getElementById("form-livro");
const lista = document.getElementById("lista");
const busca = document.getElementById("busca");
const cardForm = document.getElementById("card-form");
const btnSalvar = document.getElementById("btn-salvar");
const btnCancelar = document.getElementById("btn-cancelar");
const tituloForm = document.getElementById("titulo-form");

let livros = [];
let ehAdmin = false;

async function carregarLivros() {
  try {
    const r = await chamarApi(API.livros, "GET");
    livros = r.dados || [];
    renderizar();
  } catch (erro) {
    tratarErro(msg, erro);
  }
}

function renderizar() {
  const termo = busca.value.trim().toLowerCase();
  const filtrados = livros.filter((l) =>
    l.titulo.toLowerCase().includes(termo) || l.autor.toLowerCase().includes(termo));

  if (filtrados.length === 0) {
    lista.innerHTML = `<tr><td colspan="5" class="vazio">Nenhum livro encontrado.</td></tr>`;
    return;
  }

  lista.innerHTML = filtrados.map((l) => {
    const disponivel = Number(l.disponivel) === 1;
    const selo = disponivel
      ? `<span class="selo devolvido">Disponível</span>`
      : `<span class="selo aberto">Emprestado</span>`;

    let acoes = "";
    if (disponivel) {
      acoes += `<button class="btn btn-pequeno" data-acao="emprestar" data-id="${esc(l.id)}">Emprestar</button>`;
    }
    if (ehAdmin) {
      acoes += `<button class="btn btn-pequeno btn-sec" data-acao="editar" data-id="${esc(l.id)}">Editar</button>
                <button class="btn btn-pequeno btn-perigo" data-acao="excluir" data-id="${esc(l.id)}">Excluir</button>`;
    }

    return `<tr>
      <td>${esc(l.titulo)}</td>
      <td>${esc(l.autor)}</td>
      <td>${esc(l.ano || "—")}</td>
      <td>${selo}</td>
      <td class="acoes">${acoes}</td>
    </tr>`;
  }).join("");
}

function limparFormulario() {
  form.reset();
  document.getElementById("livro-id").value = "";
  tituloForm.textContent = "Novo livro";
  btnCancelar.hidden = true;
}

// Criar / editar (só admin)
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const id = document.getElementById("livro-id").value;
  const titulo = document.getElementById("titulo").value.trim();
  const autor = document.getElementById("autor").value.trim();
  const anoTexto = document.getElementById("ano").value.trim();
  const anoAtual = new Date().getFullYear();

  if (!titulo || !autor) return mostrarMensagem(msg, "Título e autor são obrigatórios.", "erro");
  if (anoTexto !== "" && (!Number.isInteger(Number(anoTexto)) || Number(anoTexto) < 1 || Number(anoTexto) > anoAtual))
    return mostrarMensagem(msg, `Informe um ano válido (até ${anoAtual}).`, "erro");

  const corpo = { acao: id ? "editar" : "criar", titulo, autor, ano: anoTexto === "" ? null : Number(anoTexto) };
  if (id) corpo.id = Number(id);

  btnSalvar.disabled = true;
  try {
    const r = await chamarApi(API.livros, "POST", corpo);
    mostrarMensagem(msg, r.mensagem);
    limparFormulario();
    await carregarLivros();
  } catch (erro) {
    tratarErro(msg, erro);
  } finally {
    btnSalvar.disabled = false;
  }
});

// Botões da tabela (emprestar / editar / excluir)
lista.addEventListener("click", async (e) => {
  const btn = e.target.closest("button[data-acao]");
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const livro = livros.find((l) => Number(l.id) === id);
  if (!livro) return;

  try {
    if (btn.dataset.acao === "emprestar") {
      const r = await chamarApi(API.emprestimos, "POST", { acao: "emprestar", livro_id: id });
      mostrarMensagem(msg, r.mensagem);
      await carregarLivros();

    } else if (btn.dataset.acao === "editar") {
      document.getElementById("livro-id").value = livro.id;
      document.getElementById("titulo").value = livro.titulo;
      document.getElementById("autor").value = livro.autor;
      document.getElementById("ano").value = livro.ano || "";
      tituloForm.textContent = "Editar livro";
      btnCancelar.hidden = false;
      window.scrollTo({ top: 0, behavior: "smooth" });

    } else if (btn.dataset.acao === "excluir") {
      if (!confirm(`Excluir o livro "${livro.titulo}"?`)) return;
      const r = await chamarApi(API.livros, "POST", { acao: "excluir", id });
      mostrarMensagem(msg, r.mensagem);
      await carregarLivros();
    }
  } catch (erro) {
    tratarErro(msg, erro);
  }
});

btnCancelar.addEventListener("click", limparFormulario);
busca.addEventListener("input", renderizar);

// Início da página
(async () => {
  const usuario = await protegerPagina("livros.html");
  if (!usuario) return;
  ehAdmin = usuario.perfil === "admin";
  cardForm.hidden = !ehAdmin;
  carregarLivros();
})();