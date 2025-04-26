# API de Aluguel de Veículos - Documentação

## Configuração do Postman

1. Importe a coleção `vehicle-rental-api.postman_collection.json`
2. Crie um ambiente com a variável `base_url`: `http://localhost:8000`

## Fluxo de Teste

### 1. Autenticação
- Use `Registrar Usuário` para criar uma conta
- Use `Login` para obter um token JWT (salvo automaticamente)

### 2. Cadastro Base
- Crie um veículo: `Veículos > Criar Veículo`
- Crie um cliente: `Clientes > Criar Cliente`

### 3. Fluxo de Aluguel

#### Cenário 1: Ciclo Completo
1. **Reserva** - Crie uma reserva com IDs do veículo e cliente
   - Status inicial: `reserved`

2. **Início** - Inicie o aluguel (define `start_date`)
   - Status: `in_progress`
   - Veículo fica indisponível

3. **Término** - Encerre o aluguel (define `end_date`)
   - Calcula valor com base na diária e duração
   - Status: `completed`
   - Veículo fica disponível novamente

#### Cenário 2: Cancelamento
1. Crie uma reserva
2. Cancele a reserva (apenas possível no status `reserved`)
   - Status: `cancelled`

## Regras de Negócio

- **Cálculo do Valor:**
  - Diária mínima: 1 dia (mesmo para aluguéis <24h)
  - Para aluguéis >24h: dias arredondados para cima

- **Filtros na Listagem:**
  - Status: `reserved`, `in_progress`, `completed`, `cancelled`
  - Ex: `/api/rentals?status=in_progress` 