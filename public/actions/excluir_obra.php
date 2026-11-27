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

// estante_id chega como valor único (ex.: "2"), então é lido direto como inteiro.
$estanteId = (int) ($_POST['estante_id'] ?? 0);
if ($estanteId < 1 || $estanteId > 4) {
    $estanteId = 0;
}

$voltar = '/perfil.php' . ($estanteId > 0 ? '?estante=' . $estanteId : '');

if ($obraId <= 0) {
    redirecionar($voltar);
}

try {
    $pdo = conectarBanco();
    $pdo->beginTransaction();

    // Confere o dono e trava a linha até o fim da transação.
    $dono = $pdo->prepare(
        'SELECT 1 FROM obras WHERE id = :id AND usuario_id = :u FOR UPDATE'
    );
    $dono->execute(['id' => $obraId, 'u' => $usuarioId]);

    if (!$dono->fetchColumn()) {
        $pdo->rollBack();
        redirecionar('/perfil.php');
    }

    $total = $pdo->prepare('SELECT count(*) FROM obras_estantes WHERE obra_id = :o');
    $total->execute(['o' => $obraId]);
    $qtd = (int) $total->fetchColumn();

    $naEstante = false;
    if ($estanteId > 0) {
        $consulta = $pdo->prepare(
            'SELECT 1 FROM obras_estantes WHERE obra_id = :o AND estante_id = :e'
        );
        $consulta->execute(['o' => $obraId, 'e' => $estanteId]);
        $naEstante = (bool) $consulta->fetchColumn();
    }

    if ($estanteId > 0 && $qtd > 1 && $naEstante) {
        // Está em outras estantes: tira só desta
        $del = $pdo->prepare(
            'DELETE FROM obras_estantes WHERE obra_id = :o AND estante_id = :e'
        );
        $del->execute(['o' => $obraId, 'e' => $estanteId]);
    } else {
        // Última estante (ou estante não informada): apaga a obra
        $del = $pdo->prepare('DELETE FROM obras WHERE id = :id AND usuario_id = :u');
        $del->execute(['id' => $obraId, 'u' => $usuarioId]);
    }

    if ($del->rowCount() !== 1) {
        throw new RuntimeException(
            'DELETE afetou ' . $del->rowCount() . ' linhas (esperado 1) para a obra ' . $obraId
        );
    }

    // Os triggers deferidos do schema são validados aqui; se falharem, cai no catch.
    $pdo->commit();

    guardarMensagem('mensagem_obra', 'sucesso', 'Obra removida.');
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[excluir_obra] ' . $erro->getMessage());
    guardarMensagem('mensagem_obra', 'erro', 'Erro ao remover a obra.');
}

redirecionar($voltar);