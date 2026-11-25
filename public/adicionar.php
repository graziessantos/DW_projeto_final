<?php

require_once '../config/session.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Obra</title>
</head>
<body>

<hr>

<h2>Buscar Obra na Open Library</h2>

<form action="buscar_obra.php" method="GET">

    <label>Título da Obra</label>

    <input
        type="text"
        name="titulo_busca"
        required
    >

    <button type="submit">
        Buscar na Nuvem
    </button>

</form>

<hr>

<h1>Adicionar Obra</h1>

<form action="actions/cadastrar_obra.php" method="POST">

    <label>Título</label>
    <input type="text" name="titulo" required>

    <br><br>

    <label>Autor</label>
    <input type="text" name="autor" required>

    <br><br>

    <label>Plataforma</label>
    <input type="text" name="plataforma" required>

    <br><br>

    <label>Link</label>
    <input type="url" name="link_url">

    <br><br>

    <label>Estante</label>

    <select name="estante_id" required>
        <option value="">Selecione</option>
        <option value="1">Lendo</option>
        <option value="2">Lido</option>
        <option value="3">Quero Ler</option>
        <option value="4">Favoritos</option>
    </select>

    <br><br>

    <label>Capítulos Lidos</label>

        <input
            type="number"
            name="capitulos_lidos"
            id="capitulos_lidos"
            min="0"
        >

        <br><br>

        <label>Total de Capítulos</label>

        <input
            type="number"
            name="total_capitulos"
            id="total_capitulos"
            min="1"
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

    <button type="submit">
        Salvar
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