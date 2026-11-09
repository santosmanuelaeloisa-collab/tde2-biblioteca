# Biblioteca Comunitária — TDE 2 (Programação Web)

Aplicação web de biblioteca com cadastro e login de usuários, controle de sessão
e CRUD de livros, empréstimos e usuários. Os dados são salvos em MySQL.

**Equipe:** Emanuelly Araújo, Helena Ançai, Manuela Santos

## Tecnologias
- HTML, CSS e JavaScript (fetch com JSON)
- PHP 8 (PDO, sessões, prepared statements)
- MySQL / MariaDB

## Requisitos
- XAMPP (ou outro servidor com Apache, PHP 8+ e MySQL)

## Como instalar e executar
1. Copie a pasta `tde2-biblioteca` para dentro de `htdocs` (XAMPP).
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Abra o phpMyAdmin (`http://localhost/phpmyadmin`), vá na aba **Importar** e
   importe o arquivo `sql/schema.sql` (ele cria o banco `biblioteca_comunitaria` e as tabelas).
4. Na pasta `config/`, copie `conexao.exemplo.php` para `conexao.php` e preencha
   usuário e senha do MySQL (no XAMPP padrão: usuário `root`, senha vazia).
5. Acesse `http://localhost/tde2-biblioteca/login.html`.

## Como testar
1. Em **Cadastre-se**, crie uma conta. Todo novo usuário começa com o perfil **membro**.
2. Para criar o primeiro administrador, no phpMyAdmin rode:
   `UPDATE usuarios SET perfil = 'admin' WHERE email = 'seu@email.com';`
3. Faça login. Funcionalidades:
   - **Livros:** todos veem o acervo e podem emprestar livros disponíveis;
     o administrador cadastra, edita e exclui livros.
   - **Meus empréstimos:** o usuário devolve livros e exclui registros já devolvidos.
   - **Usuários (somente admin):** editar perfil, nome e e-mail, e excluir usuários.
4. Use **Sair** para encerrar a sessão.

## Estrutura
- `API/`: endpoints JSON (livros, empréstimos, usuários)
- `autenticacao/`: cadastro, login, logout e sessão
- `config/`: conexão com o banco
- `css/`, `js/` e os `.html` da raiz: interface
- `sql/schema.sql`: criação do banco

## Banco de dados
Tabelas `usuarios`, `livros` e `emprestimos`. A tabela `emprestimos` liga usuário
e livro por chaves estrangeiras.