# Serviço Python de Relatórios

Este é um microserviço Python baseado em FastAPI que fornece relatórios analíticos para o sistema de aluguel de veículos, processando dados do banco de dados compartilhado.

## Funcionalidades

- **Relatório de Receita por Veículo**: Agrega e calcula a receita total e quantidade de aluguéis por veículo em um período específico
- **Conexão com MySQL/PostgreSQL**: Conecta diretamente ao mesmo banco de dados usado pelo Laravel
- **API RESTful**: Expõe relatórios através de endpoints HTTP simples
- **Integração com Docker**: Facilmente implantável via Docker e Docker Compose

## Requisitos

- Python 3.8+
- FastAPI
- SQLAlchemy
- MySQL ou PostgreSQL

## Estrutura do Projeto

```
python-reports/
├── app/
│   └── main.py          # Aplicativo principal FastAPI
├── .env.example         # Exemplo de variáveis de ambiente
├── Dockerfile           # Configuração Docker
├── README.md            # Esta documentação
├── requirements.txt     # Dependências Python
└── run.py               # Script para iniciar o serviço
```

## Instalação e Configuração

### Usando Docker (recomendado)

1. Copie o arquivo de exemplo `.env.example` para `.env`:
   ```bash
   cp .env.example .env
   ```

2. Configure o arquivo `.env` com os detalhes do seu banco de dados:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=locadora
   DB_USERNAME=root
   DB_PASSWORD=secret
   ```

3. Execute o serviço via Docker Compose:
   ```bash
   docker-compose up -d python-reports
   ```

### Instalação Manual

1. Crie um ambiente virtual Python:
   ```bash
   python -m venv venv
   source venv/bin/activate  # Linux/Mac
   # ou
   venv\Scripts\activate     # Windows
   ```

2. Instale as dependências:
   ```bash
   pip install -r requirements.txt
   ```

3. Configure as variáveis de ambiente ou crie um arquivo `.env`

4. Execute o serviço:
   ```bash
   python run.py
   ```

## Endpoints da API

### Verificação de Saúde
- **URL**: `/`
- **Método**: `GET`
- **Resposta de Sucesso**: 
  ```json
  {
    "status": "ok", 
    "message": "Vehicle Rental API Reports Service"
  }
  ```

### Relatório de Receita por Veículo
- **URL**: `/reports/revenue`
- **Método**: `GET`
- **Parâmetros**:
  - `start` - Data inicial (YYYY-MM-DD)
  - `end` - Data final (YYYY-MM-DD)
- **Resposta de Sucesso**:
  ```json
  [
    {
      "plate": "ABC1234",
      "make": "Honda",
      "model": "Civic",
      "total_rentals": 5,
      "total_revenue": 1500.00
    },
    // ... mais veículos
  ]
  ```

## Integração com Laravel

O serviço Laravel principal inclui:

1. Um serviço (`ReportService`) para comunicação com este serviço Python
2. Um controller (`ReportController`) que expõe os relatórios através da API Laravel
3. Rotas protegidas por autenticação JWT para acessar os relatórios

Para acessar o relatório através do Laravel, utilize:
```
GET /api/reports/revenue?start=YYYY-MM-DD&end=YYYY-MM-DD
```

## Desenvolvimento

### Adicionando Novos Relatórios

Para criar um novo tipo de relatório:

1. Adicione um novo endpoint no arquivo `app/main.py`
2. Implemente a lógica SQL para o relatório
3. Adicione um método correspondente ao `ReportService` no Laravel
4. Crie um endpoint no `ReportController` do Laravel

## Solução de Problemas

### Erros de Conexão com o Banco de Dados

Verifique:
- Se as credenciais do banco de dados estão corretas no arquivo `.env`
- Se o serviço do banco de dados está acessível na rede
- Se o usuário do banco tem permissões para executar consultas complexas

### Serviço Indisponível

Verifique:
- Os logs do container Docker com `docker logs python-reports`
- Se o serviço está em execução: `docker ps | grep python-reports`
- Tente reiniciar o serviço: `docker-compose restart python-reports` 