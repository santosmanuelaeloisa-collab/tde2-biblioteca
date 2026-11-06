<?php
// =====================================================================
// usuarios.php  -  listar, editar e excluir usuários (SÓ ADMINISTRADOR)
//
// (Cadastro e login ficam com a Pessoa 1.)
//
//   LISTAR  -> GET   usuarios.php
//   EDITAR  -> POST  usuarios.php  {"acao":"editar",  "id":2, "nome":"..", "email":"..", "perfil":"admin"|"membro"}
//   EXCLUIR -> POST  usuarios.php  {"acao":"excluir", "id":2}
//
// O POST envia JSON no corpo (igual ao cadastro.php).
//
// RESPOSTAS (mesmo padrão do cadastro.php):
//   erro    -> status 4xx/5xx  {"erro": "mensagem"}
//   sucesso -> status 200/201  {"mensagem": "...", "dados": ...}
//
// A senha (senha_hash) NUNCA é enviada na resposta.
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
// PROTEÇÃO: este arquivo inteiro é só para administrador.
// 1) precisa estar logado (senão 401)
// 2) precisa ter perfil 'admin' (senão 403)
// =====================================================================
if (!isset($_SESSION['usuario_id'])) {
    responder(401, 'Você precisa estar logado.');
}
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    responder(403, 'Apenas administradores podem fazer isso.');
}

// id de quem está logado (para o admin não se prejudicar sem querer)
$meu_id = (int) $_SESSION['usuario_id'];

$metodo = $_SERVER['REQUEST_METHOD'];

try {

    // =================================================================
    // GET -> LISTAR usuários
    // =================================================================
    if ($metodo === 'GET') {
        // Repare: não pedimos senha_hash no SELECT
        $stmt = $pdo->query('SELECT id, nome, email, perfil, criado_em FROM usuarios ORDER BY nome');
        responder(200, 'Usuários listados.', $stmt->fetchAll());
    }

    // =================================================================
    // POST -> EDITAR ou EXCLUIR
    // =================================================================
    elseif ($metodo === 'POST') {

        // Lê o JSON enviado pelo fetch (mesma técnica do cadastro.php)
        $dados = json_decode(file_get_contents('php://input'), true);
        if (!is_array($dados)) {
            $dados = [];
        }

        $acao = $dados['acao'] ?? '';
        $id   = (int) ($dados['id'] ?? 0);

        if ($id < 1) {
            responder(422, 'Informe o id do usuário.');
        }

        // O usuário existe? (vale para editar e excluir)
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            responder(404, 'Usuário não encontrado.');
        }

        // ------------------------- EDITAR ----------------------------
        if ($acao === 'editar') {

            $nome   = trim($dados['nome'] ?? '');
            $email  = trim($dados['email'] ?? '');
            $perfil = $dados['perfil'] ?? '';

            // --- validações ---
            if ($nome === '' || $email === '' || $perfil === '') {
                responder(422, 'Nome, e-mail e perfil são obrigatórios.');
            }
            if (mb_strlen($nome) > 100) {
                responder(422, 'O nome pode ter no máximo 100 caracteres.');
            }
            // filter_var confere se o e-mail tem formato válido
            if (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                responder(422, 'Informe um e-mail válido.');
            }
            // O perfil só pode ser um destes dois valores
            if ($perfil !== 'admin' && $perfil !== 'membro') {
                responder(422, "O perfil deve ser 'admin' ou 'membro'.");
            }

            // O admin não pode tirar o próprio cargo de admin
            // (senão o sistema poderia ficar sem nenhum administrador)
            if ($id === $meu_id && $perfil !== 'admin') {
                responder(409, 'Você não pode remover o seu próprio perfil de administrador.');
            }

            // O e-mail não pode pertencer a OUTRO usuário (id <> ?)
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                responder(409, 'Este e-mail já está em uso por outro usuário.');
            }

            // Tudo certo: atualiza
            $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil = ? WHERE id = ?');
            $stmt->execute([$nome, $email, $perfil, $id]);

            responder(200, 'Usuário atualizado com sucesso.');
        }

        // ------------------------- EXCLUIR ---------------------------
        elseif ($acao === 'excluir') {

            // Não pode excluir a si mesmo
            if ($id === $meu_id) {
                responder(409, 'Você não pode excluir a sua própria conta.');
            }

            // RESPEITAR O RELACIONAMENTO: quem tem empréstimos não pode ser excluído
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM emprestimos WHERE usuario_id = ?');
            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {
                responder(409, 'Não é possível excluir: o usuário possui empréstimos registrados.');
            }

            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);

            responder(200, 'Usuário excluído com sucesso.');
        }

        else {
            responder(422, 'Ação inválida. Use: editar ou excluir.');
        }
    }

    else {
        responder(405, 'Método não permitido.');
    }

} catch (PDOException $e) {
    responder(500, 'Erro interno no banco de dados.');
}