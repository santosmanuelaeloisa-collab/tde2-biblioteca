
document.getElementById('form-cadastro').addEventListener('submit', async (e) => {
  e.preventDefault();
  const mensagem = document.getElementById('mensagem');
  mensagem.textContent = 'Enviando...';

  try {
    const dados = await chamarApi('../autenticacao/cadastro.php', 'POST', {
      nome:  document.getElementById('nome').value,
      email: document.getElementById('email').value,
      senha: document.getElementById('senha').value
    });
    mensagem.textContent = dados.mensagem;
    e.target.reset();
  } catch (erro) {
    mensagem.textContent = 'Erro: ' + erro.message;
  }
});