<?php
// =====================================================================
// livros.php  -  CRUD da tabela "livros"
//
// Como o frontend chama este arquivo (via fetch):
//
//   LISTAR  -> GET   livros.php                (qualquer usuário logado)
//   1 LIVRO -> GET   livros.php?id=3           (qualquer usuário logado)
//   CRIAR   -> POST  livros.php   {"acao":"criar",   "titulo":"..", "autor":"..", "ano":1899}       (admin)
//   EDITAR  -> POST  livros.php   {"acao":"editar",  "id":3, "titulo":"..", "autor":"..", "ano":1899} (admin)
//   EXCLUIR -> POST  livros.php   {"acao":"excluir", "id":3}                                          (admin)
//
// O POST envia JSON no corpo (igual ao cadastro.php).
// "ano" é opcional.
//
// RESPOSTAS (mesmo padrão do cadastro.php):
//   erro    -> status 4xx/5xx  {"erro": "mensagem"}
//   sucesso -> status 200/201  {"mensagem": "...", "dados": ...}
// =====================================================================

// Liga a sessão: sem isso o PHP não sabe quem está logado ($_SESSION).
session_start();

// Avisa o navegador que a resposta será JSON.
header('Content-Type: application/json; charset=utf-8');

// Traz a conexão da Pessoa 1 (cria a variável $pdo).
// __DIR__ = pasta deste arquivo; '/../config/' = sobe uma pasta e entra em config.
require __DIR__ . '/../config/conexao.php';


// ---------------------------------------------------------------------
// responder(): monta a resposta JSON e PARA o script (exit).
//   $codigo   -> código HTTP (200 ok, 201 criado, 401 não logado,
//                403 sem permissão, 404 não achou, 409 conflito,
//                422 dados inválidos, 500 erro no servidor)
//   $mensagem -> texto para o frontend mostrar
//   $dados    -> (opcional) dados extras, como a lista de livros
// ---------------------------------------------------------------------
function responder($codigo, $mensagem, $dados = null) {
    http_response_code($codigo);

    if ($codigo >= 400) {
        // Códigos 4xx e 5xx são erros: usa a chave "erro"
        $resposta = ['erro' => $mensagem];
    } else {
        // Códigos 2xx são sucesso: usa a chave "mensagem"
        $resposta = ['mensagem' => $mensagem];
        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }
    }

    echo json_encode($resposta);
    exit;
}

// ---------------------------------------------------------------------
// Valida título, autor e ano (usada em criar e editar).
// Devolve o ano já tratado: um número, ou null se não foi informado.
// ---------------------------------------------------------------------
function validar_livro($titulo, $autor, $ano) {
    if ($titulo === '' || $autor === '') {
        responder(422, 'Título e autor são obrigatórios.');
    }
    if (mb_strlen($titulo) > 150) {
        responder(422, 'O título pode ter no máximo 150 caracteres.');
    }
    if (mb_strlen($autor) > 100) {
        responder(422, 'O autor pode ter no máximo 100 caracteres.');
    }

    // Ano é opcional: se veio vazio, vira null (no banco fica sem valor)
    if ($ano === null || $ano === '') {
        return null;
    }

    // filter_var confere se é um número inteiro (devolve false se não for)
    $ano = filter_var($ano, FILTER_VALIDATE_INT);
    if ($ano === false || $ano < 1 || $ano > (int) date('Y')) {
        responder(422, 'Informe um ano válido.');
    }
    return $ano;
}

// ---------------------------------------------------------------------
// Confere se existe um livro com esse id. Devolve true ou false.
// ---------------------------------------------------------------------
function livro_existe($pdo, $id) {
    $stmt = $pdo->prepare('SELECT id FROM livros WHERE id = ?'); // 1) prepara (? = valor que virá depois)
    $stmt->execute([$id]);                                       // 2) executa, colocando $id no lugar do ?
    return (bool) $stmt->fetch();                                // 3) fetch() devolve a linha, ou false se não achou
}


// =====================================================================
// PROTEÇÃO: só entra quem está logado.
// $_SESSION['usuario_id'] é gravado pelo login (Pessoa 1).
// =====================================================================
if (!isset($_SESSION['usuario_id'])) {
    responder(401, 'Você precisa estar logado.');
}

// Descobre se a requisição foi GET ou POST
$metodo = $_SERVER['REQUEST_METHOD'];

