# Entre Abas

Organizador de leituras (fanfics e livros) — Desenvolvimento Web, CEUB.
PHP 8 (PDO) + PostgreSQL + HTML/CSS/JS.

## Como rodar

1. Instale PHP 8 com as extensões `pdo_pgsql` e `curl`.
2. Copie `.env.example` para `.env` e coloque a `DATABASE_URL` do banco do grupo.
3. Na pasta do projeto:

```
php -S localhost:8000 -t public
```

Acesse http://localhost:8000. A pasta `public/` precisa ser a raiz do servidor
(os links começam com `/`).

## Testar sem o banco do grupo

1. Instale o PostgreSQL local e crie um banco: `createdb entre_abas`
2. Carregue o esquema: `psql -d entre_abas -f database/schema.sql`
3. No `.env`:

```
DATABASE_URL=postgresql://postgres:SUA_SENHA@localhost:5432/entre_abas
DATABASE_SSLMODE=disable
```

(`DATABASE_SSLMODE` não é necessário no banco do grupo, que usa SSL.)

## Estrutura

- `public/` páginas e `public/actions/` ações (POST) que gravam no banco
- `src/` regras: cadastro, login, "lembrar de mim", funções de sessão
- `config/` conexão com o banco, `.env` e proteção de páginas
- `database/schema.sql` esquema PostgreSQL

## Rotas

| Rota | O que faz |
|---|---|
| POST /actions/cadastrar_usuario.php | cria conta |
| POST /actions/login.php | login (+ "lembrar de mim", 30 dias) |
| POST /actions/logout.php | sai e revoga o token lembrado |
| POST /actions/cadastrar_obra.php | cadastra obra (manual/Open Library) em 1+ estantes |
| POST /actions/editar_obra.php | edita título, autor, plataforma, link |
| POST /actions/editar_estantes.php | muda as estantes da obra |
| POST /actions/excluir_obra.php | remove da estante aberta (apaga se for a última) |
| POST /actions/mover_obra.php | mover/copiar (página simples /mover_obra.php) |
