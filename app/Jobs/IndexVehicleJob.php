<?php

namespace App\Jobs;

use App\Models\Vehicle;
use Elasticsearch\ClientBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexVehicleJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Dispatchable;

    protected $vehicle;

    public function __construct(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
    }

    public function handle()
    {
        $client = ClientBuilder::create()->build();

        $client->index([
            'index' => 'vehicles',
            'id' => $this->vehicle->id,
            'body' => [
                'plate' => $this->vehicle->plate,
                'make' => $this->vehicle->make,
                'model' => $this->vehicle->model,
                'daily_rate' => $this->vehicle->daily_rate,
            ]
        ]);
    }
}
