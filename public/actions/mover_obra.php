<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/sessao.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

exigirPost();
$usuarioId = exigirLoginNaAcao();

$obraId = (int) ($_POST['obra_id'] ?? 0);
$estanteId = lerIdsEstantes($_POST['estante_id'] ?? [])[0] ?? 0;
$acao = (string) ($_POST['acao'] ?? '');

if ($obraId <= 0 || $estanteId <= 0 || !in_array($acao, ['mover', 'copiar'], true)) {
    redirecionar('/perfil.php');
}

try {
    $pdo = conectarBanco();

    $dono = $pdo->prepare('SELECT 1 FROM obras WHERE id = :id AND usuario_id = :u');
    $dono->execute(['id' => $obraId, 'u' => $usuarioId]);

    if (!$dono->fetch()) {
        redirecionar('/perfil.php');
    }

    $pdo->beginTransaction();

    $pdo->prepare(
        'INSERT INTO obras_estantes (obra_id, estante_id)
         VALUES (:o, :e) ON CONFLICT DO NOTHING'
    )->execute(['o' => $obraId, 'e' => $estanteId]);

    if ($acao === 'mover') {
        $pdo->prepare(
            'DELETE FROM obras_estantes WHERE obra_id = :o AND estante_id <> :e'
        )->execute(['o' => $obraId, 'e' => $estanteId]);
    }

    $pdo->commit();
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());
}

redirecionar('/perfil.php?estante=' . $estanteId);
