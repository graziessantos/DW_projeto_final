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

/* ---- Apenas apresentação ---- */
require_once __DIR__ . '/partials/layout.php';

$mensagem = pegarMensagem('mensagem_obra');
$daOpenLibrary = ($obra['origem'] ?? 'manual') === 'open_library';

?>
<?php
topo('Editar obra', 'perfil');
?>
<div class="coluna">
  <h1>Editar obra</h1>
  <?php exibirMensagem($mensagem); ?>
  <form class="cartao-form borda" action="/actions/editar_obra.php" method="post">
    <input type="hidden" name="id" value="<?= (int) $obra['id'] ?>">
    <div class="campo">
      <label for="titulo">Nome da história *</label>
      <input id="titulo" name="titulo" type="text" required maxlength="255" value="<?= e($obra['titulo']) ?>">
    </div>
    <div class="campo">
      <label for="autor">Autor(a) *</label>
      <input id="autor" name="autor" type="text" required maxlength="255" value="<?= e($obra['autor']) ?>">
    </div>
    <div class="campo">
      <label for="plataforma">Plataforma *</label>
      <input id="plataforma" name="plataforma" type="text" required maxlength="100" value="<?= e($obra['plataforma']) ?>"<?= $daOpenLibrary ? ' readonly aria-describedby="plat-d"' : '' ?>>
      <?php if ($daOpenLibrary) : ?>
        <span class="dica" id="plat-d">Obras vindas da Open Library mantêm essa plataforma.</span>
      <?php endif; ?>
    </div>
    <div class="campo">
      <label for="link_url">Link (URL)</label>
      <input id="link_url" name="link_url" type="url" placeholder="https://" value="<?= e($obra['link_url'] ?? '') ?>">
    </div>
    <div class="acoes">
      <button class="btn" type="submit">Salvar alterações</button>
      <a class="btn btn-borda" href="/perfil.php">Cancelar</a>
    </div>
  </form>
</div>
<?php rodape(); ?>
