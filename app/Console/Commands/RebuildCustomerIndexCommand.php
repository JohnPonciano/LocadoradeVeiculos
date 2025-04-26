<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class RebuildCustomerIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:rebuild-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recria o índice de clientes no Elasticsearch com o mapeamento atualizado';

    /**
     * Execute the console command.
     */
    public function handle(ElasticsearchService $elasticsearch)
    {
        $this->info('Iniciando reconstrução do índice de clientes...');

        // 1. Obter o mapeamento da configuração
        $mappings = config('elasticsearch.indices.customers.mappings', []);
        
        if (empty($mappings)) {
            $this->error('Mapeamento não encontrado na configuração!');
            return Command::FAILURE;
        }
        
        $this->info('Mapeamento encontrado: ' . json_encode($mappings));

        // 2. Excluir o índice existente
        if ($elasticsearch->indexExists('customers')) {
            $this->info('Excluindo índice existente...');
            $elasticsearch->deleteIndex('customers');
        }

        // 3. Criar o índice com o novo mapeamento
        $this->info('Criando índice com novo mapeamento...');
        $elasticsearch->createIndex('customers', $mappings);

        // 4. Reindexar todos os clientes
        $customers = Customer::all();
        $count = $customers->count();
        
        if ($count === 0) {
            $this->info('Nenhum cliente encontrado para indexar.');
            return Command::SUCCESS;
        }
        
        $this->info("Reindexando {$count} clientes...");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($customers as $customer) {
            $elasticsearch->indexDocument('customers', $customer->id, $customer->toArray());
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('Verificando índice reconstruído...');
        $stats = $elasticsearch->getClient()->indices()->stats(['index' => 'customers']);
        
        $this->info("Índice reconstruído com sucesso. Documentos indexados: " . 
                    ($stats['_all']['primaries']['docs']['count'] ?? 0));
        
        return Command::SUCCESS;
    }
} 