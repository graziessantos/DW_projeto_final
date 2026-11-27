<?php

require_once __DIR__ . '/partials/layout.php';

redirecionarSeLogado();

$mensagem = pegarMensagem('mensagem_cadastro');
$dados = $_SESSION['dados_cadastro'] ?? [];
unset($_SESSION['dados_cadastro']);

topo('Criar conta', '', true);
?>
<form class="cartao-form" action="/actions/cadastrar_usuario.php" method="post">
  <h1>Criar conta</h1>
  <p class="dica">Todos os campos são obrigatórios.</p>
  <?php exibirMensagem($mensagem); ?>
  <div class="campo">
    <label for="nome_exibicao">Nome de exibição</label>
    <input id="nome_exibicao" name="nome_exibicao" type="text" required minlength="2" maxlength="100" autocomplete="name" value="<?= e($dados['nome_exibicao'] ?? '') ?>">
  </div>
  <div class="campo">
    <label for="nome_usuario">Nome de usuário</label>
    <span class="dica" id="nome_usuario-d">Letras, números e . _ - Aparece como @usuario.</span>
    <input id="nome_usuario" name="nome_usuario" type="text" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._\-]+" autocomplete="username" aria-describedby="nome_usuario-d" value="<?= e($dados['nome_usuario'] ?? '') ?>">
  </div>
  <div class="campo">
    <label for="email">E-mail</label>
    <input id="email" name="email" type="email" required maxlength="254" autocomplete="email" value="<?= e($dados['email'] ?? '') ?>">
  </div>
  <div class="campo">
    <label for="senha">Senha</label>
    <span class="dica" id="senha-d">Mínimo de 8 caracteres.</span>
    <input id="senha" name="senha" type="password" required minlength="8" autocomplete="new-password" aria-describedby="senha-d">
  </div>
  <div class="campo">
    <label for="confirmacao_senha">Confirmação de senha</label>
    <input id="confirmacao_senha" name="confirmacao_senha" type="password" required minlength="8" autocomplete="new-password">
  </div>
  <label class="check">
    <input type="checkbox" name="aceitou_termos" value="1" required>
    <span>Li e aceito os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a>.</span>
  </label>
  <button class="btn cheio" type="submit">Criar conta</button>
  <p class="centro pequeno">Já tem conta? <a href="/login.php">Faça login</a></p>
</form>
<?php rodape(true); ?>
