<?php
// Copie este arquivo para conexao.php e coloque a sua senha do MySQL.
$host  = '127.0.0.1';
$porta = 3306;
$banco = 'biblioteca_comunitaria';
$user  = 'root';
$senha = 'COLOQUE_SUA_SENHA_AQUI';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4",
        $user,
        $senha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}