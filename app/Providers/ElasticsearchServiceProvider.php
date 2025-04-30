<?php

namespace App\Providers;

use App\Services\ElasticsearchService;
use Illuminate\Support\ServiceProvider;

/**
 * Provider responsável por registrar o serviço do Elasticsearch
 * 
 * Este provider garante que apenas uma instância do ElasticsearchService
 * seja criada (singleton)  e disponibilizada para toda a aplicação.
 * Isso é importante para manter uma única conexão com o Elasticsearch
 * e permitir a injeção de dependência onde necessário.
 * Eu realmente não sei como o SINGLETON funciona de verdade, 
 * mas eu sei que ele é útil o pessoal do tutorial recomendou usar
 */

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ElasticsearchService::class, function ($app) {
            return new ElasticsearchService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
