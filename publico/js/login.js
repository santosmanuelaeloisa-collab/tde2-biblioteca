document.getElementById('form-login').addEventListener('submit', async (e) => {
  e.preventDefault();
  const mensagem = document.getElementById('mensagem');
  mensagem.textContent = 'Entrando...';

  try {
    const dados = await chamarApi('../autenticacao/login.php', 'POST', {
      email: document.getElementById('email').value,
      senha: document.getElementById('senha').value
    });
    mensagem.textContent = dados.mensagem + ' Bem-vinda, ' + dados.nome + '!';
  } catch (erro) {
    mensagem.textContent = 'Erro: ' + erro.message;
  }
});