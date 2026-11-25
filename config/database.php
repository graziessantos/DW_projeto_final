<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

carregarEnv(dirname(__DIR__) . '/.env');

function conectarBanco(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = getenv('DATABASE_URL');

    if ($databaseUrl === false || trim($databaseUrl) === '') {
        throw new RuntimeException(
            'A variável DATABASE_URL não foi configurada.'
        );
    }

    $dados = parse_url($databaseUrl);

    if ($dados === false) {
        throw new RuntimeException(
            'A variável DATABASE_URL possui formato inválido.'
        );
    }

    $host = $dados['host'] ?? null;
    $port = $dados['port'] ?? 5432;
    $usuario = isset($dados['user'])
        ? rawurldecode($dados['user'])
        : null;
    $senha = isset($dados['pass'])
        ? rawurldecode($dados['pass'])
        : null;
    $nomeBanco = isset($dados['path'])
        ? ltrim($dados['path'], '/')
        : null;

    if (
        !$host
        || !$usuario
        || !$senha
        || !$nomeBanco
    ) {
        throw new RuntimeException(
            'A DATABASE_URL está incompleta.'
        );
    }

    $dsn = sprintf(
        'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
        $host,
        $port,
        $nomeBanco
    );

    try {
        $pdo = new PDO(
            $dsn,
            $usuario,
            $senha,
            [
                PDO::ATTR_ERRMODE =>
                    PDO::ERRMODE_EXCEPTION,

                PDO::ATTR_DEFAULT_FETCH_MODE =>
                    PDO::FETCH_ASSOC,

                PDO::ATTR_EMULATE_PREPARES =>
                    false,
            ]
        );

        return $pdo;
    } catch (PDOException $erro) {
        error_log(
            'Erro de conexão com o banco: '
            . $erro->getMessage()
        );

        throw new RuntimeException(
            'Não foi possível conectar ao banco de dados.'
        );
    }
}