<?php
// =====================================================================
// emprestimos.php  -  usa o RELACIONAMENTO entre usuários e livros
//
// Como o frontend chama este arquivo (via fetch):
//
//   MEUS EMPRÉSTIMOS -> GET   emprestimos.php
//   EMPRESTAR        -> POST  emprestimos.php  {"acao":"emprestar", "livro_id":3}
//   DEVOLVER         -> POST  emprestimos.php  {"acao":"devolver",  "id":7}
//   EXCLUIR REGISTRO -> POST  emprestimos.php  {"acao":"excluir",   "id":7}
//
// O POST envia JSON no corpo (igual ao cadastro.php).
//
// RESPOSTAS (mesmo padrão do cadastro.php):
//   erro    -> status 4xx/5xx  {"erro": "mensagem"}
//   sucesso -> status 200/201  {"mensagem": "...", "dados": ...}
//
// IMPORTANTE: o id do usuário NUNCA vem do frontend.
// Ele vem da sessão ($_SESSION), que só o servidor controla.
// Senão qualquer pessoa poderia pegar livro "em nome" de outra.
// =====================================================================

// Liga a sessão (para saber quem está logado)
session_start();

// Avisa o navegador que a resposta será JSON
header('Content-Type: application/json; charset=utf-8');

// Traz a conexão da Pessoa 1 (cria a variável $pdo)
require __DIR__ . '/../config/conexao.php';


// ---------------------------------------------------------------------
// responder(): monta a resposta JSON e PARA o script (exit).
//   erro    (código >= 400) -> {"erro": "..."}
//   sucesso (código < 400)  -> {"mensagem": "...", "dados": ...}
// ---------------------------------------------------------------------
function responder($codigo, $mensagem, $dados = null) {
    http_response_code($codigo);

    if ($codigo >= 400) {
        $resposta = ['erro' => $mensagem];
    } else {
        $resposta = ['mensagem' => $mensagem];
        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }
    }

    echo json_encode($resposta);
    exit;
}


// =====================================================================
// PROTEÇÃO: tudo aqui exige login.
// =====================================================================
if (!isset($_SESSION['usuario_id'])) {
    responder(401, 'Você precisa estar logado.');
}

// id de quem está logado (guardado na sessão pelo login)
$usuario_id = (int) $_SESSION['usuario_id'];

$metodo = $_SERVER['REQUEST_METHOD'];

