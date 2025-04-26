<?php

namespace App\Providers;

use App\Models\Vehicle;
use App\Observers\VehicleObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        //
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Registrar apenas o observer de Vehicle, já que não vamos mais usar Elasticsearch para Customer
        try {
            Vehicle::observe(VehicleObserver::class);
            Log::info('VehicleObserver registrado com sucesso');
        } catch (\Exception $e) {
            // Log error but continue if there are issues with Elasticsearch
            Log::error('Falha ao registrar VehicleObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
