# API de Aluguel de Veículos

Uma API RESTful para gerenciamento de aluguel de veículos, construída com Laravel e integrada com Elasticsearch para buscas avançadas.

## Funcionalidades

- **Autenticação de usuários** com JWT (JSON Web Tokens)
- **Gerenciamento de veículos** - CRUD completo e busca avançada com Elasticsearch
- **Gerenciamento de clientes** - CRUD completo e busca
- **Gestão de aluguéis** - Reserva, início, finalização e cálculo automático de valores
- **Elasticsearch para busca avançada** - Busca rápida e eficiente com suporte a múltiplos campos e relevância
- **Documentação completa** com coleção Postman

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- Elasticsearch 7.x+
- Docker e Docker Compose
- Python 3.8+ (para o serviço de relatórios)

## Instalação

### Usando Docker (recomendado)

1. Clone o repositório:
   ```bash
   git clone https://github.com/JohnPonciano/LocadoradeVeiculos.git
   cd LocadoradeVeiculos
   ```

2. Configure o arquivo .env:
   ```bash
   cp .env.example .env
   ```

3. Configure as variáveis de ambiente no .env:
   ```
   # Configurações do Laravel
   APP_NAME="Vehicle Rental API"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost

   # Configurações do banco de dados
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=locadora
   DB_USERNAME=root
   DB_PASSWORD=secret

   # Configurações do Elasticsearch
   ELASTICSEARCH_ENABLED=true
   ELASTICSEARCH_HOST=elasticsearch
   ELASTICSEARCH_PORT=9200
   ELASTICSEARCH_SCHEME=http
   ELASTICSEARCH_USER=
   ELASTICSEARCH_PASS=

   # Configurações do serviço Python de relatórios
   REPORTS_SERVICE_URL=http://python-reports:8000
   ```

4. Construa e inicie os containers:
   ```bash
   docker-compose build --no-cache
   docker-compose up -d
   ```

5. Instale as dependências e configure o projeto:
   ```bash
   docker-compose exec laravel.test composer install
   docker-compose exec laravel.test php artisan key:generate
   docker-compose exec laravel.test php artisan migrate --seed
   docker-compose exec laravel.test php artisan jwt:secret
   ```

O script de inicialização (`docker/start.sh`) executará automaticamente:
- Verificação de disponibilidade dos serviços (MySQL, Elasticsearch, Python Reports)
- Execução das migrations do Laravel
- Criação e configuração dos índices do Elasticsearch
- Indexação dos dados existentes
- Inicialização do worker para processamento assíncrono das filas
- Seed de dados iniciais

Pronto! Acesse a API em http://localhost

### Instalação Manual (sem Docker)

1. Clone o repositório:
   ```bash
   git clone https://github.com/JohnPonciano/LocadoradeVeiculos.git
   cd LocadoradeVeiculos
   ```

2. Instale as dependências do PHP:
   ```bash
   composer install
   ```

3. Configure o arquivo .env:
   ```bash
   cp .env.example .env
   ```

4. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```

5. Configure o banco de dados no arquivo .env:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=locadora
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
   ```

6. Configure o Elasticsearch no arquivo .env:
   ```
   ELASTICSEARCH_ENABLED=true
   ELASTICSEARCH_HOST=localhost
   ELASTICSEARCH_PORT=9200
   ELASTICSEARCH_SCHEME=http
   ```

7. Execute as migrations e seeders:
   ```bash
   php artisan migrate --seed
   ```

8. Gere a chave JWT:
   ```bash
   php artisan jwt:secret
   ```

9. Crie os índices do Elasticsearch:
   ```bash
   php artisan elastic:create-indices
   ```

10. Indexe os dados existentes:
    ```bash
    php artisan elastic:index-all
    ```

11. Inicie o servidor:
    ```bash
    php artisan serve
    ```

12. Inicie o worker para processar as filas:
    ```bash
    php artisan queue:work --queue=elasticsearch
    ```

## Configuração do Elasticsearch

### Usando Docker

