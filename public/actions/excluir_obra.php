<?php

session_start();

require_once '../../config/database.php';

$obraId = (int) ($_GET['id'] ?? 0);

if ($obraId <= 0) {
    header('Location: /perfil.php');
    exit;
}

$pdo = conectarBanco();

$stmt = $pdo->prepare(
    "
    DELETE FROM obras
    WHERE
        id = :id
        AND usuario_id = :usuario_id
    "
);

$stmt->execute([
    'id' => $obraId,
    'usuario_id' => $_SESSION['usuario_id']
]);

header('Location: /perfil.php');

exit;
