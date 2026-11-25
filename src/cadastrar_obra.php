<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

function redirecionarAdicionar(
    string $tipo,
    string $mensagem
): never {

    $_SESSION['mensagem_obra'] = [
        'tipo' => $tipo,
        'texto' => $mensagem
    ];

    header('Location: /adicionar.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('Método não permitido.');
}

$titulo = trim(
    (string) ($_POST['titulo'] ?? '')
);

$autor = trim(
    (string) ($_POST['autor'] ?? '')
);

$plataforma = trim(
    (string) ($_POST['plataforma'] ?? '')
);

$linkUrl = trim(
    (string) ($_POST['link_url'] ?? '')
);

$estanteId = (int)(
    $_POST['estante_id'] ?? 0
);

$usuarioId = (int)(
    $_SESSION['usuario_id'] ?? 0
);
$capitulosLidos = (int) (
        $_POST['capitulos_lidos']
        ?? 0
    );

$totalCapitulos = (int) (
        $_POST['total_capitulos']
        ?? 0
    );

if (
    $titulo === '' ||
    $autor === '' ||
    $plataforma === ''
) {

    redirecionarAdicionar(
        'erro',
        'Preencha todos os campos obrigatórios.'
    );
}

if (
    $linkUrl !== '' &&
    filter_var($linkUrl, FILTER_VALIDATE_URL) === false
) {

    redirecionarAdicionar(
        'erro',
        'Informe uma URL válida.'
    );
}

if ($estanteId <= 0) {

    redirecionarAdicionar(
        'erro',
        'Selecione uma estante.'
    );
}

try {

    $pdo = conectarBanco();

    $verificar = $pdo->prepare(
        '
        SELECT id
        FROM obras
        WHERE
            usuario_id = :usuario_id
            AND lower(titulo) = lower(:titulo)
            AND lower(autor) = lower(:autor)
            AND lower(plataforma) = lower(:plataforma)
        LIMIT 1
        '
    );

    $verificar->execute([
        'usuario_id' => $usuarioId,
        'titulo' => $titulo,
        'autor' => $autor,
        'plataforma' => $plataforma
    ]);

    if ($verificar->fetch()) {

        redirecionarAdicionar(
            'erro',
            'Essa obra já foi cadastrada.'
        );
    }

    $pdo->beginTransaction();

    $inserirObra = $pdo->prepare(
        '
        INSERT INTO obras (
            usuario_id,
            titulo,
            autor,
            plataforma,
            capitulos_lidos,
            total_capitulos,
            link_url
        )
        VALUES (
            :usuario_id,
            :titulo,
            :autor,
            :plataforma,
            :capitulos_lidos,
            :total_capitulos,
            :link_url
        )
        RETURNING id
        '
    );

    $inserirObra->execute([
        'usuario_id' => $usuarioId,
        'titulo' => $titulo,
        'autor' => $autor,
        'plataforma' => $plataforma,
        'capitulos_lidos' => $capitulosLidos,
        'total_capitulos' => $totalCapitulos,
        'link_url' =>
            $linkUrl !== ''
            ? $linkUrl
            : null
    ]);

    $obraId = $inserirObra->fetchColumn();

    $inserirEstante = $pdo->prepare(
        '
        INSERT INTO obras_estantes (
            obra_id,
            estante_id
        )
        VALUES (
            :obra_id,
            :estante_id
        )
        '
    );

    $inserirEstante->execute([
        'obra_id' => $obraId,
        'estante_id' => $estanteId
    ]);

    $pdo->commit();

    $_SESSION['mensagem_obra'] = [
        'tipo' => 'sucesso',
        'texto' => 'Obra cadastrada com sucesso.'
    ];

    header('Location: /perfil.php');

    exit;

} catch (Throwable $erro) {

    if (
        isset($pdo)
        && $pdo->inTransaction()
    ) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());

    redirecionarAdicionar(
        'erro',
        'Erro ao cadastrar a obra.'
    );
}