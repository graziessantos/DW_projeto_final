<?php

require_once '../config/session.php';
require_once __DIR__ . '/partials/layout.php';

$mensagem = pegarMensagem('mensagem_obra');
$dadosManual = $_SESSION['dados_obra'] ?? [];
unset($_SESSION['dados_obra']);

/* ---- Busca na Open Library (botão "Buscar livro") ---- */
$tituloBusca = trim((string) ($_GET['titulo_busca'] ?? ''));
$achada = null;
$avisoBusca = '';

if ($tituloBusca !== '') {
    if (mb_strlen($tituloBusca) < 3) {
        $avisoBusca = 'Digite pelo menos 3 caracteres.';
    } else {
        $url = 'https://openlibrary.org/search.json?limit=1&fields=key,title,author_name&title='
            . urlencode($tituloBusca);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resposta = curl_exec($curl);
        $falhou = $resposta === false || curl_error($curl) !== '';

        if ($falhou) {
            $avisoBusca = 'Não foi possível consultar a Open Library agora. Você pode cadastrar a obra manualmente ao lado.';
        } else {
            $dados = json_decode((string) $resposta, true);
            $doc = $dados['docs'][0] ?? null;
            if (!$doc || empty($doc['title'])) {
                $avisoBusca = 'Nenhuma obra encontrada. Tente outro título ou cadastre manualmente.';
            } else {
                $achada = [
                    'titulo' => (string) $doc['title'],
                    'autor' => (string) ($doc['author_name'][0] ?? 'Autor desconhecido'),
                    'chave' => (string) ($doc['key'] ?? ''),
                ];
            }
        }
    }
}

topo('Adicionar obras', 'adicionar');
?>
<div class="coluna larga">
  <div>
    <h1>Adicionar Obras</h1>
    <p class="mudo">Cadastre uma fanfic à mão ou busque um livro. Campos com * são obrigatórios.</p>
  </div>
  <?php exibirMensagem($mensagem); ?>

  <div class="duas">
    <form class="cartao-form borda" action="/actions/cadastrar_obra.php" method="post" id="form-manual">
      <h2>Inserção Manual</h2>
      <input type="hidden" name="origem" value="manual">
      <div class="campo">
        <label for="titulo">Nome da história *</label>
        <input id="titulo" name="titulo" type="text" required maxlength="255" value="<?= e($dadosManual['titulo'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="autor">Autor(a) *</label>
        <input id="autor" name="autor" type="text" required maxlength="255" value="<?= e($dadosManual['autor'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="plataforma">Plataforma *</label>
        <input id="plataforma" name="plataforma" type="text" required maxlength="100" placeholder="Ao3, Wattpad, Spirit..." value="<?= e($dadosManual['plataforma'] ?? '') ?>">
      </div>
      <div class="campo">
        <label for="link_url">Link (URL) *</label>
        <input id="link_url" name="link_url" type="url" required placeholder="https://" value="<?= e($dadosManual['link_url'] ?? '') ?>">
      </div>
      <?php caixasEstantes('m', array_map('intval', $dadosManual['estantes'] ?? [])); ?>
      <div class="centro"><button class="btn" type="submit">Salvar</button></div>
    </form>

    <div class="coluna-direita">
      <form class="cartao-form borda" action="/adicionar.php" method="get" id="form-busca">
        <h2>Busca de Livros</h2>
        <div class="campo">
          <label for="titulo_busca">Título da Obra</label>
          <input id="titulo_busca" name="titulo_busca" type="search" required minlength="3" value="<?= e($tituloBusca) ?>">
        </div>
        <button class="btn cheio" type="submit">Buscar livro</button>
        <p class="dica">Dados fornecidos pela Open Library.</p>
        <?php if ($avisoBusca !== '') : ?>
          <p class="erro bloco" role="alert"><?= e($avisoBusca) ?></p>
        <?php endif; ?>

        <?php if ($achada) : ?>
          <div class="selecionada" role="status">
            <p class="destaque"><strong>Obra encontrada!</strong></p>
            <p><strong><?= e($achada['titulo']) ?></strong><br><span class="mudo"><?= e($achada['autor']) ?></span></p>
          </div>
        <?php endif; ?>
      </form>

      <?php if ($achada) : ?>
        <form class="cartao-form borda" action="/actions/cadastrar_obra.php" method="post" id="form-achada">
          <input type="hidden" name="origem" value="open_library">
          <input type="hidden" name="titulo" value="<?= e($achada['titulo']) ?>">
          <input type="hidden" name="autor" value="<?= e($achada['autor']) ?>">
          <input type="hidden" name="plataforma" value="Open Library">
          <input type="hidden" name="open_library_key" value="<?= e($achada['chave']) ?>">
          <?php if ($achada['chave'] !== '') : ?>
            <input type="hidden" name="link_url" value="<?= e('https://openlibrary.org' . $achada['chave']) ?>">
          <?php endif; ?>
          <?php caixasEstantes('b'); ?>
          <div class="centro"><button class="btn" type="submit">Salvar</button></div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <img class="arte-direita" src="/assets/img/cadeira.png" width="260" height="212" alt="Ilustração de uma pessoa sentada em uma cadeira, lendo, ao lado de uma pilha de livros.">
</div>
<?php rodape(); ?>
