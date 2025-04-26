<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedAndIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-and-index {--fresh : Limpar o banco antes de executar o seed} {--force : Forçar recriação dos índices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa o seed do banco de dados e indexa os dados no Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando processo de seed e indexação');
        
        // 1. Executar o seed
        if ($this->option('fresh')) {
            $this->info('🗑️ Limpando o banco de dados e executando as migrações...');
            $this->call('migrate:fresh');
        }
        
        $this->info('🌱 Executando seeder...');
        $this->call('db:seed');
        
        // 2. Indexar no Elasticsearch
        $this->info('📊 Indexando dados no Elasticsearch...');
        $forceOption = $this->option('force') ? '--force' : '';
        $this->call('elastic:index-all', [
            '--sync' => true,
            '--force' => $this->option('force'),
        ]);
        
        $this->info('✅ Processo concluído! Banco de dados populado e indexado no Elasticsearch.');
        
        // 3. Mostrar exemplos de usuários
        $this->info('');
        $this->info('👤 Usuários disponíveis para teste:');
        $this->table(
            ['Email', 'Senha', 'Descrição'],
            [
                ['admin@locadora.com', 'senha123', 'Administrador do sistema'],
                ['gerente@locadora.com', 'senha456', 'Gerente da locadora'],
                ['atendente@locadora.com', 'senha789', 'Atendente da locadora'],
            ]
        );
        
        return Command::SUCCESS;
    }
} 