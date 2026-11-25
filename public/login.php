<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>

<h1>Login</h1>

<?php if (isset($_GET['erro'])) : ?>
    <p style="color:red;">
        E-mail/usuário ou senha inválidos.
    </p>
<?php endif; ?>

<form action="actions/login.php" method="POST">

    <label>
        E-mail ou Usuário
    </label>

    <input
        type="text"
        name="login"
        required
    >

    <br><br>

    <label>
        Senha
    </label>

    <input
        type="password"
        name="senha"
        required
    >

    <br><br>

    <button type="submit">
        Entrar
    </button>

</form>

</body>
</html>