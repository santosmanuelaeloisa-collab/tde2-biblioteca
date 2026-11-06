async function chamarApi(url, metodo = 'GET', corpo = null) {
  const opcoes = { method: metodo, headers: {} };
  if (corpo !== null) {
    opcoes.headers['Content-Type'] = 'application/json';
    opcoes.body = JSON.stringify(corpo);
  }

  const resposta = await fetch(url, opcoes);

  let dados = {};
  try { dados = await resposta.json(); } catch (e) {}

  if (resposta.status === 401 && document.body.dataset.publica !== 'sim') {
    location.href = 'login.html';
    return;
  }
  if (!resposta.ok) {
    throw new Error(dados.erro || 'Erro inesperado (código ' + resposta.status + ').');
  }
  return dados;
}