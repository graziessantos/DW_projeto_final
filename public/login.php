<?php

require_once __DIR__ . '/partials/layout.php';

redirecionarSeLogado();

$mensagem = pegarMensagem('mensagem_login');
$erroLogin = isset($_GET['erro']);

topo('Entrar', '', true);
?>
<form class="cartao-form" action="/actions/login.php" method="post">
  <h1>Entrar</h1>
  <?php exibirMensagem($mensagem); ?>
  <?php if ($erroLogin) : ?>
    <p class="erro bloco" role="alert">E-mail/usuário ou senha inválidos.</p>
  <?php endif; ?>
  <div class="campo">
    <label for="login">E-mail ou usuário</label>
    <input id="login" name="login" type="text" required autocomplete="username"<?= $erroLogin ? ' aria-invalid="true"' : '' ?>>
  </div>
  <div class="campo">
    <label for="senha">Senha</label>
    <input id="senha" name="senha" type="password" required autocomplete="current-password"<?= $erroLogin ? ' aria-invalid="true"' : '' ?>>
  </div>
  <label class="check"><input type="checkbox" name="lembrar" value="1"> Lembrar de mim</label>
  <button class="btn cheio" type="submit">Entrar</button>
  <p class="centro pequeno">Não tem uma conta? <a href="/cadastro.php">Cadastre-se</a></p>
</form>
<?php rodape(true); ?>
