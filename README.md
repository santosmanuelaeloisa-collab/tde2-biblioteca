# Biblioteca Comunitária — TDE 2 (Programação Web)

Aplicação web de biblioteca comunitária com cadastro e login de usuários, controle
de sessão PHP e CRUD de *usuários, **livros* e *empréstimos*. Os dados são
persistidos em MySQL.

*Equipe:* Emanuelly Araújo, Helena Ançai, Manuela Santos
*Disciplina:* Programação Web — 2º semestre de 2026

## Tecnologias
- HTML, CSS e JavaScript (comunicação com a API via fetch + JSON)
- PHP 8+ (PDO, sessões, prepared statements, password_hash)
- MySQL / MariaDB

## Requisitos
- XAMPP (ou outro ambiente com Apache, PHP 8+ e MySQL/MariaDB)

## Instalação e execução (passo a passo)
1. Extraia o ZIP e copie a pasta do projeto para dentro de htdocs
   (ex.: C:\xampp\htdocs\tde2-biblioteca).
2. Abra o painel do XAMPP e inicie *Apache* e *MySQL*.
3. Acesse http://localhost/phpmyadmin, vá na aba *Importar*, escolha o arquivo
   sql/schema.sql e clique em *Importar*. Isso cria o banco
   biblioteca_comunitaria, as tabelas e 30 livros de exemplo.
4. Confira o arquivo config/conexao.php. O padrão do XAMPP já vem configurado:
   host 127.0.0.1, porta 3306, usuário root, *senha vazia*.
   Se o seu MySQL tiver senha, altere a variável $senha.
5. Abra no navegador: http://localhost/tde2-biblioteca/login.html
   (ajuste o nome da pasta na URL se você a renomeou).

## Como testar o sistema
O banco *não vem com usuários*; crie as contas pela tela de cadastro.

1. Acesse *Cadastre-se* (cadastro.html) e crie a primeira conta.
   *O primeiro usuário cadastrado vira administrador automaticamente.*
   Os demais começam como *membro*.
2. Crie uma segunda conta (outro e-mail) para testar o perfil de membro.
3. Faça login em login.html.

| Perfil | O que pode fazer |
|---|---|
| Visitante (sem login) | Só acessa login e cadastro; as APIs retornam 401 |
| Membro | Ver o acervo, emprestar livros disponíveis, devolver livros e excluir seus empréstimos já devolvidos |
| Admin | Tudo do membro + cadastrar, editar e excluir livros + gerenciar usuários (editar nome, e-mail e perfil; excluir) |

### Roteiro de demonstração
1. *Cadastro* → crie um usuário (validação: nome, e-mail válido, senha com no mínimo 6 caracteres; e-mail duplicado é recusado).
2. *Login / sessão* → entre com o usuário; tente abrir livros.html sem login e veja o redirecionamento.
3. *Livros (CRUD)* → como admin: criar, editar e excluir um livro.
4. *Relacionamento* → como membro: em *Livros, clique em *Emprestar; o livro fica indisponível e aparece em *Meus empréstimos*.
5. *Empréstimos* → devolva o livro (volta a ficar disponível) e depois exclua o registro devolvido.
6. *Usuários (admin)* → edite o perfil de um usuário e tente excluir um usuário que tem empréstimo (a chave estrangeira impede e uma mensagem de erro é exibida).
7. *Logout* → clique em *Sair* e confirme que as páginas protegidas deixam de abrir.

## Estrutura do projeto

├── API/             endpoints JSON: livros.php, emprestimos.php, usuarios.php
├── autenticacao/    cadastro.php, login.php, logout.php, sessao.php
├── config/          conexao.php (PDO) e verifica_sessao.php
├── css/ e js/       estilos e scripts do frontend (fetch/JSON)
├── sql/schema.sql   criação do banco, tabelas e dados de exemplo
└── *.html           login, cadastro, livros, emprestimos, usuarios


## Banco de dados
Banco biblioteca_comunitaria (InnoDB, utf8mb4) com 3 tabelas:

- usuarios (id, nome, email único, senha_hash, perfil admin/membro, criado_em)
- livros (id, titulo, autor, ano, disponivel)
- emprestimos (id, usuario_id, livro_id, data_emprestimo, data_devolucao)

Relacionamento: emprestimos.usuario_id → usuarios.id e
emprestimos.livro_id → livros.id (chaves estrangeiras com ON DELETE RESTRICT).
Um usuário ou livro com empréstimos registrados não pode ser excluído.

## Segurança
- Senhas armazenadas com password_hash e conferidas com password_verify.
- Todas as consultas SQL com dados do usuário usam prepared statements (PDO).
- O id do usuário vem sempre da sessão PHP, nunca do frontend.
- Ações administrativas são validadas no backend (HTTP 403 para quem não é admin).