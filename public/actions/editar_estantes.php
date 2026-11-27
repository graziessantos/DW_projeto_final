<?php

declare(strict_types=1);

/*
 * Muda em quais estantes a obra está (RF16/RF17).
 * Recebe: obra_id, estante_atual, estantes[] (ids 1 a 4 marcados).
 */

require_once dirname(__DIR__, 2) . '/src/sessao.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

exigirPost();
$usuarioId = exigirLoginNaAcao();

$obraId = (int) ($_POST['obra_id'] ?? 0);
$estanteAtual = lerIdsEstantes($_POST['estante_atual'] ?? [])[0] ?? 1;
$novas = lerIdsEstantes($_POST['estantes'] ?? []);
$voltar = '/perfil.php?estante=' . $estanteAtual;

if ($obraId <= 0) {
    redirecionar($voltar);
}

if ($novas === []) {
    guardarMensagem('mensagem_obra', 'erro', 'Marque pelo menos uma pasta.');
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

    $existentes = $pdo->prepare('SELECT estante_id FROM obras_estantes WHERE obra_id = :o');
    $existentes->execute(['o' => $obraId]);
    $atuais = array_map('intval', $existentes->fetchAll(PDO::FETCH_COLUMN));

    $inserir = $pdo->prepare('INSERT INTO obras_estantes (obra_id, estante_id) VALUES (:o, :e)');
    foreach (array_diff($novas, $atuais) as $estanteId) {
        $inserir->execute(['o' => $obraId, 'e' => $estanteId]);
    }

    $remover = $pdo->prepare('DELETE FROM obras_estantes WHERE obra_id = :o AND estante_id = :e');
    foreach (array_diff($atuais, $novas) as $estanteId) {
        $remover->execute(['o' => $obraId, 'e' => $estanteId]);
    }

    $pdo->commit();

    guardarMensagem('mensagem_obra', 'sucesso', 'Estantes atualizadas.');
    // Se a obra saiu da estante aberta, abre a primeira em que ela ficou
    redirecionar('/perfil.php?estante=' . (in_array($estanteAtual, $novas, true) ? $estanteAtual : $novas[0]));
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($erro->getMessage());
    guardarMensagem('mensagem_obra', 'erro', 'Erro ao atualizar as estantes.');
    redirecionar($voltar);
}
