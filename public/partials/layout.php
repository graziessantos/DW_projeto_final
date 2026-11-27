<?php

declare(strict_types=1);

/**
 * Layout compartilhado do Entre Abas: topo, menu, rodapé, logo e janelas de confirmação.
 * Usado por todas as páginas em /public.
 */

require_once dirname(__DIR__, 2) . '/src/lembrar_login.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// "Lembrar de mim": se a sessão expirou mas há cookie válido, o login volta aqui
restaurarLoginLembrado();

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function usuarioLogado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

/** Barra a página se o usuário não estiver logado. */
function exigirLogin(): void
{
    if (!usuarioLogado()) {
        header('Location: /login.php');
        exit;
    }
}

/** Páginas de login e cadastro redirecionam quem já está logado (RN02). */
function redirecionarSeLogado(): void
{
    if (usuarioLogado()) {
        header('Location: /perfil.php');
        exit;
    }
}

/** Lê e apaga uma mensagem salva na sessão pelas ações (ex.: mensagem_obra). */
function pegarMensagem(string $chave): ?array
{
    $mensagem = $_SESSION[$chave] ?? null;
    unset($_SESSION[$chave]);

    return is_array($mensagem) ? $mensagem : null;
}

/** As quatro estantes fixas do banco (tabela estantes). */
function estantesFixas(): array
{
    return [
        1 => 'Lendo',
        2 => 'Lido',
        3 => 'Quero Ler',
        4 => 'Favoritos',
    ];
}

function iconeLogo(string $cor, int $altura): string
{
    $largura = (int) round($altura * 361 / 380);
    $p = 'M33 44H60V317H33Q3 317 3 287V74Q3 44 33 44Z';
    $q = 'M108 5H140Q158 5 158 18Q158 32 140 32H138V340H140Q158 340 158 354Q158 372 140 372H108Q78 372 78 342V35Q78 5 108 5Z';

    return "<svg width=\"$largura\" height=\"$altura\" viewBox=\"0 0 361 380\" fill=\"$cor\" aria-hidden=\"true\" focusable=\"false\">"
        . "<path d=\"$p\"/><path d=\"$q\"/>"
        . "<g transform=\"translate(361 0) scale(-1 1)\"><path d=\"$p\"/><path d=\"$q\"/></g></svg>";
}

function logo(string $cor, int $tamanho, string $href = '/index.php'): string
{
    return '<a class="logo" href="' . e($href) . '" aria-label="Entre Abas, página inicial" style="color:' . $cor . ';font-size:' . $tamanho . 'px">'
        . iconeLogo($cor, (int) round($tamanho * 0.95))
        . '<span>Entre Abas</span></a>';
}

/**
 * Abre a página. $ativo marca o item do menu (inicio, sobre, adicionar, perfil).
 * $simples = true usa o topo reduzido das telas de login e cadastro.
 */
function topo(string $titulo, string $ativo = '', bool $simples = false): void
{
    $logado = usuarioLogado();
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> | Entre Abas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Kenia&display=swap">
<link rel="stylesheet" href="/assets/css/style.css">
<script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>
<?php if (!$simples) : ?>
<header class="topo on-dark">
  <div class="topo-in">
    <?= logo('#FFFFFF', 30, $logado ? '/perfil.php' : '/index.php') ?>
    <button class="menu-btn" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu-principal">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <nav id="menu-principal" class="menu" aria-label="Principal">
      <?php if ($logado) : ?>
        <a href="/sobre.php"<?= $ativo === 'sobre' ? ' aria-current="page"' : '' ?>>Sobre</a>
        <a href="/adicionar.php"<?= $ativo === 'adicionar' ? ' aria-current="page"' : '' ?>>Adicionar</a>
        <a href="/perfil.php"<?= $ativo === 'perfil' ? ' aria-current="page"' : '' ?>>Perfil</a>
        <button type="button" class="link-sair" data-abrir="dlg-sair">Sair</button>
      <?php else : ?>
        <a href="/index.php"<?= $ativo === 'inicio' ? ' aria-current="page"' : '' ?>>Início</a>
        <a href="/sobre.php"<?= $ativo === 'sobre' ? ' aria-current="page"' : '' ?>>Sobre</a>
        <a class="pilula" href="/login.php">Entrar</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php else : ?>
<div class="topo-simples"><?= logo('#970747', 44) ?></div>
<?php endif; ?>
<main id="conteudo" tabindex="-1">
<?php
}

function rodape(bool $simples = false): void
{
    $logado = usuarioLogado();
    ?>
</main>
<?php if ($logado) : ?>
<dialog id="dlg-sair" aria-labelledby="dlg-sair-t" class="dlg">
  <form method="post" action="/actions/logout.php">
    <h2 id="dlg-sair-t">Sair</h2>
    <p>Deseja sair da sua conta?</p>
    <div class="acoes"><button class="btn" type="submit">Sim</button><button class="btn btn-borda" type="button" data-fechar autofocus>Não</button></div>
  </form>
</dialog>
<?php endif; ?>
<?php if ($simples) : ?>
<footer class="rodape on-dark rodape-mini"><span>© 2026 Entre Abas</span></footer>
<?php else : ?>
<footer class="rodape on-dark">
  <div class="rodape-in">
    <div class="rodape-marca">
      <?= logo('#FFFFFF', 28, $logado ? '/perfil.php' : '/index.php') ?>
      <p>Plataforma feita para leitores terem a liberdade de organizar suas leituras.</p>
    </div>
    <nav aria-label="Rodapé: navegação"><strong>Navegação</strong>
      <?php if ($logado) : ?>
        <a href="/sobre.php">Sobre</a><a href="/adicionar.php">Adicionar</a><a href="/perfil.php">Perfil</a>
      <?php else : ?>
        <a href="/index.php">Início</a><a href="/sobre.php">Sobre</a><a href="/login.php">Entrar</a>
      <?php endif; ?>
    </nav>
    <div class="rodape-info">
      <strong>Informações Acadêmicas</strong>
      <p><b>Curso:</b> Ciência da Computação</p>
      <p><b>Disciplina:</b> Desenvolvimento Web</p>
      <p><b>Turma:</b> A</p>
      <p><b>Integrantes:</b> Grazielle, Beatriz, Lara, Ana Júlia</p>
    </div>
  </div>
  <p class="direitos">© 2026 Entre Abas. Todos os direitos reservados.</p>
</footer>
<?php endif; ?>
</body>
</html>
<?php
}

/** Caixa de mensagem (sucesso ou erro) vinda da sessão. */
function exibirMensagem(?array $mensagem): void
{
    if (!$mensagem) {
        return;
    }

    $erro = ($mensagem['tipo'] ?? '') === 'erro';
    ?>
<p class="<?= $erro ? 'erro bloco' : 'aviso' ?>" role="<?= $erro ? 'alert' : 'status' ?>"><?= e($mensagem['texto'] ?? '') ?></p>
<?php
}

/** Grupo de caixas "Guardar em" com as quatro estantes. */
function caixasEstantes(string $prefixo, array $marcadas = []): void
{
    ?>
<fieldset class="grupo">
  <legend>Guardar em *</legend>
  <div class="checks">
    <?php foreach (estantesFixas() as $id => $nome) : ?>
      <label class="check">
        <input type="checkbox" name="estantes[]" value="<?= $id ?>" id="<?= e($prefixo) ?>-<?= $id ?>"<?= in_array($id, $marcadas, true) ? ' checked' : '' ?>>
        <?= e($nome) ?>
      </label>
    <?php endforeach; ?>
  </div>
</fieldset>
<?php
}
