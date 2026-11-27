<?php

declare(strict_types=1);

/*
 * Remove a obra da estante aberta (RF18). Se era a última estante dela,
 * a obra é apagada. Só aceita POST.
 */

require_once dirname(__DIR__, 2) . '/src/sessao.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

exigirPost();
$usuarioId = exigirLoginNaAcao();

$obraId = (int) ($_POST['id'] ?? 0);
$estanteId = lerIdsEstantes($_POST['estante_id'] ?? [])[0] ?? 0;
$voltar = '/perfil.php' . ($estanteId > 0 ? '?estante=' . $estanteId : '');

if ($obraId <= 0) {
    redirecionar($voltar);
}

try {
    $pdo = conectarBanco();

    $dono = $pdo->prepare('SELECT 1 FROM obras WHERE id = :id AND usuario_id = :u');
    $dono->execute(['id' => $obraId, 'u' => $usuarioId]);

    if (!$dono->fetch()) {
        redirecionar('/perfil.php');
    }

    $pdo->beginTransaction();

    $total = $pdo->prepare('SELECT count(*) FROM obras_estantes WHERE obra_id = :o');
    $total->execute(['o' => $obraId]);
    $qtd = (int) $total->fetchColumn();

    $naEstante = $pdo->prepare(
        'SELECT 1 FROM obras_estantes WHERE obra_id = :o AND estante_id = :e'
    );
    $naEstante->execute(['o' => $obraId, 'e' => $estanteId]);

    if ($estanteId > 0 && $qtd > 1 && $naEstante->fetch()) {
        // Está em outras estantes: tira só desta
        $pdo->prepare('DELETE FROM obras_estantes WHERE obra_id = :o AND estante_id = :e')
            ->execute(['o' => $obraId, 'e' => $estanteId]);
    } else {
        // Última estante (ou estante não informada): apaga a obra
        $pdo->prepare('DELETE FROM obras WHERE id = :id AND usuario_id = :u')
            ->execute(['id' => $obraId, 'u' => $usuarioId]);
    }

    $pdo->commit();

    guardarMensagem('mensagem_obra', 'sucesso', 'Obra removida.');
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());
    guardarMensagem('mensagem_obra', 'erro', 'Erro ao remover a obra.');
}

redirecionar($voltar);
