<?php

declare(strict_types=1);

/*
 * lembrar_login.php — "Lembrar de mim" (RF02).
 *
 * Quando o usuário marca "Lembrar de mim", gravamos um token na tabela
 * tokens_login e um cookie no navegador. Se a sessão expirar, o cookie
 * faz o login voltar sozinho por até 30 dias.
 *
 * O cookie tem "seletor:validador". O banco guarda só o HASH do validador,
 * então quem vir o banco não consegue usar o token. A cada uso o validador
 * é trocado. Se alguém apresentar um seletor com validador errado, o token
 * é revogado (sinal de cookie roubado).
 */

const LEMBRAR_COOKIE = 'lembrar_token';
const LEMBRAR_DIAS = 30;

function opcoesCookieLembrar(int $expira): array
{
    return [
        'expires' => $expira,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,       // JavaScript não consegue ler o cookie
        'samesite' => 'Lax',
    ];
}

function apagarCookieLembrar(): void
{
    if (!headers_sent()) {
        setcookie(LEMBRAR_COOKIE, '', opcoesCookieLembrar(time() - 3600));
    }

    unset($_COOKIE[LEMBRAR_COOKIE]);
}

/**
 * Lê o cookie e devolve [seletor, validador], ou null se não for válido.
 */
function lerCookieLembrar(): ?array
{
    $valor = $_COOKIE[LEMBRAR_COOKIE] ?? '';

    if (
        !is_string($valor)
        || preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $valor, $partes) !== 1
    ) {
        return null;
    }

    return [$partes[1], $partes[2]];
}

/**
 * Cria o token no banco e o cookie no navegador (chamado no login).
 */
function criarLoginLembrado(PDO $pdo, int $usuarioId): void
{
    // Limpeza: tokens vencidos ou revogados deste usuário
    $pdo->prepare(
        'DELETE FROM tokens_login
         WHERE usuario_id = :usuario_id
           AND (expira_em < CURRENT_TIMESTAMP OR revogado_em IS NOT NULL)'
    )->execute(['usuario_id' => $usuarioId]);

    $seletor = bin2hex(random_bytes(12));
    $validador = bin2hex(random_bytes(32));

    $inserir = $pdo->prepare(
        'INSERT INTO tokens_login (usuario_id, seletor, token_hash, expira_em)
         VALUES (
             :usuario_id,
             :seletor,
             :token_hash,
             CURRENT_TIMESTAMP + INTERVAL \'' . LEMBRAR_DIAS . ' days\'
         )
         RETURNING EXTRACT(EPOCH FROM expira_em)::BIGINT'
    );

    $inserir->execute([
        'usuario_id' => $usuarioId,
        'seletor' => $seletor,
        'token_hash' => hash('sha256', $validador),
    ]);

    $expira = (int) $inserir->fetchColumn();

    if (!headers_sent()) {
        setcookie(
            LEMBRAR_COOKIE,
            $seletor . ':' . $validador,
            opcoesCookieLembrar($expira)
        );
    }
}

/**
 * Se não há sessão mas há um cookie válido, faz o login voltar sozinho.
 * Deve ser chamada antes de qualquer saída na página.
 */
function restaurarLoginLembrado(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['usuario_id'])) {
        return;
    }

    $cookie = lerCookieLembrar();

    if ($cookie === null) {
        if (isset($_COOKIE[LEMBRAR_COOKIE])) {
            apagarCookieLembrar();    // cookie com formato inválido
        }

        return;
    }

    [$seletor, $validador] = $cookie;

    try {
        require_once dirname(__DIR__) . '/config/database.php';

        $pdo = conectarBanco();

        $consulta = $pdo->prepare(
            'SELECT
                 t.id,
                 t.token_hash,
                 EXTRACT(EPOCH FROM t.expira_em)::BIGINT AS expira_ts,
                 u.id AS usuario_id,
                 u.nome_exibicao,
                 u.nome_usuario,
                 u.ativo
             FROM tokens_login t
             JOIN usuarios u ON u.id = t.usuario_id
             WHERE t.seletor = :seletor
               AND t.revogado_em IS NULL
               AND t.expira_em > CURRENT_TIMESTAMP
             LIMIT 1'
        );

        $consulta->execute(['seletor' => $seletor]);
        $token = $consulta->fetch();

        if ($token === false) {
            apagarCookieLembrar();
            return;
        }

        if (
            !hash_equals($token['token_hash'], hash('sha256', $validador))
            || !$token['ativo']
        ) {
            // Validador errado: possível cookie roubado. Revoga o token.
            $pdo->prepare(
                'UPDATE tokens_login
                 SET revogado_em = CURRENT_TIMESTAMP
                 WHERE id = :id'
            )->execute(['id' => $token['id']]);

            apagarCookieLembrar();
            return;
        }

        // Token válido: abre a sessão
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = (int) $token['usuario_id'];
        $_SESSION['nome_exibicao'] = $token['nome_exibicao'];
        $_SESSION['nome_usuario'] = $token['nome_usuario'];

        // Troca o validador a cada uso
        $novoValidador = bin2hex(random_bytes(32));

        $pdo->prepare(
            'UPDATE tokens_login
             SET token_hash = :token_hash,
                 ultimo_uso_em = CURRENT_TIMESTAMP
             WHERE id = :id'
        )->execute([
            'token_hash' => hash('sha256', $novoValidador),
            'id' => $token['id'],
        ]);

        if (!headers_sent()) {
            setcookie(
                LEMBRAR_COOKIE,
                $seletor . ':' . $novoValidador,
                opcoesCookieLembrar((int) $token['expira_ts'])
            );
        }
    } catch (Throwable $erro) {
        // Sem banco o login lembrado simplesmente não acontece
        error_log('Erro ao restaurar login lembrado: ' . $erro->getMessage());
    }
}

/**
 * Revoga o token do navegador atual e apaga o cookie (chamado no logout).
 */
function esquecerLoginLembrado(): void
{
    $cookie = lerCookieLembrar();

    if ($cookie !== null) {
        try {
            require_once dirname(__DIR__) . '/config/database.php';

            conectarBanco()->prepare(
                'UPDATE tokens_login
                 SET revogado_em = CURRENT_TIMESTAMP
                 WHERE seletor = :seletor AND revogado_em IS NULL'
            )->execute(['seletor' => $cookie[0]]);
        } catch (Throwable $erro) {
            error_log('Erro ao revogar login lembrado: ' . $erro->getMessage());
        }
    }

    apagarCookieLembrar();
}
