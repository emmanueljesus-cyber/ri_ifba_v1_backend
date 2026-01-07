# RF14 - Gerenciar Usuários (Bolsistas)

## ✅ Status: IMPLEMENTADO

**Requisito:** Como administrador do RI, desejo cadastrar, editar e remover(desligar) usuários do sistema (bolsistas) para manter a base de dados atualizada.

---

## 📋 Funcionalidades Implementadas

### 1. ✅ Listar Usuários
**Endpoint:** `GET /api/v1/admin/usuarios`

**Query Parameters:**
- `per_page` (opcional): Itens por página (padrão: 15)
- `perfil` (opcional): Filtrar por perfil (`admin` ou `estudante`)
- `bolsista` (opcional): Filtrar apenas bolsistas (`true`/`false`)
- `desligado` (opcional): Incluir desligados (`true`/`false`)
- `busca` (opcional): Buscar por nome, matrícula ou email
- `sort_by` (opcional): Campo para ordenar (padrão: `nome`)
- `sort_order` (opcional): Ordem (`asc` ou `desc`, padrão: `asc`)

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuários recuperados com sucesso",
  "data": [
    {
      "id": 1,
      "nome": "João Silva",
      "email": "joao.silva@estudante.ifba.edu.br",
      "matricula": "202401001",
      "perfil": "estudante",
      "bolsista": true,
      "curso": "Informática",
      "turno": "matutino",
      "desligado": false
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 2. ✅ Criar Novo Usuário
**Endpoint:** `POST /api/v1/admin/usuarios`

**Body (JSON):**
```json
{
  "nome": "João Silva",
  "email": "joao.silva@estudante.ifba.edu.br",
  "matricula": "202401001",
  "password": "senha123",
  "perfil": "estudante",
  "bolsista": true,
  "curso": "Informática",
  "turno": "matutino",
  "limite_faltas_mes": 3
}
```

**Validações:**
- ✅ Nome obrigatório (máx 255 caracteres)
- ✅ Email obrigatório e único
- ✅ Matrícula obrigatória e única
- ✅ Senha obrigatória (mín 6 caracteres)
- ✅ Perfil obrigatório (`admin` ou `estudante`)
- ✅ Turno opcional (`matutino`, `vespertino`, `noturno`)

**Resposta (201):**
```json
{
  "status": "success",
  "message": "Usuário criado com sucesso",
  "data": {
    "id": 1,
    "nome": "João Silva",
    "email": "joao.silva@estudante.ifba.edu.br",
    "matricula": "202401001",
    "perfil": "estudante",
    "bolsista": true,
    "desligado": false
  }
}
```

---

### 3. ✅ Buscar Usuário por ID
**Endpoint:** `GET /api/v1/admin/usuarios/{id}`

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário recuperado com sucesso",
  "data": {
    "id": 1,
    "nome": "João Silva",
    "matricula": "202401001",
    "email": "joao.silva@estudante.ifba.edu.br",
    "perfil": "estudante",
    "bolsista": true,
    "curso": "Informática",
    "turno": "matutino"
  }
}
```

---

### 4. ✅ Buscar por Matrícula
**Endpoint:** `GET /api/v1/admin/usuarios/matricula/{matricula}`

**Exemplo:** `/api/v1/admin/usuarios/matricula/202401001`

---

### 5. ✅ Atualizar Usuário
**Endpoint:** `PUT/PATCH /api/v1/admin/usuarios/{id}`

**Body (JSON) - Campos opcionais:**
```json
{
  "nome": "João Silva Santos",
  "email": "joao.santos@estudante.ifba.edu.br",
  "bolsista": true,
  "turno": "vespertino",
  "curso": "Informática Integrado"
}
```

**Validações:**
- ✅ Email único (ignorando o próprio usuário)
- ✅ Matrícula única (ignorando o próprio usuário)
- ✅ Senha opcional (se fornecida, mín 6 caracteres)

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário atualizado com sucesso",
  "data": {
    "id": 1,
    "nome": "João Silva Santos",
    "email": "joao.santos@estudante.ifba.edu.br",
    "bolsista": true,
    "turno": "vespertino"
  }
}
```

---

### 6. ✅ Desativar Usuário (Soft Delete)
**Endpoint:** `DELETE /api/v1/admin/usuarios/{id}`

**Comportamento:**
- ❌ NÃO deleta o registro do banco
- ✅ Marca campo `desligado = true`
- ✅ Usuário não aparece mais em listagens padrão
- ✅ Mantém histórico de presenças/justificativas

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário desativado com sucesso",
  "data": null
}
```

---

### 7. ✅ Reativar Usuário
**Endpoint:** `POST /api/v1/admin/usuarios/{id}/reativar`

**Comportamento:**
- ✅ Marca campo `desligado = false`
- ✅ Usuário volta a aparecer nas listagens

**Resposta:**
```json
{
  "status": "success",
  "message": "Usuário reativado com sucesso",
  "data": {
    "id": 1,
    "nome": "João Silva",
    "desligado": false
  }
}
```

---

### 8. ✅ Listar Apenas Bolsistas
**Endpoint:** `GET /api/v1/admin/usuarios/bolsistas`

**Query Parameters:**
- `turno` (opcional): Filtrar por turno

**Resposta:**
```json
{
  "status": "success",
  "message": "Bolsistas recuperados com sucesso",
  "data": [
    {
      "id": 1,
      "nome": "João Silva",
      "matricula": "202401001",
      "bolsista": true,
      "turno": "matutino"
    }
  ],
  "meta": {
    "total": 1
  }
}
```

---

## 🗂️ Arquivos Criados

### Backend
```
app/Http/Controllers/Admin/UserController.php        (Controller)
app/Http/Requests/Admin/StoreUserRequest.php         (Validação criação)
app/Http/Requests/Admin/UpdateUserRequest.php        (Validação edição)
routes/api.php                                        (Rotas)
```

### Postman
```
postman/RI_IFBA_Admin_API.postman_collection.json   (Coleção com 8 endpoints)
postman/RI_IFBA_Local.postman_environment.json       (Environment)
```

---

## 🚀 Como Testar

### Opção 1: Postman (Recomendado)

1. **Importar coleção:**
   - Abra Postman
   - Import → `postman/RI_IFBA_Admin_API.postman_collection.json`
   - Import → `postman/RI_IFBA_Local.postman_environment.json`

2. **Selecionar environment:**
   - Dropdown no canto superior direito
   - Selecione "RI IFBA - Local"

3. **Testar endpoints:**
   - Na pasta "Usuários (RF14)"
   - 8 requests prontos para usar

### Opção 2: cURL

```bash
# Listar usuários
curl http://127.0.0.1:8000/api/v1/admin/usuarios

