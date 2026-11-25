BEGIN;

-- 1. FUNCAO GENERICA PARA ATUALIZAR atualizado_em

CREATE OR REPLACE FUNCTION definir_atualizado_em()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.atualizado_em := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


-- 2. USUARIOS


CREATE TABLE IF NOT EXISTS usuarios (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome_exibicao       VARCHAR(100) NOT NULL,
    nome_usuario        VARCHAR(50)  NOT NULL,
    email               VARCHAR(254) NOT NULL,
    senha_hash          VARCHAR(255) NOT NULL,
    avatar_url          TEXT,
    aceitou_termos      BOOLEAN NOT NULL DEFAULT FALSE,
    termos_aceitos_em   TIMESTAMPTZ,
    ativo               BOOLEAN NOT NULL DEFAULT TRUE,
    criado_em           TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT ck_usuarios_nome_exibicao
        CHECK (char_length(btrim(nome_exibicao)) BETWEEN 2 AND 100),

    CONSTRAINT ck_usuarios_nome_usuario
        CHECK (
            char_length(btrim(nome_usuario)) BETWEEN 3 AND 50
            AND nome_usuario ~ '^[A-Za-z0-9._-]+$'
        ),

    CONSTRAINT ck_usuarios_email
        CHECK (
            char_length(btrim(email)) BETWEEN 3 AND 254
            AND email ~* '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$'
        ),

    CONSTRAINT ck_usuarios_senha_hash
        CHECK (char_length(senha_hash) >= 20),

    CONSTRAINT ck_usuarios_avatar_url
        CHECK (
            avatar_url IS NULL
            OR btrim(avatar_url) = ''
            OR avatar_url ~* '^https?://'
        ),

    CONSTRAINT ck_usuarios_aceite_termos
        CHECK (
            (aceitou_termos = TRUE AND termos_aceitos_em IS NOT NULL)
            OR
            (aceitou_termos = FALSE AND termos_aceitos_em IS NULL)
        )
);

-- Unicidade sem diferenciar maiusculas/minusculas e ignorando espacos externos.
CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_nome_usuario_normalizado
    ON usuarios (lower(btrim(nome_usuario)));

CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_email_normalizado
    ON usuarios (lower(btrim(email)));

DROP TRIGGER IF EXISTS trg_usuarios_atualizado_em ON usuarios;
CREATE TRIGGER trg_usuarios_atualizado_em
BEFORE UPDATE ON usuarios
FOR EACH ROW
EXECUTE FUNCTION definir_atualizado_em();


-- 3. TOKENS DE LOGIN PERSISTENTE (LEMBRAR DE MIM)

CREATE TABLE IF NOT EXISTS tokens_login (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id      BIGINT NOT NULL,
    seletor         VARCHAR(64) NOT NULL,
    token_hash      VARCHAR(255) NOT NULL,
    expira_em       TIMESTAMPTZ NOT NULL,
    criado_em       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso_em   TIMESTAMPTZ,
    revogado_em     TIMESTAMPTZ,

    CONSTRAINT fk_tokens_login_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_tokens_login_seletor UNIQUE (seletor),

    CONSTRAINT ck_tokens_login_expiracao
        CHECK (expira_em > criado_em),

    CONSTRAINT ck_tokens_login_revogacao
        CHECK (revogado_em IS NULL OR revogado_em >= criado_em)
);

CREATE INDEX IF NOT EXISTS idx_tokens_login_usuario
    ON tokens_login (usuario_id);

CREATE INDEX IF NOT EXISTS idx_tokens_login_validade
    ON tokens_login (expira_em)
    WHERE revogado_em IS NULL;


-- 4. ESTANTES FIXAS

CREATE TABLE IF NOT EXISTS estantes (
    id          SMALLINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo      VARCHAR(20) NOT NULL,
    nome        VARCHAR(50) NOT NULL,
    ordem       SMALLINT NOT NULL,
    criada_em   TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_estantes_codigo UNIQUE (codigo),
    CONSTRAINT uq_estantes_nome UNIQUE (nome),
    CONSTRAINT uq_estantes_ordem UNIQUE (ordem),

    CONSTRAINT ck_estantes_codigo
        CHECK (codigo IN ('lendo', 'lido', 'quero_ler', 'favoritos')),

    CONSTRAINT ck_estantes_ordem
        CHECK (ordem BETWEEN 1 AND 4)
);

