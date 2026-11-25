<?php

require_once '../config/session.php';

$obraId = (int)($_GET['id'] ?? 0);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Mover Obra</title>
</head>
<body>

<h1>Mover ou Copiar Obra</h1>

<form action="/actions/mover_obra.php" method="POST">

    <input
        type="hidden"
        name="obra_id"
        value="<?= $obraId ?>"
    >

    <label>Ação</label>

    <select name="acao">
        <option value="mover">Mover</option>
        <option value="copiar">Copiar</option>
    </select>

    <br><br>

    <label>Nova Estante</label>

    <select name="estante_id">

        <option value="1">Lendo</option>
        <option value="2">Lido</option>
        <option value="3">Quero Ler</option>
        <option value="4">Favoritos</option>

    </select>

    <br><br>

    <button type="submit">
        Confirmar
    </button>

</form>

</body>
</html>