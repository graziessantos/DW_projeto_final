<?php

session_start();

require_once '../../config/database.php';

$pdo = conectarBanco();

$obraId = (int)($_POST['obra_id'] ?? 0);
$estanteId = (int)($_POST['estante_id'] ?? 0);
$acao = $_POST['acao'] ?? '';

if ($obraId <= 0 || $estanteId <= 0) {

    header('Location: /perfil.php');
    exit;
}

$verificar = $pdo->prepare("
    SELECT 1
    FROM obras_estantes
    WHERE
        obra_id = :obra_id
        AND estante_id = :estante_id
");

$verificar->execute([
    'obra_id' => $obraId,
    'estante_id' => $estanteId
]);

if (!$verificar->fetch()) {

    $inserir = $pdo->prepare("
        INSERT INTO obras_estantes (
            obra_id,
            estante_id
        )
        VALUES (
            :obra_id,
            :estante_id
        )
    ");

    $inserir->execute([
        'obra_id' => $obraId,
        'estante_id' => $estanteId
    ]);
}
if ($acao === 'mover') {

    $excluir = $pdo->prepare("
        DELETE FROM obras_estantes
        WHERE
            obra_id = :obra_id
            AND estante_id <> :estante_id
    ");

    $excluir->execute([
        'obra_id' => $obraId,
        'estante_id' => $estanteId
    ]);
}
header('Location: /perfil.php');
exit;