try {

    // =================================================================
    // GET -> LISTAR os empréstimos do usuário logado
    // =================================================================
    if ($metodo === 'GET') {

        // JOIN = junta duas tabelas. Aqui juntamos "emprestimos" com "livros"
        // para mostrar o TÍTULO do livro (e não só o número do livro_id).
        // WHERE e.usuario_id = ? -> só os empréstimos DESTE usuário.
        $sql = 'SELECT e.id, e.livro_id, l.titulo, l.autor,
                       e.data_emprestimo, e.data_devolucao
                FROM emprestimos e
                JOIN livros l ON l.id = e.livro_id
                WHERE e.usuario_id = ?
                ORDER BY e.data_emprestimo DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        $lista = $stmt->fetchAll();

        responder(200, 'Empréstimos listados.', $lista);
    }

    // =================================================================
    // POST -> EMPRESTAR, DEVOLVER ou EXCLUIR
    // =================================================================
    elseif ($metodo === 'POST') {

        // Lê o JSON enviado pelo fetch (mesma técnica do cadastro.php)
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!is_array($dados)) {
            $dados = [];
        }

        $acao = $dados['acao'] ?? '';

        // ----------------------- EMPRESTAR ---------------------------
        if ($acao === 'emprestar') {

            $livro_id = (int) ($dados['livro_id'] ?? 0);

            if ($livro_id < 1) {
                responder(422, 'Informe o livro que deseja emprestar.');
            }

            // 1) O livro existe?
            $stmt = $pdo->prepare('SELECT id FROM livros WHERE id = ?');
            $stmt->execute([$livro_id]);
            if (!$stmt->fetch()) {
                responder(404, 'Livro não encontrado.');
            }

            // 2) Tenta "reservar" o livro: só muda para indisponível
            //    SE ele ainda estiver disponível (disponivel = 1).
            //    Num único UPDATE, evita que duas pessoas
            //    peguem o mesmo livro ao mesmo tempo.
            $stmt = $pdo->prepare('UPDATE livros SET disponivel = 0 WHERE id = ? AND disponivel = 1');
            $stmt->execute([$livro_id]);

            // rowCount() = quantas linhas o UPDATE mudou.
            // Se foi 0, o livro já estava emprestado.
            if ($stmt->rowCount() === 0) {
                responder(409, 'Este livro não está disponível no momento.');
            }

            // 3) Registra o empréstimo: liga o usuário ao livro (as duas chaves estrangeiras)
            $stmt = $pdo->prepare('INSERT INTO emprestimos (usuario_id, livro_id) VALUES (?, ?)');
            $stmt->execute([$usuario_id, $livro_id]);

            responder(201, 'Empréstimo registrado com sucesso.', ['id' => (int) $pdo->lastInsertId()]);
        }

        // ------------------------ DEVOLVER ---------------------------
        elseif ($acao === 'devolver') {

            $id = (int) ($dados['id'] ?? 0);

            if ($id < 1) {
                responder(422, 'Informe o id do empréstimo.');
            }

            // Busca o empréstimo para conferir de quem é e se já foi devolvido
            $stmt = $pdo->prepare('SELECT usuario_id, livro_id, data_devolucao FROM emprestimos WHERE id = ?');
            $stmt->execute([$id]);
            $emp = $stmt->fetch(); // false se não achou

            if (!$emp) {
                responder(404, 'Empréstimo não encontrado.');
            }
            // Só o dono do empréstimo pode devolver
            if ((int) $emp['usuario_id'] !== $usuario_id) {
                responder(403, 'Você só pode devolver os seus próprios empréstimos.');
            }
            // data_devolucao preenchida = já devolvido
            if ($emp['data_devolucao'] !== null) {
                responder(409, 'Este livro já foi devolvido.');
            }

            // Marca a data de devolução (NOW() = data e hora de agora)
            $stmt = $pdo->prepare('UPDATE emprestimos SET data_devolucao = NOW() WHERE id = ?');
            $stmt->execute([$id]);

            // Libera o livro para outras pessoas
            $stmt = $pdo->prepare('UPDATE livros SET disponivel = 1 WHERE id = ?');
            $stmt->execute([(int) $emp['livro_id']]);

            responder(200, 'Livro devolvido com sucesso.');
        }

        // ------------------- EXCLUIR O REGISTRO ----------------------
        elseif ($acao === 'excluir') {

            $id = (int) ($dados['id'] ?? 0);

            if ($id < 1) {
                responder(422, 'Informe o id do empréstimo.');
            }

            $stmt = $pdo->prepare('SELECT usuario_id, data_devolucao FROM emprestimos WHERE id = ?');
            $stmt->execute([$id]);
            $emp = $stmt->fetch();

            if (!$emp) {
                responder(404, 'Empréstimo não encontrado.');
            }
            if ((int) $emp['usuario_id'] !== $usuario_id) {
                responder(403, 'Você só pode excluir os seus próprios registros.');
            }
            // Não pode apagar um empréstimo em andamento (o livro ficaria "perdido")
            if ($emp['data_devolucao'] === null) {
                responder(409, 'Devolva o livro antes de excluir o registro.');
            }

            $stmt = $pdo->prepare('DELETE FROM emprestimos WHERE id = ?');
            $stmt->execute([$id]);

            responder(200, 'Registro de empréstimo excluído.');
        }

        else {
            responder(422, 'Ação inválida. Use: emprestar, devolver ou excluir.');
        }
    }

    else {
        responder(405, 'Método não permitido.');
    }

} catch (PDOException $e) {
    responder(500, 'Erro interno no banco de dados.');
}