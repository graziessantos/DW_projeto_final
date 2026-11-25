<form action="/actions/cadastrar_usuario.php" method="POST">
<label for="nome_exibicao">Nome de Exibição</label>
<input
    type="text"
    id="nome_exibicao"
    name="nome_exibicao"
    required
    maxlength="100"
>

<label for="nome_usuario">Nome de Usuário</label>
<input
    type="text"
    id="nome_usuario"
    name="nome_usuario"
    required
    minlength="3"
    maxlength="50"
>

<label for="email">E-mail</label>
<input
    type="email"
    id="email"
    name="email"
    required
    maxlength="254"
>

<label for="senha">Senha</label>
<input
    type="password"
    id="senha"
    name="senha"
    required
    minlength="8"
>

<label for="confirmacao_senha">Confirmação de Senha</label>
<input
    type="password"
    id="confirmacao_senha"
    name="confirmacao_senha"
    required
    minlength="8"
>

<label>
    <input
        type="checkbox"
        name="aceitou_termos"
        value="1"
        required
    >
    Li e aceito os Termos de Uso e a Política de Privacidade
</label>

<button type="submit">Criar</button>