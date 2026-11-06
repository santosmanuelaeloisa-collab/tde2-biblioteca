<?php
// =====================================================================
// login_teste.php  -  APENAS PARA TESTE!  APAGUE ANTES DE ENTREGAR!
//
// Finge que você fez login, gravando na sessão as mesmas duas coisas
// que o login de verdade (da Pessoa 1) vai gravar.
//
// Como usar (no navegador):
//   http://localhost/biblioteca/api/login_teste.php                    -> admin, usuário 1
//   http://localhost/biblioteca/api/login_teste.php?id=2&perfil=membro -> membro, usuário 2
// =====================================================================

session_start();

// $_GET['id'] vem da URL. Se não vier nada, usa 1.
$_SESSION['usuario_id'] = (int) ($_GET['id'] ?? 1);
$_SESSION['perfil']     = $_GET['perfil'] ?? 'admin';

echo 'Login de teste feito: usuario_id = ' . $_SESSION['usuario_id']
   . ', perfil = ' . $_SESSION['perfil']
   . '. APAGUE este arquivo antes da entrega!';