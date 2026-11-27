<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/sessao.php';

exigirPost();
iniciarSessao();

esquecerLoginLembrado();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $p['path'],
        'domain' => $p['domain'],
        'secure' => $p['secure'],
        'httponly' => $p['httponly'],
    ]);
}

session_destroy();

redirecionar('/login.php');
