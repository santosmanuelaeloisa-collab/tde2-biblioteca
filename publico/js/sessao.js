async function carregarSessao() {
  try {
    const dados = await chamarApi('../autenticacao/sessao.php');
    if (!dados) return null;   // deu 401: o api.js já mandou a pessoa para o login

    const nome = document.getElementById('usuario-nome');
    if (nome) nome.textContent = dados.usuario.nome;
    return dados.usuario;
  } catch (erro) {
    location.href = 'login.html';
    return null;
  }
}

async function sair() {
  await chamarApi('../autenticacao/logout.php', 'POST');
  location.href = 'login.html';
}