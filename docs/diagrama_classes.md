# Diagrama de Classes - RI IFBA

## Visão Geral da Arquitetura

```mermaid
classDiagram
    direction TB
    
    class PerfilController {
        -ImagemPerfilService imagemService
        +show() JsonResponse
        +update() JsonResponse
        +atualizarFoto() JsonResponse
        +removerFoto() JsonResponse
    }
    
    class CardapioController {
        -CardapioService service
        +index() JsonResponse
        +store() JsonResponse
        +show() JsonResponse
        +update() JsonResponse
        +destroy() JsonResponse
    }
    
    class PresencaController {
        -PresencaService service
        +index() JsonResponse
        +confirmarPresenca() JsonResponse
        +validarLote() JsonResponse
        +marcarFalta() JsonResponse
    }
    
    class BolsistaController {
        -PresencaService presencaService
        +todosBolsistas() JsonResponse
        +bolsistasDoDia() JsonResponse
        +confirmarPresenca() JsonResponse
    }
    
    class JustificativaController {
        -JustificativaService service
        +index() JsonResponse
        +store() JsonResponse
        +aprovar() JsonResponse
        +rejeitar() JsonResponse
    }
    
    class DashboardController {
        -DashboardService service
        +index() JsonResponse
        +resumo() JsonResponse
        +taxaPresenca() JsonResponse
    }

    class CardapioService {
        +listar()
        +criar()
        +atualizar()
        +excluir()
        +cardapioHoje()
    }
    
    class PresencaService {
        +confirmar()
        +validar()
        +marcarFalta()
        +validarPorQrCode()
    }
    
    class JustificativaService {
        +listar()
        +criar()
        +aprovar()
        +rejeitar()
    }
    
    class DashboardService {
        +resumoGeral()
        +taxaPresenca()
        +faltasDoMes()
    }
    
    class ImagemPerfilService {
        +processarEsalvar()
        +remover()
        -redimensionar()
    }
    
    class NotificacaoService {
        +notificar()
        +naoLidas()
        +marcarComoLida()
    }

    class User {
        +id bigint
        +matricula string
        +nome string
        +email string
        +perfil enum
        +bolsista bool
        +foto_perfil string
        +presencas()
        +justificativas()
        +isAdmin()
        +isBolsista()
    }
    
    class Cardapio {
        +id bigint
        +data_do_cardapio date
        +prato_principal_ptn01 string
        +ovo_lacto_vegetariano string
        +refeicoes()
    }
    
    class Refeicao {
        +id bigint
        +cardapio_id bigint
        +turno enum
        +capacidade int
        +presencas()
    }
    
    class Presenca {
        +id bigint
        +user_id bigint
        +refeicao_id bigint
        +status_da_presenca enum
        +validado_em timestamp
    }
    
    class Justificativa {
        +id bigint
        +user_id bigint
        +motivo text
        +status enum
        +anexo string
    }
    
    class FilaExtra {
        +id bigint
        +user_id bigint
        +refeicao_id bigint
        +status_fila_extras enum
    }
    
    class Bolsista {
        +id bigint
        +matricula string
        +ativo bool
        +user_id bigint
    }

    PerfilController --> ImagemPerfilService
    CardapioController --> CardapioService
    PresencaController --> PresencaService
    BolsistaController --> PresencaService
    JustificativaController --> JustificativaService
    DashboardController --> DashboardService
    
    CardapioService --> Cardapio
    PresencaService --> Presenca
    JustificativaService --> Justificativa
    ImagemPerfilService --> User
    
    User "1" --> "*" Presenca
    User "1" --> "*" Justificativa
    User "1" --> "*" FilaExtra
    Cardapio "1" --> "*" Refeicao
    Refeicao "1" --> "*" Presenca
    Bolsista "1" --> "0..1" User
```

---

## Camadas da Aplicação

| Camada | Responsabilidade |
|--------|------------------|
| **Controllers** | Receber requisições, validar input, retornar JSON |
| **Services** | Lógica de negócio, regras de domínio |
| **Models** | Representação das entidades, relacionamentos |

---

## Services do Sistema

| Service | Responsabilidade |
|---------|------------------|
| `CardapioService` | CRUD de cardápios |
| `PresencaService` | Controle de presenças |
| `JustificativaService` | Fluxo de justificativas |
| `DashboardService` | Métricas e estatísticas |
| `ImagemPerfilService` | Upload de fotos |
| `NotificacaoService` | Notificações |
| `UserService` | Gerenciamento de usuários |
| `RelatorioService` | Geração de relatórios |
