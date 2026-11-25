<?php

require_once '../config/session.php';
require_once '../config/database.php';

$pdo = conectarBanco();
$estanteSelecionada = $_GET['estante'] ?? 1;

$sql = "
SELECT
    o.id,
    o.titulo,
    o.autor,
    o.plataforma,
    o.link_url
FROM obras o
INNER JOIN obras_estantes oe
    ON oe.obra_id = o.id
WHERE
    o.usuario_id = :usuario_id
    AND oe.estante_id = :estante_id
ORDER BY o.id DESC
";

$resumoSql = "
SELECT
    e.nome,
    COUNT(DISTINCT o.id) AS total
FROM estantes e
LEFT JOIN obras_estantes oe
    ON oe.estante_id = e.id
LEFT JOIN obras o
    ON o.id = oe.obra_id
    AND o.usuario_id = :usuario_id
GROUP BY e.id, e.nome
ORDER BY e.id
";

$resumoStmt = $pdo->prepare($resumoSql);

$resumoStmt->execute([
    'usuario_id' => $_SESSION['usuario_id']
]);

$resumo = $resumoStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'usuario_id' => $_SESSION['usuario_id'],
    'estante_id' => $estanteSelecionada
]);

$obras = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Perfil</title>
</head>

<body>

<h1>Perfil</h1>

<p>
    Bem-vinda,
    <?= $_SESSION['nome_exibicao']; ?>
</p>

<p>
    @<?= $_SESSION['nome_usuario']; ?>
</p>

<p>
    <a href="/adicionar.php">
        Adicionar Obra
    </a>
</p>

<p>
    <a href="/actions/logout.php">
        Sair
    </a>
</p>

<hr>

<h2>Resumo de Leituras</h2>

<table border="1" cellpadding="5">

    <tr>
        <th>Estante</th>
        <th>Quantidade</th>
    </tr>

    <?php
    $totalGeral = 0;

    foreach ($resumo as $linha):

        $totalGeral += $linha['total'];
    ?>

    <tr>
        <td><?= htmlspecialchars($linha['nome']) ?></td>
        <td><?= $linha['total'] ?></td>
    </tr>

    <?php endforeach; ?>

    <tr>
        <th>Total Geral</th>
        <th><?= $totalGeral ?></th>
    </tr>

</table>

<hr>

<h2>Estantes</h2>

<a href="?estante=1">Lendo</a>
<a href="?estante=2">Lido</a>
<a href="?estante=3">Quero Ler</a>
<a href="?estante=4">Favoritos</a>

<?php if (empty($obras)) : ?>

    <p>Nenhuma obra cadastrada.</p>

<?php else : ?>

    <?php foreach ($obras as $obra) : ?>

        <div style="margin-bottom:20px">

            <h3>
                <?= htmlspecialchars($obra['titulo']) ?>
            </h3>

            <p>
                Autor:
                <?= htmlspecialchars($obra['autor']) ?>
            </p>

            <p>
                Plataforma:
                <?= htmlspecialchars($obra['plataforma']) ?>
            </p>

            <?php if (!empty($obra['link_url'])) : ?>

                <a
                    href="<?= htmlspecialchars($obra['link_url']) ?>"
                    target="_blank"
                >
                    Acessar Obra
                </a>

            <?php endif; ?>
            <p>
                <a href="/mover_obra.php?id=<?= $obra['id'] ?>">
                    Mover/Copiar
                </a>
            </p>
            <p>
                <a href="/editar_obra.php?id=<?= $obra['id'] ?>">
                    Editar
                </a>
                <a
                    href="/actions/excluir_obra.php?id=<?= $obra['id'] ?>"
                    onclick="return confirm('Deseja excluir esta obra?')">
                    Excluir
                </a>
            </p>

        </div>

        <hr>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>