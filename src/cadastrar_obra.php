<?php

declare(strict_types=1);

require_once __DIR__ . '/sessao.php';
require_once dirname(__DIR__) . '/config/database.php';

function redirecionarAdicionar(string $tipo, string $mensagem): never
{
    guardarMensagem('mensagem_obra', $tipo, $mensagem);
    redirecionar('/adicionar.php');
}

/**
 * Cadastra uma obra (manual ou da Open Library) em uma ou mais estantes (RF08-RF10).
 */
function cadastrarObra(): void
{
    exigirPost();
    $usuarioId = exigirLoginNaAcao();

    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $autor = trim((string) ($_POST['autor'] ?? ''));
    $plataforma = trim((string) ($_POST['plataforma'] ?? ''));
    $linkUrl = trim((string) ($_POST['link_url'] ?? ''));
    $origem = ($_POST['origem'] ?? 'manual') === 'open_library' ? 'open_library' : 'manual';
    $chaveOL = trim((string) ($_POST['open_library_key'] ?? ''));
    $estantes = lerIdsEstantes($_POST['estantes'] ?? ($_POST['estante_id'] ?? []));
    $lidos = lerCapitulos($_POST['capitulos_lidos'] ?? '');
    $total = lerCapitulos($_POST['total_capitulos'] ?? '');

    // Guarda o que foi digitado para a tela repreencher se houver erro
    $_SESSION['dados_obra'] = [
        'titulo' => $titulo,
        'autor' => $autor,
        'plataforma' => $plataforma,
        'link_url' => $linkUrl,
    ];

    if ($origem === 'open_library') {
        $plataforma = 'Open Library';
    } else {
        $chaveOL = '';
    }

    if ($titulo === '' || $autor === '' || $plataforma === '') {
        redirecionarAdicionar('erro', 'Preencha todos os campos obrigatórios.');
    }

    if (
        mb_strlen($titulo) > 255 || mb_strlen($autor) > 255
        || mb_strlen($plataforma) > 100 || mb_strlen($linkUrl) > 2048
    ) {
        redirecionarAdicionar('erro', 'Algum campo ultrapassou o tamanho permitido.');
    }

    if ($linkUrl !== '' && !preg_match('#^https?://#i', $linkUrl)) {
        redirecionarAdicionar('erro', 'Informe uma URL válida, começando com http:// ou https://.');
    }

    if ($linkUrl !== '' && filter_var($linkUrl, FILTER_VALIDATE_URL) === false) {
        redirecionarAdicionar('erro', 'Informe uma URL válida.');
    }

    if ($estantes === []) {
        redirecionarAdicionar('erro', 'Selecione pelo menos uma estante.');
    }

    if ($lidos === false || $total === false || ($total !== null && $total <= 0)) {
        redirecionarAdicionar('erro', 'Informe números válidos de capítulos.');
    }

    if ($total !== null && ($lidos ?? 0) > $total) {
        redirecionarAdicionar('erro', 'Os capítulos lidos não podem passar do total.');
    }

    if ($lidos !== null && $total === null && $lidos < 0) {
        redirecionarAdicionar('erro', 'Informe números válidos de capítulos.');
    }

    try {
        $pdo = conectarBanco();

        $verificar = $pdo->prepare(
            'SELECT 1 FROM obras
             WHERE usuario_id = :u
               AND lower(btrim(titulo)) = lower(btrim(:t))
               AND lower(btrim(autor)) = lower(btrim(:a))
               AND lower(btrim(plataforma)) = lower(btrim(:p))
             LIMIT 1'
        );
        $verificar->execute(['u' => $usuarioId, 't' => $titulo, 'a' => $autor, 'p' => $plataforma]);

        if ($verificar->fetch()) {
            redirecionarAdicionar('erro', 'Essa obra já foi cadastrada.');
        }

        $pdo->beginTransaction();

        $inserir = $pdo->prepare(
            'INSERT INTO obras
                (usuario_id, titulo, autor, plataforma, capitulos_lidos,
                 total_capitulos, link_url, origem, open_library_key)
             VALUES
                (:u, :t, :a, :p, :cl, :tc, :l, :o, :k)
             RETURNING id'
        );
        $inserir->bindValue(':u', $usuarioId, PDO::PARAM_INT);
        $inserir->bindValue(':t', $titulo);
        $inserir->bindValue(':a', $autor);
        $inserir->bindValue(':p', $plataforma);
        $inserir->bindValue(':cl', $lidos ?? 0, PDO::PARAM_INT);
        $inserir->bindValue(':tc', $total, $total === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $inserir->bindValue(':l', $linkUrl !== '' ? $linkUrl : null, $linkUrl !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $inserir->bindValue(':o', $origem);
        $inserir->bindValue(':k', $chaveOL !== '' ? $chaveOL : null, $chaveOL !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $inserir->execute();

        $obraId = (int) $inserir->fetchColumn();

        $vincular = $pdo->prepare(
            'INSERT INTO obras_estantes (obra_id, estante_id) VALUES (:o, :e)'
        );

        foreach ($estantes as $estanteId) {
            $vincular->execute(['o' => $obraId, 'e' => $estanteId]);
        }

        $pdo->commit();

        unset($_SESSION['dados_obra']);
        guardarMensagem('mensagem_obra', 'sucesso', 'Obra cadastrada com sucesso.');
        redirecionar('/perfil.php?estante=' . $estantes[0]);
    } catch (Throwable $erro) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($erro->getMessage());

        if ($erro instanceof PDOException && $erro->getCode() === '23505') {
            redirecionarAdicionar('erro', 'Essa obra já foi cadastrada.');
        }

        redirecionarAdicionar('erro', 'Erro ao cadastrar a obra.');
    }
}
