#!/bin/bash
set -e

# Verificar se o comando ps está instalado, caso contrário, instalá-lo
# Ele serve para verificar se o worker está executando corretamente e para rodar ele em segundo plano
if ! command -v ps >/dev/null 2>&1; then
    echo "📦 Instalando ps (procps)..."
    apt-get update && apt-get install -y procps
fi

# Função para verificar disponibilidade com timeout
check_service() {
    local service=$1
    local max_attempts=$2
    local check_cmd=$3
    local attempt=1

    echo "🔄 Esperando o $service ficar disponível..."
    
    while ! eval $check_cmd; do
        if [ $attempt -ge $max_attempts ]; then
            echo "❌ Tempo limite excedido esperando por $service. Saindo..."
            exit 1
        fi
        
        echo "Aguardando conexão com o $service... (tentativa $attempt/$max_attempts)"
        sleep 3
        ((attempt++))
    done
    
    echo "✅ $service está disponível!"
}

# Verificar MySQL com timeout de 30 tentativas (90 segundos)
check_service "MySQL" 30 "mysqladmin ping -h\"$DB_HOST\" -u\"$DB_USERNAME\" -p\"$DB_PASSWORD\" --silent"

# Verificar Elasticsearch com timeout de 30 tentativas (90 segundos)
check_service "Elasticsearch" 30 "curl -s \"$ELASTICSEARCH_SCHEME://$ELASTICSEARCH_HOST:$ELASTICSEARCH_PORT/_cluster/health\" | grep -q '\"status\":\"\\(green\\|yellow\\)\"'"

# Verificar Python Reports Service com timeout de 30 tentativas (90 segundos)
check_service "Python Reports Service" 30 "curl -s http://python-reports:3000 | grep -q '{\"status\":\"ok\",\"message\":\"Vehicle Rental API Reports Service\"}'"

echo "🔄 Esperando 5 segundos para garantir que todos os serviços estão online..."
sleep 5    

# Verificar se o Elasticsearch está totalmente operacional
echo "🔄 Verificando configuração do Elasticsearch..."
ES_URL="$ELASTICSEARCH_SCHEME://$ELASTICSEARCH_HOST:$ELASTICSEARCH_PORT"

# Verificar versão do Elasticsearch
ES_VERSION=$(curl -s "$ES_URL" | grep -o '"version":{"number":"[^"]*"' | cut -d'"' -f6)
echo "📊 Versão do Elasticsearch: $ES_VERSION"

# Verificar plugins instalados
echo "📋 Plugins do Elasticsearch:"
curl -s "$ES_URL/_cat/plugins?v" | sed 's/^/  /'

# Verificar índices existentes
echo "📋 Índices existentes:"
curl -s "$ES_URL/_cat/indices?v" | sed 's/^/  /'

# Verificar espaço em disco
echo "📊 Uso de disco no Elasticsearch:"
curl -s "$ES_URL/_cat/allocation?v" | sed 's/^/  /'

echo "🔄 Executando migrations do Laravel..."
php artisan migrate --force

# Verificar se existe a tabela de jobs
echo "🔄 Verificando tabela de jobs..."
if ! php artisan migrate:status | grep -q "jobs"; then
    echo "Criando tabela de jobs..."
    php artisan queue:table
    php artisan migrate --force
fi

echo "🔄 Criando/atualizando índices do Elasticsearch..."
# Verificar se o comando existe antes de executá-lo
if php artisan list | grep -q "elastic:setup"; then
    # Usar o novo comando elastic:setup que criamos
    php artisan elastic:setup --force --no-interaction
    echo "✅ Índices do Elasticsearch configurados com sucesso!"
else
    # Fallback se por algum motivo o comando não estiver disponível
    echo "Comando elastic:setup não encontrado, usando elastic:index-all em vez disso."
    # Usar --no-interaction para evitar qualquer prompt
    php artisan elastic:index-all --force --sync --no-interaction
    echo "✅ Índices do Elasticsearch configurados com sucesso!"
fi

# Verificar se os índices foram criados corretamente
echo "🔄 Verificando índices após configuração:"
curl -s "$ES_URL/_cat/indices?v" | grep -E 'vehicles|customers' | sed 's/^/  /'

echo "🔄 Iniciando fila de jobs do Elasticsearch em segundo plano..."
mkdir -p /var/log/laravel
php artisan queue:work --tries=3 --delay=5 --sleep=3 --queue=elasticsearch,default > /var/log/laravel/queue-worker.log 2>&1 &
QUEUE_PID=$!
mkdir -p /var/run
echo $QUEUE_PID > /var/run/queue.pid
echo "✅ Queue worker iniciado em segundo plano (PID: $QUEUE_PID)!"

# Criar arquivo de status para healthcheck
mkdir -p /tmp/health

# Verificar se o worker está executando corretamente
sleep 2
if ! ps -p $QUEUE_PID > /dev/null 2>&1; then
    echo "⚠️ Aviso: O worker de queue parece não estar rodando. Verifique os logs!"
    cat /var/log/laravel/queue-worker.log | tail -20
    # Marcar como não saudável, mas continuar o script
    touch /tmp/health/worker_issue
else
    echo "✅ Queue worker verificado e funcionando!"
    # Marcar como saudável
    rm -f /tmp/health/worker_issue
fi

echo "🔄 Executando seed de dados..."
# Executar sempre o comando seed-and-index
if php artisan app:seed-and-index --fresh --force; then
    echo "✅ Seed e indexação concluídos com sucesso!"
    touch /tmp/health/app_ready
else
    echo "⚠️ Erro durante o seed ou indexação, mas continuando inicialização..."
    # Mesmo com erro, marcar como pronto para evitar falha de healthcheck
    touch /tmp/health/app_ready
fi

# Trap para encerrar o worker quando o container for desligado
trap "echo '🛑 Encerrando queue worker...'; kill $QUEUE_PID 2>/dev/null || true; rm -f /tmp/health/app_ready" SIGTERM SIGINT

# Atualizar arquivo para healthcheck com status detalhado
echo "Aplicação inicializada com sucesso em $(date)" > /tmp/health/app_ready
echo "PID do worker: $QUEUE_PID" >> /tmp/health/app_ready
echo "Versão do Elasticsearch: $ES_VERSION" >> /tmp/health/app_ready

echo "✅ Inicialização concluída! Iniciando servidor..."

# Inicia o servidor em primeiro plano
php artisan serve --host=0.0.0.0 --port=80