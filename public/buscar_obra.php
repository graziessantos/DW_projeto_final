<?php

require_once '../config/session.php';

/**
 * A busca na Open Library agora acontece dentro da página Adicionar.
 * Este arquivo só redireciona links antigos para lá.
 */
$titulo = trim((string) ($_GET['titulo_busca'] ?? ''));

header('Location: /adicionar.php' . ($titulo !== '' ? '?titulo_busca=' . urlencode($titulo) : ''));
exit;
