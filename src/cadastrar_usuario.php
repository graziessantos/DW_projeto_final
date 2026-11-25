<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Salva uma mensagem na sessão e retorna à página de cadastro.
 */
function redirecionarCadastro(
    string $tipo,
    string $mensagem
): never {
    $_SESSION['mensagem_cadastro'] = [
        'tipo' => $tipo,
        'texto' => $mensagem,
    ];

    header('Location: /cadastro.php');
    exit;
}

/**
 * Preserva apenas os campos não sensíveis após um erro.
 */
function preservarDadosCadastro(
    string $nomeExibicao,
    string $nomeUsuario,
    string $email
): void {
    $_SESSION['dados_cadastro'] = [
        'nome_exibicao' => $nomeExibicao,
        'nome_usuario' => $nomeUsuario,
        'email' => $email,
    ];
}

/**
 * Aceita somente requisição POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');

    exit('Método não permitido.');
}

/**
 * Recebe e normaliza os dados.
 */
$nomeExibicao = trim(
    (string) ($_POST['nome_exibicao'] ?? '')
);

$nomeUsuario = trim(
    (string) ($_POST['nome_usuario'] ?? '')
);

$email = strtolower(
    trim((string) ($_POST['email'] ?? ''))
);

$senha = (string) ($_POST['senha'] ?? '');

$confirmacaoSenha = (string) (
    $_POST['confirmacao_senha'] ?? ''
);

$aceitouTermos =
    ($_POST['aceitou_termos'] ?? null) === '1';

/**
 * Mantém os dados preenchidos se ocorrer algum erro.
 * As senhas nunca são armazenadas na sessão.
 */
preservarDadosCadastro(
    $nomeExibicao,
    $nomeUsuario,
    $email
);

/**
 * Validação dos campos obrigatórios.
 */
if (
    $nomeExibicao === ''
    || $nomeUsuario === ''
    || $email === ''
    || $senha === ''
    || $confirmacaoSenha === ''
) {
    redirecionarCadastro(
        'erro',
        'Preencha todos os campos obrigatórios.'
    );
}

/**
 * Validação do nome de exibição.
 */
$tamanhoNomeExibicao = strlen(
    $nomeExibicao,
);

if (
    $tamanhoNomeExibicao < 2
    || $tamanhoNomeExibicao > 100
) {
    redirecionarCadastro(
        'erro',
        'O nome de exibição deve ter entre 2 e 100 caracteres.'
    );
}

/**
 * Validação do nome de usuário.
 */
$tamanhoNomeUsuario = strlen(
    $nomeUsuario,
);

if (
    $tamanhoNomeUsuario < 3
    || $tamanhoNomeUsuario > 50
) {
    redirecionarCadastro(
        'erro',
        'O nome de usuário deve ter entre 3 e 50 caracteres.'
    );
}

if (
    preg_match(
        '/^[A-Za-z0-9._-]+$/',
        $nomeUsuario
    ) !== 1
) {
    redirecionarCadastro(
        'erro',
        'O nome de usuário pode conter apenas letras, números, ponto, hífen e sublinhado.'
    );
}

/**
 * Validação do e-mail.
 */
if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {
    redirecionarCadastro(
        'erro',
        'Informe um endereço de e-mail válido.'
    );
}

if (strlen($email) > 254) {
    redirecionarCadastro(
        'erro',
        'O endereço de e-mail informado é muito longo.'
    );
}

/**
 * Validação da senha.
 */
if (strlen($senha) < 8) {
    redirecionarCadastro(
        'erro',
        'A senha deve ter no mínimo 8 caracteres.'
    );
}

if ($senha !== $confirmacaoSenha) {
    redirecionarCadastro(
        'erro',
        'A senha e a confirmação de senha não coincidem.'
    );
}

/**
 * Validação do aceite dos termos.
 */
if (!$aceitouTermos) {
    redirecionarCadastro(
        'erro',
        'É necessário aceitar os Termos de Uso e a Política de Privacidade.'
    );
}

/**
 * A senha é transformada em hash antes de chegar ao banco.
 */
$senhaHash = password_hash(
    $senha,
    PASSWORD_DEFAULT
);

if ($senhaHash === false) {
    redirecionarCadastro(
        'erro',
        'Não foi possível processar a senha.'
    );
}

try {
    $pdo = conectarBanco();

    /**
     * Verifica se o e-mail ou nome de usuário já existe.
     *
     * A consulta é útil para apresentar mensagens específicas.
     * Os índices UNIQUE do banco continuam sendo a proteção final.
     */
    $consulta = $pdo->prepare(
        '
        SELECT
            nome_usuario,
            email
        FROM usuarios
        WHERE
            lower(btrim(email)) =
                lower(btrim(:email))
            OR
            lower(btrim(nome_usuario)) =
                lower(btrim(:nome_usuario))
        LIMIT 1
        '
    );

    $consulta->execute([
        'email' => $email,
        'nome_usuario' => $nomeUsuario,
    ]);

    $usuarioExistente = $consulta->fetch();

    if ($usuarioExistente !== false) {
        if (
            strtolower(
                trim($usuarioExistente['email']),
            ) === $email
        ) {
            redirecionarCadastro(
                'erro',
                'Este e-mail já está cadastrado.'
            );
        }

        redirecionarCadastro(
            'erro',
            'Este nome de usuário já está em uso.'
        );
    }

    /**
     * Insere o usuário.
     *
     * A senha original nunca é enviada ao banco.
     */
    $inserir = $pdo->prepare(
        '
        INSERT INTO usuarios (
            nome_exibicao,
            nome_usuario,
            email,
            senha_hash,
            aceitou_termos,
            termos_aceitos_em
        )
        VALUES (
            :nome_exibicao,
            :nome_usuario,
            :email,
            :senha_hash,
            TRUE,
            CURRENT_TIMESTAMP
        )
        RETURNING id
        '
    );

    $inserir->execute([
        'nome_exibicao' => $nomeExibicao,
        'nome_usuario' => $nomeUsuario,
        'email' => $email,
        'senha_hash' => $senhaHash,
    ]);

    $usuarioId = $inserir->fetchColumn();

    if ($usuarioId === false) {
        throw new RuntimeException(
            'O banco não retornou o usuário criado.'
        );
    }

    /**
     * Limpa os dados antigos e envia uma mensagem ao login.
     */
    unset($_SESSION['dados_cadastro']);

    $_SESSION['mensagem_login'] = [
        'tipo' => 'sucesso',
        'texto' =>
            'Cadastro realizado com sucesso. Faça seu login.',
    ];

    header('Location: /login.php');
    exit;
} catch (PDOException $erro) {
    error_log(
        'Erro de banco no cadastro: '
        . $erro->getMessage()
    );

    /**
     * 23505 é o código do PostgreSQL para violação UNIQUE.
     * Pode acontecer se dois cadastros iguais forem enviados
     * praticamente ao mesmo tempo.
     */
    if ($erro->getCode() === '23505') {
        redirecionarCadastro(
            'erro',
            'O e-mail ou nome de usuário informado já está cadastrado.'
        );
    }

    redirecionarCadastro(
        'erro',
        'Não foi possível concluir o cadastro. Tente novamente.'
    );
} catch (Throwable $erro) {
    error_log(
        'Erro inesperado no cadastro: '
        . $erro->getMessage()
    );

    redirecionarCadastro(
        'erro',
        'Não foi possível concluir o cadastro. Tente novamente.'
    );
}