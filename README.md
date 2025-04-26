
# API de Aluguel de Veículos

Uma API RESTful para gerenciamento de aluguel de veículos, construída com Laravel.

## Funcionalidades

- **Autenticação de usuários** com JWT (JSON Web Tokens)
- **Gerenciamento de veículos** - CRUD completo e busca
- **Gerenciamento de clientes** - CRUD completo e busca
- **Gestão de aluguéis** - Reserva, início, finalização e cálculo automático de valores
- **Documentação completa** com coleção Postman

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- Docker e Docker Compose (recomendado para uso com Laravel Sail)

## Instalação

### Usando Laravel Sail (recomendado)

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/vehicle-rental-api.git
   cd vehicle-rental-api
   ```

2. Instale as dependências do Composer:
   ```bash
   docker run --rm -v $(pwd):/app composer install
   ```

3. Configure o arquivo .env:
   ```bash
   cp .env.example .env
   ```

4. Inicie os containers:
   ```bash
   ./vendor/bin/sail up -d
   ```

5. Execute o script de configuração:
   ```bash
   ./vendor/bin/sail bash setup.sh
   ```

### Usando Container Docker (para usuários avançados)

Se você estiver usando um container Docker personalizado, verifique o arquivo [CONTAINER_GUIDE.md](CONTAINER_GUIDE.md) para instruções detalhadas sobre como configurar e solucionar problemas comuns.

## Estrutura da API

### Autenticação

- `POST /api/register` - Registrar novo usuário
- `POST /api/login` - Fazer login e obter token JWT
- `POST /api/logout` - Invalidar token JWT

### Veículos

- `GET /api/vehicles` - Listar todos os veículos (com paginação)
- `GET /api/vehicles?search=termo` - Pesquisar veículos
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

Veja o arquivo [CONTAINER_GUIDE.md](CONTAINER_GUIDE.md) para mais detalhes sobre solução de problemas.

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

