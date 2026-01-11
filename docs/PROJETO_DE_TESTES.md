# 4.1 PROJETO DE TESTES

Este capítulo apresenta as estratégias, metodologias e ferramentas utilizadas para validação e verificação do Sistema de Gestão do Restaurante Institucional (RI IFBA), garantindo a qualidade e conformidade do software desenvolvido.

## 4.1.1 Estratégias de Testes

A estratégia de testes adotada neste projeto segue uma abordagem em camadas, combinando testes automatizados e manuais para garantir a cobertura adequada das funcionalidades implementadas.

### 4.1.1.1 Testes Unitários

Os testes unitários têm como objetivo validar o comportamento isolado das unidades de código, como métodos de Models e Services. Segundo Pressman e Maxim (2021), testes unitários são fundamentais para identificar defeitos precocemente no ciclo de desenvolvimento.

Foram desenvolvidos testes unitários para:

- **Model User**: Validação dos métodos `isBolsista()`, `isAdmin()`, `isEstudante()`, `atingiuLimiteFaltas()` e `temDireitoRefeicaoNoDia()`;
- **CardapioService**: Validação das operações CRUD de cardápios;
- **PresencaService**: Validação da lógica de confirmação de presença e marcação de faltas.

### 4.1.1.2 Testes de Integração (Feature Tests)

Os testes de integração verificam a interação entre múltiplos componentes do sistema, incluindo banco de dados, rotas HTTP e camada de autenticação. Estes testes simulam requisições reais à API e validam as respostas esperadas.

Foram desenvolvidos testes de feature para:

- Fluxo completo de confirmação de presença de bolsistas;
- Validação de regras de negócio na API;
- Tratamento de exceções e respostas HTTP.

### 4.1.1.3 Testes Manuais

Além dos testes automatizados, foram realizados testes manuais utilizando a ferramenta Postman para validação das rotas da API durante o desenvolvimento. A coleção de requisições está disponível no diretório `postman/` do projeto.

## 4.1.2 Ferramentas Utilizadas

O Quadro 1 apresenta as ferramentas utilizadas no processo de testes do sistema.

**Quadro 1 – Ferramentas de Testes Utilizadas**

| Ferramenta | Versão | Finalidade |
|------------|--------|------------|
| PHPUnit | 11.5 | Framework de testes unitários para PHP |
| Mockery | 1.6 | Biblioteca para criação de mocks e stubs |
| Laravel Testing | 12.0 | Utilitários de teste integrados ao framework |
| Postman | - | Testes manuais de API REST |
| PostgreSQL | 15+ | Banco de dados relacional para testes |

Fonte: Elaborado pelo autor (2026).

## 4.1.3 Casos de Teste

O Quadro 2 apresenta os principais casos de teste implementados e sua relação com os requisitos funcionais do sistema.

**Quadro 2 – Casos de Teste e Requisitos Relacionados**

| ID | Caso de Teste | Tipo | Requisito |
|----|---------------|------|-----------|
| CT01 | Usuário pode ser identificado como bolsista | Unitário | RF14 |
| CT02 | Usuário pode ser identificado como administrador | Unitário | RF14 |
| CT03 | Sistema verifica limite de faltas do usuário | Unitário | RF10 |
| CT04 | Cardápio pode ser criado com dados válidos | Unitário | RF08 |
| CT05 | Cardápio pode ser atualizado | Unitário | RF08 |
| CT06 | Bolsista pode confirmar presença | Feature | RF13 |
| CT07 | Não-bolsista não pode confirmar presença | Feature | RF13 |
| CT08 | Sistema valida direito à refeição por dia | Unitário | RF09 |

Fonte: Elaborado pelo autor (2026).

## 4.1.4 Ambiente de Testes

Os testes são executados em um ambiente isolado utilizando:

- **Banco de dados PostgreSQL**: Mesmo banco utilizado em produção, garantindo fidelidade dos testes ao ambiente real;
- **Factories**: Classes de fabricação de dados utilizando a biblioteca Faker para geração de dados fictícios consistentes;
- **Traits de teste**: `RefreshDatabase` para resetar o banco entre cada teste.

## 4.1.5 Execução dos Testes

Para executar os testes automatizados, utiliza-se o comando:

```bash
php artisan test
```

Ou, para mais detalhes sobre a execução:

```bash
./vendor/bin/phpunit --testdox
```

## 4.1.6 Resultados Esperados

Todos os testes implementados devem passar com sucesso, validando:

1. **Corretude funcional**: As funcionalidades atendem aos requisitos especificados;
2. **Tratamento de exceções**: O sistema responde adequadamente a entradas inválidas;
3. **Integridade dos dados**: As operações no banco de dados mantêm a consistência.

---

## Referências

PRESSMAN, R. S.; MAXIM, B. R. **Engenharia de Software**: Uma Abordagem Profissional. 9. ed. Porto Alegre: AMGH, 2021.

LARAVEL. **Laravel Testing Documentation**. Disponível em: https://laravel.com/docs/testing. Acesso em: 10 jan. 2026.

PHPUNIT. **PHPUnit Manual**. Disponível em: https://phpunit.de/manual/current/en/. Acesso em: 10 jan. 2026.