INSERT INTO estantes (codigo, nome, ordem)
VALUES
    ('lendo',     'Lendo',     1),
    ('lido',      'Lido',      2),
    ('quero_ler', 'Quero Ler', 3),
    ('favoritos', 'Favoritos', 4)
ON CONFLICT (codigo) DO NOTHING;

-- Impede criacao, alteracao ou exclusao das quatro estantes pela aplicacao.
CREATE OR REPLACE FUNCTION proteger_estantes_fixas()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'As estantes do sistema sao fixas e nao podem ser alteradas ou excluidas.';
END;
$$;

DROP TRIGGER IF EXISTS trg_proteger_estantes_update ON estantes;
CREATE TRIGGER trg_proteger_estantes_update
BEFORE UPDATE ON estantes
FOR EACH ROW
EXECUTE FUNCTION proteger_estantes_fixas();

DROP TRIGGER IF EXISTS trg_proteger_estantes_delete ON estantes;
CREATE TRIGGER trg_proteger_estantes_delete
BEFORE DELETE ON estantes
FOR EACH ROW
EXECUTE FUNCTION proteger_estantes_fixas();


-- 5. OBRAS


CREATE TABLE IF NOT EXISTS obras (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id          BIGINT NOT NULL,
    titulo              VARCHAR(255) NOT NULL,
    autor               VARCHAR(255) NOT NULL,
    plataforma          VARCHAR(100) NOT NULL,
    link_url            TEXT,
    capitulos_lidos     INTEGER,
    total_capitulos     INTEGER,
    origem              VARCHAR(20) NOT NULL DEFAULT 'manual',
    open_library_key    VARCHAR(100),
    criado_em           TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_obras_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT ck_obras_titulo
        CHECK (char_length(btrim(titulo)) BETWEEN 1 AND 255),

    CONSTRAINT ck_obras_autor
        CHECK (char_length(btrim(autor)) BETWEEN 1 AND 255),

    CONSTRAINT ck_obras_plataforma
        CHECK (char_length(btrim(plataforma)) BETWEEN 1 AND 100),

    CONSTRAINT ck_obras_link_url
        CHECK (
            link_url IS NULL
            OR btrim(link_url) = ''
            OR link_url ~* '^https?://[^[:space:]]+$'
        ),

    CONSTRAINT ck_obras_capitulos_lidos
        CHECK (capitulos_lidos IS NULL OR capitulos_lidos >= 0),

    CONSTRAINT ck_obras_total_capitulos
        CHECK (total_capitulos IS NULL OR total_capitulos > 0),

    CONSTRAINT ck_obras_progresso
        CHECK (
            capitulos_lidos IS NULL
            OR total_capitulos IS NULL
            OR capitulos_lidos <= total_capitulos
        ),

    CONSTRAINT ck_obras_origem
        CHECK (origem IN ('manual', 'open_library')),

    CONSTRAINT ck_obras_open_library
        CHECK (
            (origem = 'manual')
            OR
            (origem = 'open_library' AND plataforma = 'Open Library')
        )
);

-- Impede obra duplicada para o mesmo usuario, sem diferenciar maiusculas, minusculas ou espacos externos.
CREATE UNIQUE INDEX IF NOT EXISTS uq_obras_usuario_titulo_autor_plataforma
    ON obras (
        usuario_id,
        lower(btrim(titulo)),
        lower(btrim(autor)),
        lower(btrim(plataforma))
    );

CREATE INDEX IF NOT EXISTS idx_obras_usuario_criacao
    ON obras (usuario_id, criado_em DESC);

CREATE INDEX IF NOT EXISTS idx_obras_plataforma
    ON obras (usuario_id, lower(btrim(plataforma)));

DROP TRIGGER IF EXISTS trg_obras_atualizado_em ON obras;
CREATE TRIGGER trg_obras_atualizado_em
BEFORE UPDATE ON obras
FOR EACH ROW
EXECUTE FUNCTION definir_atualizado_em();


-- 6. RELACIONAMENTO MUITOS-PARA-MUITOS ENTRE OBRAS E ESTANTES
-- Uma obra pode estar em varias estantes, mas nunca repetida na mesma estante.

