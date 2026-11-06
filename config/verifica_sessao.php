<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function exigir_login(): array {
    if (empty($_SESSION['usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Você precisa fazer login.']);
        exit;
    }
    return $_SESSION['usuario'];
}

function exigir_admin(): array {
    $usuario = exigir_login();
    if ($usuario['perfil'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Apenas administradores podem fazer isso.']);
        exit;
    }
    return $usuario;
}