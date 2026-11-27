<?php

declare(strict_types=1);

function carregarEnv(string $caminho): void
{
    if (!is_file($caminho) || !is_readable($caminho)) {
        return; // no Render, as variáveis vêm do painel
    }

    $linhas = file(
        $caminho,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    if ($linhas === false) {
        throw new RuntimeException(
            'Não foi possível ler o arquivo .env.'
        );
    }

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        $posicao = strpos($linha, '=');

        if ($posicao === false) {
            continue;
        }

        $chave = trim(substr($linha, 0, $posicao));
        $valor = trim(substr($linha, $posicao + 1));

        if (
            strlen($valor) >= 2
            && (
                ($valor[0] === '"' && $valor[-1] === '"')
                || ($valor[0] === "'" && $valor[-1] === "'")
            )
        ) {
            $valor = substr($valor, 1, -1);
        }

        if ($chave !== '') {
            putenv($chave . '=' . $valor);
            $_ENV[$chave] = $valor;
        }
    }
}