<?php

declare(strict_types=1);

require_once __DIR__ . '/sessao.php';
require_once dirname(__DIR__) . '/config/database.php';

function realizarLogin(): void
{
    exigirPost();
    iniciarSessao();

    $login = trim((string) ($_POST['login'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $lembrar = ($_POST['lembrar'] ?? '') === '1';

    try {
        $pdo = conectarBanco();

        $stmt = $pdo->prepare(
            'SELECT id, nome_exibicao, nome_usuario, senha_hash, ativo
             FROM usuarios
             WHERE lower(email) = lower(:login)
                OR lower(nome_usuario) = lower(:login)
             LIMIT 1'
        );
        $stmt->execute(['login' => $login]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $erro) {
        error_log($erro->getMessage());
        guardarMensagem('mensagem_login', 'erro', 'Não foi possível entrar agora. Tente novamente.');
        redirecionar('/login.php');
    }

    if (
        $login === ''
        || !$usuario
        || !$usuario['ativo']
        || !password_verify($senha, $usuario['senha_hash'])
    ) {
        redirecionar('/login.php?erro=1');
    }

    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['nome_exibicao'] = $usuario['nome_exibicao'];
    $_SESSION['nome_usuario'] = $usuario['nome_usuario'];

    if ($lembrar) {
        try {
            criarLoginLembrado($pdo, (int) $usuario['id']);
        } catch (Throwable $erro) {
            error_log($erro->getMessage());
        }
    }

    redirecionar('/perfil.php');
}
