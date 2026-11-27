<?php

/*
 * session.php — protege as páginas que exigem login (RN02).
 * Use no topo da página:  require_once '../config/session.php';
 */

require_once __DIR__ . '/../src/lembrar_login.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Se a sessão expirou mas o usuário marcou "Lembrar de mim", o login volta aqui
restaurarLoginLembrado();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}