O Elasticsearch já está configurado no docker-compose.yml e é inicializado automaticamente pelo script `docker/start.sh`. Este script:

1. Verifica a disponibilidade do Elasticsearch
2. Verifica a versão e plugins instalados
3. Verifica os índices existentes
4. Cria/atualiza os índices necessários
5. Indexa os dados existentes
6. Inicia o worker para processamento assíncrono

Para verificar se está funcionando:

```bash
docker-compose ps
```

Para verificar os logs do Elasticsearch:

```bash
docker-compose logs elasticsearch
```

Para verificar os logs do script de inicialização:

```bash
docker-compose logs laravel.test
```

### Instalação Manual do Elasticsearch

1. Instale o Elasticsearch seguindo a [documentação oficial](https://www.elastic.co/guide/en/elasticsearch/reference/current/install-elasticsearch.html)

2. Configure o Elasticsearch para aceitar conexões externas (se necessário):
   Edite o arquivo `config/elasticsearch.yml`:
   ```
   network.host: 0.0.0.0
   discovery.type: single-node
   ```

3. Inicie o serviço:
   ```bash
   sudo systemctl start elasticsearch
   sudo systemctl enable elasticsearch
   ```

4. Verifique se o serviço está rodando:
   ```bash
   curl http://localhost:9200
   ```

## Executando o Serviço Python de Relatórios

### Usando Docker

O serviço Python já está configurado no docker-compose.yml e é inicializado automaticamente pelo script `docker/start.sh`. Este script verifica a disponibilidade do serviço antes de prosseguir com a inicialização do Laravel.

Para verificar se está funcionando:

```bash
docker-compose ps
```

Para verificar os logs do serviço Python:

```bash
docker-compose logs python-reports
```

### Instalação Manual do Serviço Python

1. Navegue até o diretório do serviço Python:
   ```bash
   cd python-reports
   ```

2. Crie um ambiente virtual:
   ```bash
   python -m venv venv
   source venv/bin/activate  # No Windows: venv\Scripts\activate
   ```

3. Instale as dependências:
   ```bash
   pip install -r requirements.txt
   ```

4. Configure as variáveis de ambiente:
   ```bash
   export DB_CONNECTION=mysql
   export DB_HOST=localhost
   export DB_PORT=3306
   export DB_DATABASE=locadora
   export DB_USERNAME=seu_usuario
   export DB_PASSWORD=sua_senha
   export PORT=8000
   ```

5. Inicie o serviço:
   ```bash
   uvicorn app.main:app --host 0.0.0.0 --port 8000
   ```

## Estrutura da API

### Autenticação

- `POST /api/register` - Registrar novo usuário
- `POST /api/login` - Fazer login e obter token JWT
- `POST /api/logout` - Invalidar token JWT

### Veículos

- `GET /api/vehicles` - Listar todos os veículos (com paginação)
- `GET /api/vehicles/search?q=termo` - Pesquisar veículos usando Elasticsearch
- `GET /api/vehicles/{id}` - Obter detalhes de um veículo
- `POST /api/vehicles` - Criar novo veículo
- `PUT /api/vehicles/{id}` - Atualizar veículo existente
- `DELETE /api/vehicles/{id}` - Excluir veículo

### Clientes

- `GET /api/customers` - Listar todos os clientes (com paginação)
- `GET /api/customers?search=termo` - Pesquisar clientes
- `GET /api/customers/{id}` - Obter detalhes de um cliente
- `POST /api/customers` - Criar novo cliente
- `PUT /api/customers/{id}` - Atualizar cliente existente
- `DELETE /api/customers/{id}` - Excluir cliente

### Aluguéis

- `GET /api/rentals` - Listar todos os aluguéis
- `GET /api/rentals/{id}` - Obter detalhes de um aluguel
- `POST /api/rentals` - Criar uma nova reserva
- `POST /api/rentals/{id}/start` - Iniciar um aluguel
- `POST /api/rentals/{id}/end` - Finalizar um aluguel

## Busca Avançada com Elasticsearch

A API utiliza Elasticsearch para proporcionar busca avançada de veículos com os seguintes recursos:

- **Busca em múltiplos campos** - Pesquisa marca, modelo, placa e outros campos simultaneamente
- **Pesos de relevância** - Resultados onde o termo aparece na placa têm prioridade (plate^3)
- **Fuzzy matching** - Encontra resultados mesmo com pequenos erros de digitação
- **Highlights** - Destaca os termos encontrados nos resultados
- **Sincronização automática** - Todos os registros são automaticamente indexados no Elasticsearch pelos observers

Para usar a busca avançada, utilize o endpoint dedicado:

```
GET /api/vehicles/search?q=termo
```

Exemplo de resposta:
```json
{
    "data": [
        {
            "id": 1,
            "plate": "ABC1234",
            "make": "Toyota",
            "model": "Corolla",
            "daily_rate": 100.00,
            "available": true
        }
    ],
    "meta": {
        "total": 1,
        "per_page": 10,
        "current_page": 1,
        "last_page": 1,
        "search_term": "toyota"
    },
    "highlights": {
        "1": {
            "make": ["<em>Toyota</em>"],
            "model": ["<em>Corolla</em>"]
        }
    }
}
```

## Testes com Postman

Foi fornecida uma coleção completa do Postman para testar todos os endpoints da API. Veja mais detalhes em [postman/README.md](postman/README.md).

### Passos rápidos:

1. Importe a coleção do Postman de `postman/vehicle-rental-api.postman_collection.json`
2. Configure um ambiente com a variável `base_url` (geralmente `http://localhost`)
3. Registre um usuário e faça login para obter o token JWT
4. Explore os endpoints organizados em pastas na coleção

## Fluxo de Trabalho do Aluguel

1. Registre-se e faça login
2. Cadastre veículos e clientes
3. Crie uma reserva de aluguel (status: `reserved`)
4. Inicie o aluguel (status: `in_progress`, registra `start_date`)
5. Finalize o aluguel (status: `completed`, registra `end_date` e calcula `total_amount`)

## Solução de Problemas

### Erro 404 ao acessar /api/register

Se você estiver recebendo um erro 404 ao acessar a rota `/api/register`, verifique:

1. **Se o JWT está corretamente configurado**:
   ```bash
   # Execute dentro do container
   php artisan jwt:secret
   ```

2. **Se o provider JWT está registrado**:
   Certifique-se que `Tymon\JWTAuth\Providers\LaravelServiceProvider::class` está no array `providers` em `config/app.php`

3. **Se sua URL está correta**:
   Tente acessar `http://localhost/api/register` (com prefixo /api/)

### Problemas com Elasticsearch

Se você encontrar problemas relacionados ao Elasticsearch:

1. **Verifique se o Elasticsearch está rodando**:
   ```bash
   curl http://localhost:9200
   ```

2. **Verifique a conexão a partir do container**:
   ```bash
   docker-compose exec laravel.test curl elasticsearch:9200
   ```

3. **Verifique os logs do script de inicialização**:
   ```bash
   docker-compose logs laravel.test
   ```

4. **Verifique os logs do Elasticsearch**:
   ```bash
   docker-compose logs elasticsearch
   ```

5. **Desative temporariamente o Elasticsearch** (para depuração):
   Edite o arquivo .env e defina `ELASTICSEARCH_ENABLED=false`

### Problemas com o Serviço Python

Se você encontrar problemas com o serviço Python de relatórios:

1. **Verifique se o serviço está rodando**:
   ```bash
   curl http://localhost:8000
   ```

2. **Verifique os logs do serviço**:
   ```bash
   docker-compose logs python-reports
   ```

3. **Verifique a conexão com o banco de dados**:
   ```bash
   docker-compose exec python-reports python -c "from app.database import engine; from sqlalchemy import text; with engine.connect() as conn: result = conn.execute(text('SELECT 1')); print(result.fetchone())"
   ```

Veja o arquivo [workflow.md](workflow.md) para mais detalhes sobre como foi feita algumas soluções e como o fluxo de trabalho foi projetado.


