<?php

require_once '../config/session.php';

$tituloBusca = trim($_GET['titulo_busca'] ?? '');

if (strlen($tituloBusca) < 3) {
    die('Digite pelo menos 3 caracteres.');
}

$url = 'https://openlibrary.org/search.json?title=' .
    urlencode($tituloBusca);

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$resposta = curl_exec($curl);

$erro = curl_error($curl);


if ($erro) {

    die(
        'Erro ao consultar Open Library: '
        . $erro
    );
}

if (!$resposta) {

    die(
        'Nenhuma resposta da Open Library.'
    );
}

$dados = json_decode($resposta, true);

if (
    empty($dados['docs']) ||
    empty($dados['docs'][0])
) {
    die('Nenhuma obra encontrada.');
}

$obra = $dados['docs'][0];

$titulo = $obra['title'] ?? '';
$autor = $obra['author_name'][0] ?? 'Autor desconhecido';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Obra Encontrada</title>
</head>
<body>

<h1>Obra encontrada!</h1>

<p>
    <strong>Título:</strong>
    <?= htmlspecialchars($titulo) ?>
</p>

<p>
    <strong>Autor:</strong>
    <?= htmlspecialchars($autor) ?>
</p>

<form
    action="actions/cadastrar_obra.php"
    method="POST"
>

    <input
        type="hidden"
        name="titulo"
        value="<?= htmlspecialchars($titulo) ?>"
    >

    <input
        type="hidden"
        name="autor"
        value="<?= htmlspecialchars($autor) ?>"
    >

    <input
        type="hidden"
        name="plataforma"
        value="Open Library"
    >

    <label>Estante</label>

    <select
        name="estante_id"
        required
    >
        <option value="1">Lendo</option>
        <option value="2">Lido</option>
        <option value="3">Quero Ler</option>
        <option value="4">Favoritos</option>
    </select>

    <br><br>

    <button type="submit">
        Salvar
    </button>

</form>

</body>
</html>