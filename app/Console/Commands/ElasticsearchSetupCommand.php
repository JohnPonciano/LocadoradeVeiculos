<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ElasticsearchSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:setup {--force : Força a reindexação mesmo se o índice já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura os índices do Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando configuração dos índices do Elasticsearch...');
        
        $force = $this->option('force') ? '--force' : '';
        
        // Chamar o comando elastic:index-all com a opção sync para criar os índices
        $this->call('elastic:index-all', [
            '--sync' => true,
            '--force' => $this->option('force'),
        ]);
        
        $this->info('Configuração dos índices do Elasticsearch concluída com sucesso!');
        
        return Command::SUCCESS;
    }
} 