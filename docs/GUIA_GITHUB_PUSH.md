# 🚀 Guia: Subindo o Projeto para o GitHub

## ✅ Passo 1: Commit Realizado com Sucesso!

O commit já foi criado com todas as mudanças:

```
✅ feat: Implementação completa do RF13 - Validação de Presença
```

**Arquivos commitados:**
- ✅ 11 novos arquivos criados
- ✅ 15 arquivos modificados
- ✅ Documentação completa

---

## 📋 Passo 2: Criar Repositório no GitHub

### **Opção A: Via Site (Recomendado)**

1. **Acesse:** https://github.com
2. **Login** na sua conta
3. Clique no **+** (canto superior direito)
4. Selecione **"New repository"**

### **Configurações do Repositório:**

```
Repository name: ri_ifba_v1_backend
Description: Sistema de Restaurante Institucional - Backend Laravel
```

**Importante:**
- ✅ **Public** ou **Private** (escolha conforme sua preferência)
- ❌ **NÃO marque** "Initialize this repository with a README"
- ❌ **NÃO adicione** .gitignore (já temos)
- ❌ **NÃO adicione** license (já temos se quiser)

5. Clique em **"Create repository"**

---

## 🔗 Passo 3: Conectar Repositório Local ao GitHub

Após criar o repositório, o GitHub vai mostrar comandos. Use estes comandos no PowerShell:

### **A. Adicionar o Remote:**

```powershell
cd C:\Users\emane\OneDrive\Documentos\TCC\ri_ifba_v1\ri_ifba_v1_backend

git remote add origin https://github.com/SEU_USUARIO/ri_ifba_v1_backend.git
```

**⚠️ IMPORTANTE:** Substitua `SEU_USUARIO` pelo seu nome de usuário do GitHub!

---

### **B. Verificar Branch Atual:**

```powershell
git branch
```

Se estiver em `master`, renomeie para `main` (padrão do GitHub):

```powershell
git branch -M main
```

---

### **C. Fazer o Push (Enviar):**

```powershell
git push -u origin main
```

**Primeira vez:** O GitHub vai pedir autenticação:
- **Username:** Seu usuário do GitHub
- **Password:** Use um **Personal Access Token** (não a senha da conta)

---

## 🔑 Passo 4: Criar Personal Access Token (se necessário)

Se o GitHub pedir senha e não funcionar:

1. Acesse: https://github.com/settings/tokens
2. Clique em **"Generate new token"** → **"Generate new token (classic)"**
3. **Note:** Digite algo como "Push from PC"
4. **Expiration:** Escolha 90 dias ou mais
5. **Scopes:** Marque **`repo`** (acesso completo aos repositórios)
6. Clique em **"Generate token"**
7. **⚠️ COPIE o token!** (não poderá ver novamente)

Use esse token como senha ao fazer `git push`.

---

## 📦 Comandos Completos (Resumo)

Execute estes comandos em ordem:

```powershell
# 1. Navegue até o projeto
cd C:\Users\emane\OneDrive\Documentos\TCC\ri_ifba_v1\ri_ifba_v1_backend

# 2. Adicione o remote (substitua SEU_USUARIO)
git remote add origin https://github.com/SEU_USUARIO/ri_ifba_v1_backend.git

# 3. Renomeie branch para main
git branch -M main

# 4. Faça o push
git push -u origin main
```

---

## ✅ Verificar se Funcionou

Após o `git push`, acesse:

```
https://github.com/SEU_USUARIO/ri_ifba_v1_backend
```

Você deve ver:
- ✅ Todos os arquivos do projeto
- ✅ Commit: "feat: Implementação completa do RF13..."
- ✅ Estrutura de pastas (app, public, docs, etc.)

---

## 🔄 Commits Futuros

Após configurar o remote, para próximas mudanças:

```powershell
# 1. Adicionar arquivos modificados
git add .

# 2. Fazer commit
git commit -m "feat: descrição da mudança"

# 3. Enviar para GitHub
git push
```

---

## 📊 Estrutura que Será Enviada

```
ri_ifba_v1_backend/
├── app/
│   ├── Http/Controllers/api/v1/Admin/
│   │   ├── PresencaController.php ✅
│   │   └── RelatorioValidacaoController.php ✅
│   ├── Models/
│   │   ├── Presenca.php ✅
│   │   └── Cardapio.php ✅
│   └── Enums/
├── public/
│   ├── validar-presenca-qrcode.html ✅
│   ├── lista-presencas-dia.html ✅
│   ├── relatorio-validacoes.html ✅
│   └── teste-api-presencas.html ✅
├── routes/
│   └── api.php ✅
├── docs/
│   ├── RF13_VALIDACAO_QRCODE_MATRICULA.md ✅
│   ├── RF13_LISTA_PRESENCAS_DIA.md ✅
│   ├── RELATORIO_VALIDACOES_ADMIN.md ✅
│   ├── SISTEMA_PRESENCA_COMPLETO.md ✅
│   ├── CORRECAO_ERRO_500_USER.md ✅
│   └── CORRECAO_LISTA_PRESENCAS.md ✅
├── database/
├── config/
└── README.md
```

---

## 🎯 Checklist Final

Antes de fazer o push, verifique:

- [ ] Repositório criado no GitHub
- [ ] Remote configurado (`git remote -v`)
- [ ] Branch renomeado para `main` (se necessário)
- [ ] Personal Access Token criado (se necessário)
- [ ] Arquivos sensíveis não estão sendo enviados (.env está no .gitignore)

---

## ⚠️ Arquivos que NÃO devem ir para o GitHub

Verifique se o `.gitignore` contém:

```
/vendor
/node_modules
.env
.env.backup
database.sqlite
storage/*.key
```

✅ Esses arquivos **NÃO** serão enviados (já está configurado no Laravel).

---

## 🎉 Resultado Final

Após o push, seu projeto estará:
- ✅ No GitHub (backup seguro)
- ✅ Versionado (histórico completo)
- ✅ Compartilhável (se público)
- ✅ Acessível de qualquer lugar

---

## 📞 Precisa de Ajuda?

Se encontrar algum erro durante o push:

1. **Erro de autenticação:** Use Personal Access Token
2. **Erro "repository not found":** Verifique o nome do repositório
3. **Erro "failed to push":** Pode precisar fazer `git pull` primeiro

---

**🚀 Pronto para fazer o push!**

Execute os comandos do **Passo 3** e seu projeto estará no GitHub! ✨

