# Workflow - Sistema de Aluguel de Veículos

## Visão Geral da Arquitetura

Este documento descreve a arquitetura, fluxo de trabalho e componentes do Sistema de Aluguel de Veículos. O sistema foi construído utilizando o framework Laravel e integrado com Elasticsearch para oferecer buscas avançadas e eficientes. Adicionalmente, um serviço Python baseado em FastAPI foi implementado para fornecer relatórios e processamento analítico.

O projeto implementa uma arquitetura em camadas com princípios SOLID, utilizando padrões de design como Repository Pattern, Dependency Injection e Observer Pattern. Isso resulta em um código mais organizado, testável e de fácil manutenção.

## Estrutura do Projeto

### Diagrama de Componentes

```
                   ┌─────────────────────┐
                   │   Python Reports    │
                   │      Service        │
                   └────────────┬────────┘
                                │
                                ▼
┌─────────────────┐     ┌───────────────────┐     ┌─────────────────┐
│   Controllers   │────▶│    Repositories   │────▶│      Models     │
└────────┬────────┘     └─────────┬─────────┘     └─────────────────┘
         │                        │                        ▲
         │                        │                        │
         │                        ▼                        │
         │              ┌─────────────────┐                │
         └─────────────▶│     Services    │────────────────┘
                        └─────────────────┘
                                 │
                                 ▼
                        ┌─────────────────┐
                        │   Elasticsearch │
                        └─────────────────┘
```

### Principais Componentes

1. **Controllers**: Gerencia as requisições HTTP e respostas
2. **Repositories**: Abstrai o acesso a dados e operações de persistência
3. **Services**: Implementa lógicas de negócio mais complexas
4. **Models**: Define as entidades e seus relacionamentos
5. **Observers**: Implementa reações a eventos do ciclo de vida das entidades
6. **Python Reports Service**: Serviço em Python para processamento de relatórios avançados

## Componentes e Responsabilidades

### Models (Entidades)

Os modelos representam as entidades principais do sistema e seus relacionamentos.

#### Vehicle (`app/Models/Vehicle.php`)
- **Propósito**: Representa um veículo disponível para aluguel
- **Atributos**:
  - `plate` (string): placa do veículo
  - `make` (string): fabricante
  - `model` (string): modelo
  - `daily_rate` (decimal): valor diário de aluguel
- **Relacionamentos**:
  - Tem muitos aluguéis (`rentals`)

#### Customer (`app/Models/Customer.php`)
- **Propósito**: Representa um cliente do sistema
- **Atributos**:
  - `name` (string): nome do cliente
  - `email` (string): email de contato
  - `phone` (string): telefone de contato
  - `cnh` (string): número da CNH do cliente
- **Relacionamentos**:
  - Tem muitos aluguéis (`rentals`)

#### Rental (`app/Models/Rental.php`)
- **Propósito**: Representa um aluguel de veículo
- **Atributos**:
  - `vehicle_id` (integer): ID do veículo alugado
  - `customer_id` (integer): ID do cliente
  - `start_date` (date): data de início do aluguel
  - `end_date` (date): data de fim do aluguel
  - `total_amount` (decimal): valor total calculado pelo sistema
- **Relacionamentos**:
  - Pertence a um veículo (`vehicle`)
  - Pertence a um cliente (`customer`)

### Repositories

Os repositórios encapsulam a lógica de acesso a dados, isolando os controllers das operações de persistência.

#### Repository Interface (`app/Repositories/RepositoryInterface.php`)
- **Propósito**: Define um contrato comum para todos os repositórios
- **Métodos**:
  - `getAll()`: Retorna todos os registros
  - `getAllPaginated()`: Retorna registros com paginação
  - `findById()`: Busca um registro por ID
  - `create()`: Cria um novo registro
  - `update()`: Atualiza um registro existente
  - `delete()`: Remove um registro

#### Vehicle Repository (`app/Repositories/VehicleRepository.php`)
- **Propósito**: Implementa operações de persistência para veículos
- **Funcionalidades**:
  - Implementa todos os métodos da interface
  - Adiciona método `searchWithElasticsearch()` para busca avançada
  - Integra com o serviço de Elasticsearch

#### Customer Repository (`app/Repositories/CustomerRepository.php`)
- **Propósito**: Implementa operações de persistência para clientes
- **Funcionalidades**:
  - Implementa todos os métodos da interface
  - Fornece busca básica de clientes

### Services

#### Elasticsearch Service (`app/Services/ElasticsearchService.php`)
- **Propósito**: Encapsula a integração com o Elasticsearch
- **Funcionalidades**:
  - Inicializa o cliente Elasticsearch com configurações de ambiente
  - Gerencia índices e documentos (criar, atualizar, excluir)
  - Implementa buscas avançadas com suporte a relevância
  - Trabalha com múltiplos índices para diferentes entidades
  - Lida com casos de falha de conexão criando um cliente mock para ambiente local