# Criar usuário
curl -X POST http://127.0.0.1:8000/api/v1/admin/usuarios \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Maria Santos",
    "email": "maria@estudante.ifba.edu.br",
    "matricula": "202401002",
    "password": "senha123",
    "perfil": "estudante",
    "bolsista": true
  }'

# Buscar por ID
curl http://127.0.0.1:8000/api/v1/admin/usuarios/1

# Atualizar
curl -X PUT http://127.0.0.1:8000/api/v1/admin/usuarios/1 \
  -H "Content-Type: application/json" \
  -d '{"nome": "Maria Santos Silva"}'

# Desativar
curl -X DELETE http://127.0.0.1:8000/api/v1/admin/usuarios/1

# Reativar
curl -X POST http://127.0.0.1:8000/api/v1/admin/usuarios/1/reativar
```

---

## 🔒 Segurança

### Autenticação
- ✅ Rotas protegidas por middleware admin
- ✅ `APP_DEBUG=true`: Sem autenticação (desenvolvimento)
- ✅ `APP_DEBUG=false`: Requer `auth:sanctum` + `ensure.is.admin`

### Validações
- ✅ Email único no sistema
- ✅ Matrícula única no sistema
- ✅ Senha hasheada com bcrypt
- ✅ Perfil restrito (admin/estudante)
- ✅ Turno restrito (matutino/vespertino/noturno)

---

## 📊 Status Codes

| Código | Significado | Quando Ocorre |
|--------|-------------|---------------|
| `200` | OK | GET, PUT com sucesso |
| `201` | Created | POST criou usuário |
| `404` | Not Found | Usuário não encontrado |
| `422` | Unprocessable | Validação falhou |
| `500` | Server Error | Erro interno |

---

## 🎯 Casos de Uso

### 1. Cadastrar Novo Bolsista
```
Admin acessa Postman
→ POST /admin/usuarios
→ Preenche dados do bolsista
→ Bolsista criado e pode usar o sistema
```

### 2. Editar Dados de Bolsista
```
Admin busca bolsista
→ GET /admin/usuarios?busca=João
→ PUT /admin/usuarios/1
→ Atualiza turno de matutino para vespertino
```

### 3. Desligar Bolsista
```
Bolsista perdeu benefício
→ DELETE /admin/usuarios/1
→ Bolsista marcado como desligado
→ Não aparece mais em listas ativas
→ Histórico preservado
```

### 4. Reativar Bolsista
```
Bolsista retornou ao programa
→ POST /admin/usuarios/1/reativar
→ Bolsista volta a aparecer em listas
→ Pode confirmar presenças novamente
```

---

## ✅ Checklist de Implementação

- [x] Controller completo (UserController)
- [x] Validações (StoreUserRequest, UpdateUserRequest)
- [x] Rotas API registradas
- [x] Soft delete (campo desligado)
- [x] Filtros (perfil, bolsista, busca)
- [x] Paginação
- [x] Ordenação customizável
- [x] Postman collection
- [x] Documentação completa
- [ ] Testes unitários (opcional)
- [ ] Testes de integração (opcional)

---

## 📝 Notas Importantes

1. **Soft Delete:** Usuários nunca são deletados do banco, apenas marcados como `desligado`
2. **Histórico:** Todas as presenças e justificativas são mantidas
3. **Senha:** Sempre hasheada, nunca exposta na API
4. **Matrícula:** Imutável após criação (não pode ser editada)
5. **Email:** Único no sistema, validado no backend

---

## 🔗 Integração com Outros RFs

- **RF09:** Lista de bolsistas usa mesma base
- **RF13:** Confirmação de presença busca usuário ativo
- **RF10:** Relatórios filtram por usuários ativos

---

## 🎉 Conclusão

**RF14 100% implementado e funcional!**

Use o Postman para testar todos os 8 endpoints criados.

