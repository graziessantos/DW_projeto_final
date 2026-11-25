<?php

require_once '../config/session.php';
require_once '../config/database.php';

$obraId = (int) ($_GET['id'] ?? 0);

if ($obraId <= 0) {
    header('Location: /perfil.php');
    exit;
}

$pdo = conectarBanco();

$stmt = $pdo->prepare("
    SELECT *
    FROM obras
    WHERE
        id = :id
        AND usuario_id = :usuario_id
");

$stmt->execute([
    'id' => $obraId,
    'usuario_id' => $_SESSION['usuario_id']
]);

$obra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$obra) {
    header('Location: /perfil.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Obra</title>
</head>
<body>

<h1>Editar Obra</h1>

<form action="/actions/editar_obra.php" method="POST">

    <input
        type="hidden"
        name="id"
        value="<?= $obra['id'] ?>"
    >

    <label>Título</label>
    <input
        type="text"
        name="titulo"
        value="<?= htmlspecialchars($obra['titulo']) ?>"
        required
    >

    <br><br>

    <label>Autor</label>
    <input
        type="text"
        name="autor"
        value="<?= htmlspecialchars($obra['autor']) ?>"
        required
    >

    <br><br>

    <label>Plataforma</label>
    <input
        type="text"
        name="plataforma"
        value="<?= htmlspecialchars($obra['plataforma']) ?>"
        required
    >

    <br><br>
    <label>Capítulos Lidos</label>

    <input
        type="number"
        id="capitulos_lidos"
        name="capitulos_lidos"
        value="<?= $obra['capitulos_lidos'] ?>"
    >

    <br><br>

    <label>Total de Capítulos</label>

    <input
        type="number"
        id="total_capitulos"
        name="total_capitulos"
        value="<?= $obra['total_capitulos'] ?>"
    >
    <br><br>

    <label>Progresso</label>

    <div
        style="
            width:300px;
            border:1px solid #000;
            height:20px;
        "
    >

        <div
            id="barraProgresso"
            style="
                height:100%;
                width:0%;
                background:green;
            "
        ></div>

    </div>

    <p id="textoProgresso">
        0%
    </p>

    <br><br>

    <label>Link</label>
    <input
        type="url"
        name="link_url"
        value="<?= htmlspecialchars($obra['link_url']) ?>"
    >

    <br><br>

    <button type="submit">
        Salvar Alterações
    </button>

</form>
<script>

const capitulosLidos =
    document.getElementById(
        'capitulos_lidos'
    );

const totalCapitulos =
    document.getElementById(
        'total_capitulos'
    );

function atualizarProgresso()
{
    const lidos =
        parseInt(
            capitulosLidos.value
        ) || 0;

    const total =
        parseInt(
            totalCapitulos.value
        ) || 0;

    if (total <= 0) {
        return;
    }

    const percentual =
        Math.round(
            (lidos / total) * 100
        );

    document
        .getElementById(
            'barraProgresso'
        )
        .style.width =
        percentual + '%';

    document
        .getElementById(
            'textoProgresso'
        )
        .innerText =
        percentual + '%';

    if (percentual >= 100) {

        alert(
            'Leitura concluída! Considere mover a obra para Lido.'
        );
    }
}

atualizarProgresso();

capitulosLidos.addEventListener(
    'input',
    atualizarProgresso
);

totalCapitulos.addEventListener(
    'input',
    atualizarProgresso
);

</script>
</body>
</html>