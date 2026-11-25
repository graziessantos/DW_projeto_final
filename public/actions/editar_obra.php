<?php

session_start();

require_once '../../config/database.php';

$pdo = conectarBanco();

$id = (int) ($_POST['id'] ?? 0);

$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? '');
$plataforma = trim($_POST['plataforma'] ?? '');
$linkUrl = trim($_POST['link_url'] ?? '');
$capitulosLidos = (int) ($_POST['capitulos_lidos'] ?? 0);
$totalCapitulos = (int) ($_POST['total_capitulos'] ?? 0);

$stmt = $pdo->prepare("
    UPDATE obras
    SET
        titulo = :titulo,
        autor = :autor,
        plataforma = :plataforma,
        link_url = :link_url,
        capitulos_lidos = :capitulos_lidos,
        total_capitulos = :total_capitulos
    WHERE
        id = :id
        AND usuario_id = :usuario_id
");

$stmt->execute([
    'titulo' => $titulo,
    'autor' => $autor,
    'plataforma' => $plataforma,
    'link_url' => $linkUrl,
    'capitulos_lidos' => $capitulosLidos,
    'total_capitulos' => $totalCapitulos,
    'id' => $id,
    'usuario_id' => $_SESSION['usuario_id']
]);

header('Location: /perfil.php');

exit;