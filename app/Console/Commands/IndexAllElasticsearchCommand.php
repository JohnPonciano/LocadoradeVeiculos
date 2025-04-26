<?php

namespace App\Console\Commands;

use App\Jobs\IndexVehicleJob;
use App\Models\Vehicle;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class IndexAllElasticsearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:index-all 
                            {--sync : Indexa sincronamente em vez de usar filas} 
                            {--force : Força a reindexação mesmo se o índice já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexa todos os veículos existentes no Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle(ElasticsearchService $elasticsearch)
    {
        $this->info('Iniciando indexação de todos os veículos no Elasticsearch...');

        // Verifica se o índice existe
        $indexExists = $elasticsearch->indexExists('vehicles');
        
        if (!$indexExists) {
            $this->info('Índice de veículos não existe. Criando índice...');
            $mappings = config('elasticsearch.indices.vehicles.mappings', []);
            $elasticsearch->createIndex('vehicles', $mappings);
            $this->info('Índice criado com sucesso!');
            
            // Verificar se o índice de clientes existe também
            if (!$elasticsearch->indexExists('customers')) {
                $this->info('Índice de clientes não existe. Criando índice...');
                $customerMappings = config('elasticsearch.indices.customers.mappings', []);
                $elasticsearch->createIndex('customers', $customerMappings);
                $this->info('Índice de clientes criado com sucesso!');
            }
        } elseif ($this->option('force')) {
            $this->warn('Índice já existe, mas --force foi especificado. Recriando índice...');
            try {
                // Deletar o índice existente
                $elasticsearch->deleteIndex('vehicles');
                $this->info('Índice anterior removido.');
                
                // Recriar o índice
                $mappings = config('elasticsearch.indices.vehicles.mappings', []);
                $elasticsearch->createIndex('vehicles', $mappings);
                $this->info('Índice recriado com sucesso!');
                
                // Também recriar o índice de clientes se existir
                if ($elasticsearch->indexExists('customers')) {
                    $elasticsearch->deleteIndex('customers');
                    $customerMappings = config('elasticsearch.indices.customers.mappings', []);
                    $elasticsearch->createIndex('customers', $customerMappings);
                    $this->info('Índice de clientes recriado com sucesso!');
                }
            } catch (\Exception $e) {
                $this->error('Erro ao recriar índice: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->info('Índice de veículos já existe. Procedendo com a indexação...');
        }

        // Buscar todos os veículos
        $vehicles = Vehicle::all();
        $total = $vehicles->count();
        
        if ($total === 0) {
            $this->warn('Nenhum veículo encontrado para indexar.');
            return Command::SUCCESS;
        }
        
        $this->info("Encontrados {$total} veículos para indexar.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $useQueue = !$this->option('sync');
        $indexed = 0;
        $failed = 0;

        foreach ($vehicles as $vehicle) {
            try {
                if ($useQueue) {
                    // Usando a fila (assíncrono)
                    IndexVehicleJob::dispatch($vehicle)->onQueue('elasticsearch');
                } else {
                    // Indexando diretamente (síncrono)
                    $elasticsearch->indexDocument('vehicles', $vehicle->id, $vehicle->toArray());
                }
                $indexed++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Erro ao indexar veículo {$vehicle->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("Indexação " . ($useQueue ? "agendada" : "concluída") . "!");
        $this->info("Total de veículos: {$total}");
        $this->info("Veículos indexados/agendados: {$indexed}");
        
        if ($failed > 0) {
            $this->warn("Veículos com falha: {$failed}");
        }
        
        if ($useQueue) {
            $this->info("Os veículos serão indexados em segundo plano pelo queue worker.");
        }

        return Command::SUCCESS;
    }
} 