#### Report Service (`app/Services/ReportService.php`)
- **Propósito**: Encapsula a comunicação com o serviço Python de relatórios
- **Funcionalidades**:
  - Fornece métodos para requisitar relatórios do serviço Python
  - Gerencia a comunicação HTTP entre Laravel e o serviço Python
  - Implementa verificação de saúde do serviço de relatórios
  - Formata e processa os dados recebidos do serviço Python

### Controllers

Os controllers gerenciam as requisições HTTP, delegando a lógica de negócio para os repositórios.

#### Vehicle Controller (`app/Http/Controllers/VehicleController.php`)
- **Propósito**: Gerencia operações CRUD e busca para veículos
- **Endpoints**:
  - `GET /api/vehicles`: Lista veículos com paginação
  - `GET /api/vehicles/search?q=termo`: Busca veículos com Elasticsearch
  - `GET /api/vehicles/{id}`: Retorna detalhes de um veículo
  - `POST /api/vehicles`: Cria novo veículo
  - `PUT /api/vehicles/{id}`: Atualiza um veículo
  - `DELETE /api/vehicles/{id}`: Remove um veículo

#### Customer Controller (`app/Http/Controllers/CustomerController.php`)
- **Propósito**: Gerencia operações CRUD para clientes
- **Endpoints**:
  - `GET /api/customers`: Lista clientes com paginação
  - `GET /api/customers/{id}`: Retorna detalhes de um cliente
  - `POST /api/customers`: Cria novo cliente
  - `PUT /api/customers/{id}`: Atualiza um cliente
  - `DELETE /api/customers/{id}`: Remove um cliente

#### Rental Controller (`app/Http/Controllers/RentalController.php`)
- **Propósito**: Gerencia operações de aluguel de veículos
- **Endpoints**:
  - `GET /api/rentals`: Lista aluguéis
  - `GET /api/rentals/{id}`: Retorna detalhes de um aluguel
  - `POST /api/rentals`: Cria nova reserva
  - `POST /api/rentals/{id}/start`: Inicia um aluguel
  - `POST /api/rentals/{id}/end`: Finaliza um aluguel
  - `POST /api/rentals/{id}/cancel`: Cancela um aluguel

#### Auth Controller (`app/Http/Controllers/AuthController.php`)
- **Propósito**: Gerencia autenticação de usuários
- **Endpoints**:
  - `POST /api/register`: Registra novo usuário
  - `POST /api/login`: Autentica e gera token JWT
  - `POST /api/logout`: Invalida token JWT

#### Report Controller (`app/Http/Controllers/ReportController.php`)
- **Propósito**: Gerencia os endpoints de relatórios, fazendo a ponte com o serviço Python
- **Endpoints**:
  - `GET /api/reports/revenue`: Retorna relatório de receita por veículo em um período

### Observers

Os observers reagem a eventos do ciclo de vida das entidades, automatizando tarefas como indexação no Elasticsearch.

#### Vehicle Observer (`app/Observers/VehicleObserver.php`)
- **Propósito**: Reage a eventos do ciclo de vida de veículos
- **Funcionalidades**:
  - `created()`: Indexa um novo veículo no Elasticsearch
  - `updated()`: Atualiza o documento no Elasticsearch
  - `deleted()`: Remove o documento do Elasticsearch

#### Customer Observer (`app/Observers/CustomerObserver.php`)
- **Propósito**: Reage a eventos do ciclo de vida de clientes
- **Funcionalidades**: Similar ao VehicleObserver

### Service Providers

#### Repository Service Provider (`app/Providers/RepositoryServiceProvider.php`)
- **Propósito**: Registra os repositórios no container de injeção de dependências
- **Funcionalidades**:
  - Registra os bindings para os repositórios concretos

### Serviço Python de Relatórios

O serviço Python fornece processamento analítico e geração de relatórios para o sistema principal em Laravel.

#### Principais Componentes do Serviço Python

#### FastAPI App (`python-reports/app/main.py`)
- **Propósito**: Implementa uma API REST para relatórios usando FastAPI
- **Endpoints**:
  - `GET /reports/revenue`: Retorna relatório de receita por veículo em um período
  - `GET /`: Endpoint de verificação de saúde para monitoramento

#### SQLAlchemy Integration
- **Propósito**: Facilita a conexão e consultas ao banco de dados
- **Funcionalidades**:
  - Conecta ao mesmo banco de dados do Laravel (MySQL ou PostgreSQL)
  - Executa consultas SQL otimizadas para aggregações e análises

## Fluxos de Trabalho Principais

### Fluxo de Busca Avançada com Elasticsearch

