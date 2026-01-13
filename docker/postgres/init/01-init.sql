-- Script de inicialização do PostgreSQL
-- Executado apenas na primeira vez que o container é criado

-- Criar extensões úteis
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- Configurar timezone
SET timezone = 'America/Bahia';

-- Log
\echo '✅ Banco de dados inicializado com sucesso!'