// "try" tenta executar. Se o banco der erro, o "catch" (no final) trata.
try {

    // =================================================================
    // GET -> LISTAR (ou buscar um livro só)
    // =================================================================
    if ($metodo === 'GET') {

        // Se veio ?id=3 na URL, devolve só aquele livro
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id']; // (int) garante que é número

            $stmt = $pdo->prepare('SELECT id, titulo, autor, ano, disponivel FROM livros WHERE id = ?');
            $stmt->execute([$id]);
            $livro = $stmt->fetch();

            if (!$livro) {
                responder(404, 'Livro não encontrado.');
            }
            responder(200, 'Livro encontrado.', $livro);
        }

        // Senão, devolve todos os livros.
        // Não tem dado do usuário no SQL, então pode usar query() direto.
        $stmt  = $pdo->query('SELECT id, titulo, autor, ano, disponivel FROM livros ORDER BY titulo');
        $lista = $stmt->fetchAll(); // todas as linhas como array

        responder(200, 'Livros listados.', $lista);
    }

    // =================================================================
    // POST -> CRIAR, EDITAR ou EXCLUIR (depende do campo "acao")
    // =================================================================
    elseif ($metodo === 'POST') {

        // PROTEÇÃO: todas as ações de POST são só para administrador.
        if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
            responder(403, 'Apenas administradores podem fazer isso.');
        }

        // Lê o JSON enviado pelo fetch (mesma técnica do cadastro.php).
        // Se não vier JSON válido, $dados vira um array vazio.
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!is_array($dados)) {
            $dados = [];
        }

        // O "?? ''" evita erro se a chave não existir
        $acao = $dados['acao'] ?? '';

        // ------------------------- CRIAR -----------------------------
        if ($acao === 'criar') {

            // trim() tira espaços do começo e do fim
            $titulo = trim($dados['titulo'] ?? '');
            $autor  = trim($dados['autor'] ?? '');
            $ano    = validar_livro($titulo, $autor, $dados['ano'] ?? null);

            // Prepared statement: cada ? recebe um valor do array, na ordem
            $stmt = $pdo->prepare('INSERT INTO livros (titulo, autor, ano) VALUES (?, ?, ?)');
            $stmt->execute([$titulo, $autor, $ano]);

            // lastInsertId() = id que o banco acabou de gerar
            responder(201, 'Livro cadastrado com sucesso.', ['id' => (int) $pdo->lastInsertId()]);
        }

        // ------------------------- EDITAR ----------------------------
        elseif ($acao === 'editar') {

            $id     = (int) ($dados['id'] ?? 0);
            $titulo = trim($dados['titulo'] ?? '');
            $autor  = trim($dados['autor'] ?? '');

            if ($id < 1) {
                responder(422, 'Informe o id do livro.');
            }
            $ano = validar_livro($titulo, $autor, $dados['ano'] ?? null);

            if (!livro_existe($pdo, $id)) {
                responder(404, 'Livro não encontrado.');
            }

            // Os valores do array entram na ordem dos "?"
            $stmt = $pdo->prepare('UPDATE livros SET titulo = ?, autor = ?, ano = ? WHERE id = ?');
            $stmt->execute([$titulo, $autor, $ano, $id]);

            responder(200, 'Livro atualizado com sucesso.');
        }

        // ------------------------- EXCLUIR ---------------------------
        elseif ($acao === 'excluir') {

            $id = (int) ($dados['id'] ?? 0);

            if ($id < 1) {
                responder(422, 'Informe o id do livro.');
            }
            if (!livro_existe($pdo, $id)) {
                responder(404, 'Livro não encontrado.');
            }

            // RESPEITAR O RELACIONAMENTO:
            // se o livro já tem empréstimos, não pode ser excluído.
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE livro_id = ?');
            $stmt->execute([$id]);

            // fetchColumn() devolve o valor da primeira coluna (aqui, o total)
            if ((int) $stmt->fetchColumn() > 0) {
                responder(409, 'Não é possível excluir: o livro possui empréstimos registrados.');
            }

            $stmt = $pdo->prepare('DELETE FROM livros WHERE id = ?');
            $stmt->execute([$id]);

            responder(200, 'Livro excluído com sucesso.');
        }

        // ------------------- ação desconhecida -----------------------
        else {
            responder(422, 'Ação inválida. Use: criar, editar ou excluir.');
        }
    }

    // =================================================================
    // Qualquer outro método (PUT, DELETE...) não é aceito aqui
    // =================================================================
    else {
        responder(405, 'Método não permitido.');
    }

} catch (PDOException $e) {
    // Se qualquer comando SQL der erro, cai aqui.
    // (Não mostramos $e->getMessage() ao usuário por segurança.)
    responder(500, 'Erro interno no banco de dados.');
}