<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/sessao.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

exigirPost();
$usuarioId = exigirLoginNaAcao();

$id = (int) ($_POST['id'] ?? 0);
$voltar = '/editar_obra.php?id=' . $id;

function erroEdicao(string $voltar, string $texto): never
{
    guardarMensagem('mensagem_obra', 'erro', $texto);
    redirecionar($voltar);
}

if ($id <= 0) {
    redirecionar('/perfil.php');
}

$titulo = trim((string) ($_POST['titulo'] ?? ''));
$autor = trim((string) ($_POST['autor'] ?? ''));
$plataforma = trim((string) ($_POST['plataforma'] ?? ''));
$linkUrl = trim((string) ($_POST['link_url'] ?? ''));
$lidos = lerCapitulos($_POST['capitulos_lidos'] ?? '');
$total = lerCapitulos($_POST['total_capitulos'] ?? '');
$temCapitulos = isset($_POST['capitulos_lidos']) || isset($_POST['total_capitulos']);

try {
    $pdo = conectarBanco();

    $busca = $pdo->prepare(
        'SELECT id, origem, capitulos_lidos, total_capitulos
         FROM obras WHERE id = :id AND usuario_id = :u'
    );
    $busca->execute(['id' => $id, 'u' => $usuarioId]);
    $atual = $busca->fetch(PDO::FETCH_ASSOC);

    if (!$atual) {
        redirecionar('/perfil.php');
    }

    // Obra da Open Library mantém a plataforma (regra do banco)
    if ($atual['origem'] === 'open_library') {
        $plataforma = 'Open Library';
    }

    if ($titulo === '' || $autor === '' || $plataforma === '') {
        erroEdicao($voltar, 'Preencha título, autor e plataforma.');
    }

    if (mb_strlen($titulo) > 255 || mb_strlen($autor) > 255 || mb_strlen($plataforma) > 100) {
        erroEdicao($voltar, 'Algum campo ultrapassou o tamanho permitido.');
    }

    if (
        $linkUrl !== ''
        && (!preg_match('#^https?://#i', $linkUrl) || filter_var($linkUrl, FILTER_VALIDATE_URL) === false)
    ) {
        erroEdicao($voltar, 'Informe uma URL válida.');
    }

    // Se a tela não enviou capítulos, mantém os que já estavam
    if (!$temCapitulos) {
        $lidosFinal = (int) $atual['capitulos_lidos'];
        $totalFinal = $atual['total_capitulos'] === null ? null : (int) $atual['total_capitulos'];
    } else {
        if ($lidos === false || $total === false || ($total !== null && $total <= 0)) {
            erroEdicao($voltar, 'Informe números válidos de capítulos.');
        }

        $lidosFinal = $lidos ?? 0;
        $totalFinal = $total;

        if ($totalFinal !== null && $lidosFinal > $totalFinal) {
            erroEdicao($voltar, 'Os capítulos lidos não podem passar do total.');
        }
    }

    $up = $pdo->prepare(
        'UPDATE obras
         SET titulo = :t, autor = :a, plataforma = :p, link_url = :l,
             capitulos_lidos = :cl, total_capitulos = :tc
         WHERE id = :id AND usuario_id = :u'
    );
    $up->bindValue(':t', $titulo);
    $up->bindValue(':a', $autor);
    $up->bindValue(':p', $plataforma);
    $up->bindValue(':l', $linkUrl !== '' ? $linkUrl : null, $linkUrl !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $up->bindValue(':cl', $lidosFinal, PDO::PARAM_INT);
    $up->bindValue(':tc', $totalFinal, $totalFinal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $up->bindValue(':id', $id, PDO::PARAM_INT);
    $up->bindValue(':u', $usuarioId, PDO::PARAM_INT);
    $up->execute();

    guardarMensagem('mensagem_obra', 'sucesso', 'Obra atualizada com sucesso.');
    redirecionar('/perfil.php');
} catch (Throwable $erro) {
    error_log($erro->getMessage());

    if ($erro instanceof PDOException && $erro->getCode() === '23505') {
        erroEdicao($voltar, 'Você já tem uma obra com esse título, autor e plataforma.');
    }

    erroEdicao($voltar, 'Erro ao atualizar a obra.');
}
