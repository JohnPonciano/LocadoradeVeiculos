<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(ElasticsearchServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set reasonable timeout settings to fix socket hang up issues
        // Use environment variables if available, fallback to reasonable defaults
        $maxExecutionTime = env('PHP_MAX_EXECUTION_TIME', 30);
        $socketTimeout = env('PHP_DEFAULT_SOCKET_TIMEOUT', 30);
        
        ini_set('max_execution_time', $maxExecutionTime);
        ini_set('default_socket_timeout', $socketTimeout);
    }
}