1. O usuário envia uma requisição para `/api/vehicles?search=termo` ou `/api/vehicles/search?q=termo`
2. O `VehicleController` recebe a requisição e extrai o termo de busca
3. O controller chama o método `searchWithElasticsearch()` do `VehicleRepository`
4. O repositório utiliza o `ElasticsearchService` para realizar a busca no índice de veículos
5. O Elasticsearch processa a busca aplicando relevância e critérios definidos
6. Os resultados são devolvidos ao controller que formata a resposta JSON
7. A resposta é enviada ao cliente com os resultados paginados

### Fluxo de Criação/Atualização/Remoção de Veículo com Sincronização Elasticsearch

1. O usuário envia uma requisição para criar, atualizar ou remover um veículo
2. O `VehicleController` processa a requisição e chama o método correspondente do `VehicleRepository`
3. O repositório realiza a operação no banco de dados
4. O `VehicleObserver` é acionado automaticamente pelo evento do Eloquent
5. O observer chama o `ElasticsearchService` para sincronizar os dados com o Elasticsearch
6. O serviço executa a operação correspondente no Elasticsearch (indexar, atualizar ou remover)
7. O controller retorna a resposta ao cliente

### Fluxo de Aluguel de Veículo

1. **Reserva**: Cliente reserva um veículo via `POST /api/rentals`
   - O sistema verifica a disponibilidade do veículo
   - Cria um registro de aluguel com status "reserved"
   
2. **Início do Aluguel**: Via `POST /api/rentals/{id}/start`
   - O sistema registra a data de início
   - Muda o status para "in_progress"
   - Marca o veículo como indisponível

3. **Fim do Aluguel**: Via `POST /api/rentals/{id}/end`
   - O sistema registra a data de fim
   - Calcula o valor total com base na tarifa diária e duração
   - Muda o status para "completed"
   - Marca o veículo como disponível novamente

### Fluxo de Geração de Relatório de Receita

1. O usuário envia uma requisição para `/api/reports/revenue?start=YYYY-MM-DD&end=YYYY-MM-DD`
2. O `ReportController` do Laravel recebe a requisição e valida os parâmetros de data
3. O controller usa o `ReportService` para fazer uma requisição HTTP ao serviço Python
4. O serviço Python processa a requisição:
   - Conecta ao banco de dados
   - Executa a consulta SQL para agregação de dados
   - Formata o resultado como JSON
   - Retorna ao Laravel
5. O Laravel recebe os dados, adiciona metadados adicionais
6. O controller envia a resposta final ao cliente com os dados do relatório

## Configuração do Elasticsearch

O sistema utiliza o Elasticsearch para fornecer buscas avançadas e eficientes. A integração é configurada através das seguintes variáveis de ambiente:

```
ELASTICSEARCH_ENABLED=true
ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200
ELASTICSEARCH_SCHEME=http
ELASTICSEARCH_USER=
ELASTICSEARCH_PASS=
```

### Índices do Elasticsearch

O sistema mantém os seguintes índices:

1. **vehicles**: Armazena documentos de veículos para busca rápida
   - Campos indexados: plate, make, model
   - Configurado com análise de texto e relevância

2. **customers**: Armazena documentos de clientes
   - Campos indexados: name, email, phone, cnh

## Configuração do Serviço Python de Relatórios

O serviço Python de relatórios é configurado através das seguintes variáveis de ambiente:

```
# Configurações de banco de dados
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=locadora
DB_USERNAME=root
DB_PASSWORD=secret

# Configuração do serviço
PORT=8000
ALLOW_ORIGINS=http://localhost,http://localhost:8000,http://laravel.test
```

A comunicação entre o Laravel e o serviço Python é configurada no Laravel através da variável:

```
REPORTS_SERVICE_URL=http://python-reports:8000
```

## Benefícios da Arquitetura

### Separação de Responsabilidades
Cada componente tem uma função específica, tornando o código mais organizado e fácil de entender.

### Testabilidade
A injeção de dependências facilita a criação de testes unitários com mocks.

### Manutenibilidade
Alterações em uma camada não afetam diretamente as outras, facilitando manutenção.

### Escalabilidade
Novos recursos podem ser adicionados sem modificar o código existente.

### Performance
- A integração com Elasticsearch oferece buscas rápidas mesmo com grandes volumes de dados.
- O serviço Python permite processamento analítico otimizado sem impactar o desempenho da API principal.

### Flexibilidade Tecnológica
A arquitetura de microsserviços permite escolher a tecnologia mais apropriada para cada função:
- Laravel para a API principal e gerenciamento de dados
- Elasticsearch para busca avançada
- Python/FastAPI para análise de dados e relatórios

## Conclusão

Este sistema implementa uma arquitetura robusta e escalável para gerenciamento de aluguel de veículos, aproveitando ao máximo os recursos do Laravel, Elasticsearch e Python. A organização em camadas com responsabilidades bem definidas facilita a manutenção e evolução do sistema ao longo do tempo. 