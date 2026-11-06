<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Você precisa fazer login.']);
    exit;
}

echo json_encode([
    'logado'  => true,
    'usuario' => $_SESSION['usuario']
]);