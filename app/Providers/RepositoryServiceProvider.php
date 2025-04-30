<?php

namespace App\Providers;

use App\Repositories\CustomerRepository;
use App\Repositories\RepositoryInterface;
use App\Repositories\VehicleRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Provider é para registrar os repositórios 
 * 
 * Este provider é responsável por registrar os repositórios no container
 * de injeção de dependências, permitindo a resolução automática das dependências
 * com base nas interfaces.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços no container
     *
     * @return void
     */
    public function register()
    {
        // Registra os repositórios concretos quando suas classes são solicitadas
        // diretamente, sem precisar de binding com interface
        $this->app->bind(VehicleRepository::class, VehicleRepository::class);
        $this->app->bind(CustomerRepository::class, CustomerRepository::class);
    }

    /**
     * Inicializa os serviços
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 