-- ============================================================================
-- DDL PostgreSQL - Sistema RI IFBA
-- Gerado em: 2026-01-11
-- ============================================================================

-- ============================================================================
-- TIPOS ENUMERADOS
-- ============================================================================

CREATE TYPE perfil_usuario AS ENUM ('estudante', 'admin');
CREATE TYPE turno_refeicao AS ENUM ('almoco', 'jantar');
CREATE TYPE status_presenca AS ENUM ('confirmado', 'validado', 'presente', 'falta_justificada', 'falta_injustificada', 'cancelado');
CREATE TYPE tipo_justificativa AS ENUM ('antecipada', 'posterior');
CREATE TYPE status_justificativa AS ENUM ('pendente', 'aprovada', 'rejeitada');
CREATE TYPE status_fila AS ENUM ('inscrito', 'aprovado', 'rejeitado');

-- ============================================================================
-- TABELA: users
-- ============================================================================

CREATE TABLE users (
    id                    BIGSERIAL PRIMARY KEY,
    matricula             VARCHAR(20) NOT NULL UNIQUE,
    nome                  VARCHAR(100) NOT NULL,
    email                 VARCHAR(120) NOT NULL UNIQUE,
    email_verified_at     TIMESTAMP NULL,
    password              VARCHAR(255) NOT NULL,
    remember_token        VARCHAR(100) NULL,
    
    perfil                perfil_usuario NOT NULL,
    bolsista              BOOLEAN DEFAULT FALSE,
    limite_faltas_mes     INTEGER DEFAULT 3,
    
    desligado             BOOLEAN DEFAULT FALSE,
    desligado_em          TIMESTAMP NULL,
    desligado_motivo      TEXT NULL,
    
    curso                 VARCHAR(100) NULL,
    turno                 VARCHAR(20) NULL,
    preferencia_alimentar VARCHAR(50) NULL,
    foto_perfil           VARCHAR(255) NULL,
    
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_perfil ON users(perfil);
CREATE INDEX idx_users_bolsista ON users(bolsista);
CREATE INDEX idx_users_desligado ON users(desligado);
CREATE INDEX idx_users_matricula ON users(matricula);

-- ============================================================================
-- TABELA: cardapios
-- ============================================================================

CREATE TABLE cardapios (
    id                     BIGSERIAL PRIMARY KEY,
    data_do_cardapio       DATE NOT NULL UNIQUE,
    
    prato_principal_ptn01  VARCHAR(255) NOT NULL,
    prato_principal_ptn02  VARCHAR(255) NOT NULL,
    guarnicao              VARCHAR(255) NULL,
    acompanhamento_01      VARCHAR(255) NOT NULL,
    acompanhamento_02      VARCHAR(255) NOT NULL,
    salada                 VARCHAR(255) NULL,
    ovo_lacto_vegetariano  VARCHAR(255) NULL,
    suco                   VARCHAR(100) NULL,
    sobremesa              VARCHAR(100) NULL,
    
    criado_por             BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    
    criado_em              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cardapios_data ON cardapios(data_do_cardapio);

-- ============================================================================
-- TABELA: refeicoes
-- ============================================================================

CREATE TABLE refeicoes (
    id               BIGSERIAL PRIMARY KEY,
    cardapio_id      BIGINT NOT NULL REFERENCES cardapios(id) ON DELETE CASCADE,
    data_do_cardapio DATE NOT NULL,
    turno            turno_refeicao NOT NULL,
    capacidade       INTEGER NULL,
    
    criado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(cardapio_id, turno)
);

CREATE INDEX idx_refeicoes_data ON refeicoes(data_do_cardapio);
CREATE INDEX idx_refeicoes_turno ON refeicoes(turno);

-- ============================================================================
-- TABELA: presencas
-- ============================================================================

CREATE TABLE presencas (
    id                  BIGSERIAL PRIMARY KEY,
    user_id             BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    refeicao_id         BIGINT NOT NULL REFERENCES refeicoes(id) ON DELETE CASCADE,
    
    status_da_presenca  status_presenca NOT NULL,
    
    validado_em         TIMESTAMP NULL,
    validado_por        BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    registrado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, refeicao_id)
);

CREATE INDEX idx_presencas_user ON presencas(user_id);
CREATE INDEX idx_presencas_refeicao ON presencas(refeicao_id);
CREATE INDEX idx_presencas_validado_em ON presencas(validado_em);

-- ============================================================================
-- TABELA: justificativas
-- ============================================================================

CREATE TABLE justificativas (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    refeicao_id     BIGINT NULL REFERENCES refeicoes(id) ON DELETE SET NULL,
    
    tipo            tipo_justificativa NOT NULL,
    motivo          TEXT NOT NULL,
    anexo           VARCHAR(255) NULL,
    
    status          status_justificativa DEFAULT 'pendente',
    avaliado_por    BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    avaliado_em     TIMESTAMP NULL,
    motivo_rejeicao TEXT NULL,
    
    enviado_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_justificativas_user ON justificativas(user_id);
CREATE INDEX idx_justificativas_refeicao ON justificativas(refeicao_id);
CREATE INDEX idx_justificativas_tipo ON justificativas(tipo);
CREATE INDEX idx_justificativas_status ON justificativas(status);

-- ============================================================================
-- TABELA: filas_extras
-- ============================================================================

CREATE TABLE filas_extras (
    id                 BIGSERIAL PRIMARY KEY,
    user_id            BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    refeicao_id        BIGINT NOT NULL REFERENCES refeicoes(id) ON DELETE CASCADE,
    
    status_fila_extras status_fila DEFAULT 'inscrito',
    inscrito_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(user_id, refeicao_id)
);

CREATE INDEX idx_filas_extras_user ON filas_extras(user_id);
CREATE INDEX idx_filas_extras_refeicao ON filas_extras(refeicao_id);
CREATE INDEX idx_filas_extras_status ON filas_extras(status_fila_extras);

-- ============================================================================
-- TABELA: usuario_dias_semana
-- ============================================================================

CREATE TABLE usuario_dias_semana (
    user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    dia_semana  SMALLINT NOT NULL CHECK (dia_semana >= 0 AND dia_semana <= 6),
    
    PRIMARY KEY (user_id, dia_semana)
);

CREATE INDEX idx_usuario_dias_user ON usuario_dias_semana(user_id);
CREATE INDEX idx_usuario_dias_dia ON usuario_dias_semana(dia_semana);

COMMENT ON COLUMN usuario_dias_semana.dia_semana IS '0=Domingo, 1=Segunda, ..., 6=Sábado';

-- ============================================================================
-- TABELA: bolsistas (lista de matrículas aprovadas)
-- ============================================================================

CREATE TABLE bolsistas (
    id           BIGSERIAL PRIMARY KEY,
    matricula    VARCHAR(50) NOT NULL UNIQUE,
    nome         VARCHAR(100) NULL,
    curso        VARCHAR(100) NULL,
    turno        VARCHAR(20) NULL,
    dias_semana  JSONB NULL,
    ativo        BOOLEAN DEFAULT TRUE,
    
    user_id      BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    vinculado_em TIMESTAMP NULL,
    
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_bolsistas_ativo ON bolsistas(ativo);
CREATE INDEX idx_bolsistas_matricula ON bolsistas(matricula);

-- ============================================================================
-- TABELA: notifications (Laravel)
-- ============================================================================

CREATE TABLE notifications (
    id              UUID PRIMARY KEY,
    type            VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id   BIGINT NOT NULL,
    data            JSONB NOT NULL,
    read_at         TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_notifiable ON notifications(notifiable_type, notifiable_id);

-- ============================================================================
-- TABELA: sessions (Laravel)
-- ============================================================================

CREATE TABLE sessions (
    id            VARCHAR(255) PRIMARY KEY,
    user_id       BIGINT NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX idx_sessions_user ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- ============================================================================
-- TABELA: personal_access_tokens (Sanctum)
-- ============================================================================

CREATE TABLE personal_access_tokens (
    id             BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id   BIGINT NOT NULL,
    name           VARCHAR(255) NOT NULL,
    token          VARCHAR(64) NOT NULL UNIQUE,
    abilities      TEXT NULL,
    last_used_at   TIMESTAMP NULL,
    expires_at     TIMESTAMP NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);
