CREATE DATABASE IF NOT EXISTS biblioteca_comunitaria
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_comunitaria;

CREATE TABLE IF NOT EXISTS usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil     ENUM('admin','membro') NOT NULL DEFAULT 'membro',
  criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS livros (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  titulo     VARCHAR(150) NOT NULL,
  autor      VARCHAR(100) NOT NULL,
  ano        INT NULL,
  disponivel TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS emprestimos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT NOT NULL,
  livro_id        INT NOT NULL,
  data_emprestimo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_devolucao  DATETIME NULL,
  CONSTRAINT fk_emp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
  CONSTRAINT fk_emp_livro   FOREIGN KEY (livro_id)   REFERENCES livros(id)   ON DELETE RESTRICT
) ENGINE=InnoDB;
-- ===== Livros de exemplo =====

-- Clássicos brasileiros
INSERT INTO livros (titulo, autor, ano) VALUES
 ('Dom Casmurro', 'Machado de Assis', 1899),
 ('Memórias Póstumas de Brás Cubas', 'Machado de Assis', 1881),
 ('O Cortiço', 'Aluísio Azevedo', 1890),
 ('Iracema', 'José de Alencar', 1865),
 ('Macunaíma', 'Mário de Andrade', 1928),
 ('Capitães da Areia', 'Jorge Amado', 1937),
 ('Vidas Secas', 'Graciliano Ramos', 1938),
 ('Grande Sertão: Veredas', 'João Guimarães Rosa', 1956),
 ('Auto da Compadecida', 'Ariano Suassuna', 1955),
 ('Quarto de Despejo', 'Carolina Maria de Jesus', 1960),
 ('A Hora da Estrela', 'Clarice Lispector', 1977),
 ('Cidade de Deus', 'Paulo Lins', 1997);

-- Ciência da Computação
INSERT INTO livros (titulo, autor, ano) VALUES
 ('Código Limpo', 'Robert C. Martin', 2008),
 ('O Programador Pragmático', 'Andrew Hunt e David Thomas', 1999),
 ('Arquitetura Limpa', 'Robert C. Martin', 2017),
 ('Padrões de Projetos', 'Erich Gamma e outros', 1994),
 ('Algoritmos: Teoria e Prática', 'Thomas H. Cormen e outros', 1990),
 ('A Linguagem de Programação C', 'Brian W. Kernighan e Dennis M. Ritchie', 1978),
 ('Sistemas Operacionais Modernos', 'Andrew S. Tanenbaum', 1992),
 ('Redes de Computadores', 'Andrew S. Tanenbaum', 1981),
 ('Sistemas de Banco de Dados', 'Ramez Elmasri e Shamkant B. Navathe', 1989),
 ('O Mítico Homem-Mês', 'Frederick P. Brooks Jr.', 1975);

-- Adaptações famosas e best-sellers
INSERT INTO livros (titulo, autor, ano) VALUES
 ('Jogos Vorazes', 'Suzanne Collins', 2008),
 ('Melhor do que nos filmes', 'Lynn Painter', 2021),
 ('Harry Potter e a Pedra Filosofal', 'J. K. Rowling', 1997),
 ('O Hobbit', 'J. R. R. Tolkien', 1937),
 ('Orgulho e Preconceito', 'Jane Austen', 1813),
 ('O Pequeno Príncipe', 'Antoine de Saint-Exupéry', 1943),
 ('A Culpa é das Estrelas', 'John Green', 2012),
 ('Percy Jackson e o Ladrão de Raios', 'Rick Riordan', 2005),
 ('A Menina que Roubava Livros', 'Markus Zusak', 2005),
 ('Maze Runner: Correr ou Morrer', 'James Dashner', 2009),
 ('Para Todos os Garotos que Já Amei', 'Jenny Han', 2014),
 ('Mulherzinhas', 'Louisa May Alcott', 1868),
 ('Como Eu Era Antes de Você', 'Jojo Moyes', 2012);