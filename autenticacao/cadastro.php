<?php
require __DIR__ . '/../config/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);
$nome  = trim($dados['nome'] ?? '');
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
    http_response_code(422);
    echo json_encode(['erro' => 'Informe nome, e-mail válido e senha com pelo menos 6 caracteres.']);
    exit;
}

$st = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
    http_response_code(409);
    echo json_encode(['erro' => 'Este e-mail já está cadastrado.']);
    exit;
}

$total  = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$perfil = ($total == 0) ? 'admin' : 'membro';

$st = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)');
$st->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);

http_response_code(201);
echo json_encode(['mensagem' => 'Cadastro realizado com sucesso!']);