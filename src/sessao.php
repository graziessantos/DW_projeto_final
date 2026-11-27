<?php

declare(strict_types=1);

/*
 * sessao.php — funções comuns das ações (arquivos de public/actions).
 * Cuida da sessão, de exigir login e de ler os dados enviados pelas telas.
 */

require_once __DIR__ . '/lembrar_login.php';

/** As quatro estantes fixas (os ids são os da tabela "estantes"). */
function nomesEstantes(): array
{
    return [
        1 => 'Lendo',
        2 => 'Lido',
        3 => 'Quero Ler',
        4 => 'Favoritos',
    ];
}

function iniciarSessao(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Id do usuário logado, ou null. Se a sessão expirou mas o usuário marcou
 * "Lembrar de mim", o login volta sozinho aqui.
 */
function usuarioIdAtual(): ?int
{
    iniciarSessao();
    restaurarLoginLembrado();

    $id = (int) ($_SESSION['usuario_id'] ?? 0);

    return $id > 0 ? $id : null;
}

/** Ação só para quem está logado (RN02). Sem login, vai para a tela de login. */
function exigirLoginNaAcao(): int
{
    $usuarioId = usuarioIdAtual();

    if ($usuarioId === null) {
        header('Location: /login.php');
        exit;
    }

    return $usuarioId;
}

/** As ações que alteram dados aceitam somente POST. */
function exigirPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');

        exit('Método não permitido.');
    }
}

/** Guarda uma mensagem na sessão para a próxima tela mostrar. */
function guardarMensagem(string $chave, string $tipo, string $texto): void
{
    iniciarSessao();

    $_SESSION[$chave] = [
        'tipo' => $tipo,
        'texto' => $texto,
    ];
}

/** Redireciona e encerra. */
function redirecionar(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Converte o que veio do formulário em uma lista de ids de estante válidos
 * (1 a 4, sem repetir). Aceita um vetor (estantes[]) ou um id solto.
 */
function lerIdsEstantes(mixed $valor): array
{
    $lista = is_array($valor) ? $valor : [$valor];
    $ids = [];

    foreach ($lista as $item) {
        if (!is_scalar($item) || !preg_match('/^[1-4]$/', (string) $item)) {
            continue;
        }

        $ids[(int) $item] = (int) $item;
    }

    return array_values($ids);
}

/**
 * Lê um número de capítulos. Devolve null se estiver vazio (fica NULL no
 * banco) e false se não for um inteiro válido.
 */
function lerCapitulos(mixed $valor): int|false|null
{
    $texto = trim((string) $valor);

    if ($texto === '') {
        return null;
    }

    if (preg_match('/^\d{1,9}$/', $texto) !== 1) {
        return false;
    }

    return (int) $texto;
}
