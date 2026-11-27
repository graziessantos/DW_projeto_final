<?php

require_once __DIR__ . '/partials/layout.php';

topo('Organize suas leituras', 'inicio');
?>
<section class="hero">
  <div class="hero-txt">
    <h1>Organize suas leituras de forma livre!</h1>
    <p class="lead">Fanfics do AO3, Wattpad, Spirit, Tumblr e livros de todo o mundo, juntos em uma única estante! Chega de abas abertas.</p>
    <div class="acoes">
      <a class="btn" href="<?= usuarioLogado() ? '/perfil.php' : '/login.php' ?>">Entrar</a>
      <?php if (!usuarioLogado()) : ?>
        <a class="btn btn-borda" href="/cadastro.php">Criar conta</a>
      <?php endif; ?>
    </div>
    <?php if (!usuarioLogado()) : ?>
      <p class="pequeno">Não tem uma conta? <a href="/cadastro.php">Cadastre-se</a></p>
    <?php endif; ?>
  </div>
  <div class="hero-arte">
    <img src="/assets/img/leitura-planta.png" width="400" height="259" alt="Ilustração de uma leitora sentada no chão, lendo um livro ao lado de uma planta e uma xícara.">
  </div>
</section>
<?php rodape(); ?>