CREATE TABLE IF NOT EXISTS obras_estantes (
    obra_id         BIGINT NOT NULL,
    estante_id      SMALLINT NOT NULL,
    adicionada_em   TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_obras_estantes
        PRIMARY KEY (obra_id, estante_id),

    CONSTRAINT fk_obras_estantes_obra
        FOREIGN KEY (obra_id)
        REFERENCES obras(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_obras_estantes_estante
        FOREIGN KEY (estante_id)
        REFERENCES estantes(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_obras_estantes_estante_data
    ON obras_estantes (estante_id, adicionada_em DESC);


-- 7. REGRA: TODA OBRA DEVE PERMANECER EM PELO MENOS UMA ESTANTE

CREATE OR REPLACE FUNCTION validar_obra_com_estante()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_obra_id BIGINT;
BEGIN
    v_obra_id := COALESCE(NEW.obra_id, OLD.obra_id);

    -- Se a obra foi excluida, a verificacao nao e necessaria.
    IF NOT EXISTS (SELECT 1 FROM obras WHERE id = v_obra_id) THEN
        RETURN NULL;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM obras_estantes
        WHERE obra_id = v_obra_id
    ) THEN
        RAISE EXCEPTION 'Toda obra deve estar associada a pelo menos uma estante.';
    END IF;

    RETURN NULL;
END;
$$;

DROP TRIGGER IF EXISTS trg_validar_obra_com_estante ON obras_estantes;
CREATE CONSTRAINT TRIGGER trg_validar_obra_com_estante
AFTER INSERT OR UPDATE OR DELETE ON obras_estantes
DEFERRABLE INITIALLY DEFERRED
FOR EACH ROW
EXECUTE FUNCTION validar_obra_com_estante();


CREATE OR REPLACE FUNCTION validar_nova_obra_com_estante()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM obras_estantes
        WHERE obra_id = NEW.id
    ) THEN
        RAISE EXCEPTION 'Toda obra deve ser salva em pelo menos uma estante.';
    END IF;

    RETURN NULL;
END;
$$;

DROP TRIGGER IF EXISTS trg_validar_nova_obra_com_estante ON obras;
CREATE CONSTRAINT TRIGGER trg_validar_nova_obra_com_estante
AFTER INSERT ON obras
DEFERRABLE INITIALLY DEFERRED
FOR EACH ROW
EXECUTE FUNCTION validar_nova_obra_com_estante();


-- 8. VIEWS PARA CONSULTAS DO PERFIL E RESUMO

CREATE OR REPLACE VIEW vw_obras_por_estante AS
SELECT
    o.usuario_id,
    o.id AS obra_id,
    o.titulo,
    o.autor,
    o.plataforma,
    o.link_url,
    o.capitulos_lidos,
    o.total_capitulos,
    CASE
        WHEN o.total_capitulos IS NULL THEN NULL
        ELSE ROUND((o.capitulos_lidos::NUMERIC / o.total_capitulos::NUMERIC) * 100, 2)
    END AS progresso_percentual,
    e.id AS estante_id,
    e.codigo AS estante_codigo,
    e.nome AS estante_nome,
    oe.adicionada_em,
    o.criado_em,
    o.atualizado_em
FROM obras o
JOIN obras_estantes oe ON oe.obra_id = o.id
JOIN estantes e ON e.id = oe.estante_id;

CREATE OR REPLACE VIEW vw_resumo_estantes AS
SELECT
    o.usuario_id,
    e.codigo AS estante_codigo,
    e.nome AS estante_nome,
    COUNT(*)::BIGINT AS quantidade_obras
FROM obras o
JOIN obras_estantes oe ON oe.obra_id = o.id
JOIN estantes e ON e.id = oe.estante_id
GROUP BY o.usuario_id, e.id, e.codigo, e.nome, e.ordem
ORDER BY o.usuario_id, e.ordem;

CREATE OR REPLACE VIEW vw_resumo_plataformas AS
SELECT
    usuario_id,
    plataforma,
    COUNT(*)::BIGINT AS quantidade_obras
FROM obras
GROUP BY usuario_id, plataforma;

CREATE OR REPLACE VIEW vw_total_obras AS
SELECT
    usuario_id,
    COUNT(*)::BIGINT AS total_obras
FROM obras
GROUP BY usuario_id;

COMMIT;