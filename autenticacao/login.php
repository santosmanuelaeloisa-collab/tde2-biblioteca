<?php
session_start();
require __DIR__ . '/../config/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

if ($email === '' || $senha === '') {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe e-mail e senha.']);
    exit;
}

$st = $pdo->prepare('SELECT id, nome, senha_hash, perfil FROM usuarios WHERE email = ?');
$st->execute([$email]);
$usuario = $st->fetch();

if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'E-mail ou senha inválidos.']);
    exit;
}
$_SESSION['usuario'] = [
    'id'     => (int) $usuario['id'],
    'nome'   => $usuario['nome'],
    'perfil' => $usuario['perfil']
];
// Formato que a API da Pessoa 2 espera:
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['nome']       = $usuario['nome'];
$_SESSION['perfil']     = $usuario['perfil'];

echo json_encode([
    'mensagem' => 'Login realizado com sucesso!',
    'nome'     => $usuario['nome'],
    'perfil'   => $usuario['perfil']
]);