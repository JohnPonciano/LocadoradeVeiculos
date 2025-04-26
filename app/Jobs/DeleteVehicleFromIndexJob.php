<?php

namespace App\Jobs;

use App\Services\ElasticsearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteVehicleFromIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The vehicle ID.
     *
     * @var string|int
     */
    protected $vehicleId;

    /**
     * Create a new job instance.
     */
    public function __construct($vehicleId)
    {
        $this->vehicleId = $vehicleId;
    }

    /**
     * Execute the job.
     */
    public function handle(ElasticsearchService $elasticsearch): void
    {
        try {
            $elasticsearch->deleteDocument('vehicles', $this->vehicleId);
        } catch (\Exception $e) {
            // Log error but don't retry if document doesn't exist
            logger()->warning('Failed to delete vehicle from Elasticsearch: ' . $e->getMessage());
        }
    }
} 