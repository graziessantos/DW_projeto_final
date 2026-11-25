<?php

require_once __DIR__ . '/../config/database.php';

function realizarLogin()
{
    $pdo = conectarBanco();

    $login = trim($_POST['login']);
    $senha = $_POST['senha'];

    $sql = "
        SELECT *
        FROM usuarios
        WHERE
            LOWER(email) = LOWER(:login)
            OR
            LOWER(nome_usuario) = LOWER(:login)
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'login' => $login
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        !$usuario ||
        !password_verify(
            $senha,
            $usuario['senha_hash']
        )
    ) {

        header(
            'Location: ../public/login.php?erro=1'
        );
        exit;
    }

    session_start();

    $_SESSION['usuario_id'] =
        $usuario['id'];

    $_SESSION['nome_exibicao'] =
        $usuario['nome_exibicao'];

    $_SESSION['nome_usuario'] =
        $usuario['nome_usuario'];

    header(
        'Location: ../perfil.php'
    );

    exit;
}