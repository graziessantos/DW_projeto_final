<?php

require_once '../config/session.php';
require_once '../config/database.php';

$pdo = conectarBanco();
$estanteSelecionada = (int) ($_GET['estante'] ?? 1);

if ($estanteSelecionada < 1 || $estanteSelecionada > 4) {
    $estanteSelecionada = 1;
}

$sql = "
SELECT
    o.id,
    o.titulo,
    o.autor,
    o.plataforma,
    o.link_url,
    (
        SELECT string_agg(x.estante_id::text, ',' ORDER BY x.estante_id)
        FROM obras_estantes x
        WHERE x.obra_id = o.id
    ) AS estantes_ids
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

/* ---- Apenas apresentação: números do resumo a partir das contagens acima ---- */
require_once __DIR__ . '/partials/layout.php';

$estantes = estantesFixas();
$estanteAtual = (int) $estanteSelecionada;
if (!isset($estantes[$estanteAtual])) {
    $estanteAtual = 1;
}

$contagem = array_fill_keys(array_keys($estantes), 0);
foreach ($resumo as $indice => $linha) {
    $contagem[$indice + 1] = (int) $linha['total'];
}

$lendo = $contagem[1];
$lidas = $contagem[2];
$favoritas = $contagem[4];
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM obras WHERE usuario_id = :usuario_id');
$totalStmt->execute(['usuario_id' => $_SESSION['usuario_id']]);
$totalObras = (int) $totalStmt->fetchColumn();   // obras distintas (uma obra em 2 estantes conta 1)
$percentualLidas = $totalObras > 0 ? (int) round(($lidas / $totalObras) * 100) : 0;

?>
<?php
topo('Perfil', 'perfil');
?>
<div class="coluna larga">
  <?php exibirMensagem(pegarMensagem('mensagem_obra')); ?>

  <section class="perfil-cab">
    <div class="avatar" aria-hidden="true">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
    </div>
    <div>
      <h1><?= e($_SESSION['nome_exibicao'] ?? '') ?></h1>
      <p class="mudo">@<?= e($_SESSION['nome_usuario'] ?? '') ?></p>
    </div>
    <img class="perfil-arte" src="/assets/img/deitada.png" width="200" height="116" alt="Ilustração de uma pessoa deitada de bruços, usando o celular.">
  </section>

  <section id="resumo" aria-label="Resumo da estante"
           data-total="<?= $totalObras ?>" data-lendo="<?= $lendo ?>" data-lido="<?= $lidas ?>" data-fav="<?= $favoritas ?>">
    <div class="stats">
      <p class="stat"><span class="n" id="st-total"><?= $totalObras ?></span> obras na estante</p>
      <p class="stat"><span class="n" id="st-lendo"><?= $lendo ?></span> lendo agora</p>
      <p class="stat"><span class="n" id="st-lido"><?= $lidas ?></span> lidas</p>
      <p class="stat"><span class="n" id="st-fav"><?= $favoritas ?></span> favoritas</p>
    </div>
    <div class="progresso">
      <span>Já lidas: <strong id="st-pct"><?= $percentualLidas ?>%</strong> da estante</span>
      <div class="barra" id="prog" role="progressbar" aria-label="Percentual de obras lidas" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percentualLidas ?>">
        <div id="prog-fill" style="width:<?= $percentualLidas ?>%"></div>
      </div>
    </div>
  </section>

  <section aria-label="Estantes">
    <nav class="abas abas-4" aria-label="Estantes de leitura">
      <?php foreach ($estantes as $id => $nome) : ?>
        <a href="/perfil.php?estante=<?= $id ?>"<?= $id === $estanteAtual ? ' class="ativa" aria-current="page"' : '' ?>><?= e($nome) ?> (<?= $contagem[$id] ?>)</a>
      <?php endforeach; ?>
    </nav>
    <div class="painel">
      <div class="grade">
        <?php if (empty($obras)) : ?>
          <p class="vazio mudo">Nenhuma obra nesta estante ainda. Que tal adicionar a primeira?</p>
        <?php endif; ?>
        <?php foreach ($obras as $obra) : ?>
          <article class="obra">
            <h2><?= e($obra['titulo']) ?></h2>
            <p><span class="mudo">Autor:</span> <?= e($obra['autor']) ?></p>
            <p><span class="mudo">Plataforma:</span> <?= e($obra['plataforma']) ?></p>
            <div class="obra-acoes">
              <?php if (!empty($obra['link_url'])) : ?>
                <a class="abrir" href="<?= e($obra['link_url']) ?>" target="_blank" rel="noopener noreferrer">Acessar Obra <span aria-hidden="true">→</span><span class="so-leitor"> (abre em nova aba)</span></a>
              <?php endif; ?>
              <button type="button" class="link-btn" data-editar="<?= (int) $obra['id'] ?>" data-estantes="<?= e($obra['estantes_ids'] ?? (string) $estanteAtual) ?>" data-titulo="<?= e($obra['titulo']) ?>">Editar<span class="so-leitor"> <?= e($obra['titulo']) ?></span></button>
              <button type="button" class="link-btn" data-remover="<?= (int) $obra['id'] ?>" data-titulo="<?= e($obra['titulo']) ?>">Remover<span class="so-leitor"> <?= e($obra['titulo']) ?></span></button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <div class="centro"><a class="btn" href="/adicionar.php">Adicionar obras</a></div>
</div>

<!--
  JANELA EDITAR (RF16/RF17)
  Envia por POST para /actions/editar_estantes.php (a ser criado pela pessoa do back-end):
  obra_id, estante_atual e estantes[] (ids 1 a 4 marcados).
-->
<dialog id="dlg-editar" class="dlg" aria-labelledby="dlg-editar-t">
  <form method="post" action="/actions/editar_estantes.php">
    <h2 id="dlg-editar-t">Editar</h2>
    <input type="hidden" name="obra_id" id="ed-id" value="">
    <input type="hidden" name="estante_atual" value="<?= $estanteAtual ?>">
    <fieldset class="grupo">
      <legend>Selecione as pastas que deseja fazer a mudança:</legend>
      <?php foreach ($estantes as $id => $nome) : ?>
        <label class="check"><input type="checkbox" name="estantes[]" value="<?= $id ?>" data-estante="<?= $id ?>"> <?= e($nome) ?></label>
      <?php endforeach; ?>
    </fieldset>
    <p id="ed-erro" class="erro" role="alert" hidden>Marque pelo menos uma pasta.</p>
    <div class="acoes"><button class="btn" type="submit">Salvar</button><button class="btn btn-borda" type="button" data-fechar>Cancelar</button></div>
    <p class="pequeno"><a id="ed-dados" href="/editar_obra.php">Editar título, autor, plataforma ou link</a></p>
  </form>
</dialog>

<!--
  JANELA REMOVER (RF18)
  Envia por POST para /actions/excluir_obra.php. O campo estante_id
  vai junto para o back-end poder remover só da estante aberta.
-->
<dialog id="dlg-remover" class="dlg" aria-labelledby="dlg-remover-t">
  <form method="post" action="/actions/excluir_obra.php">
    <h2 id="dlg-remover-t">Remover</h2>
    <input type="hidden" name="id" id="rm-id" value="">
    <input type="hidden" name="estante_id" value="<?= $estanteAtual ?>">
    <p>Deseja remover essa história da sua prateleira?</p>
    <p class="mudo pequeno" id="rm-titulo"></p>
    <div class="acoes"><button class="btn" type="submit">Sim</button><button class="btn btn-borda" type="button" data-fechar autofocus>Não</button></div>
  </form>
</dialog>
<?php rodape(); ?>
