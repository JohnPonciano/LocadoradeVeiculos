<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\ElasticsearchService;

class CustomerObserver
{
    protected ElasticsearchService $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Handle the Customer "created" event.
     */
    public function created(Customer $customer): void
    {
        $this->elasticsearch->indexDocument('customers', $customer->id, $customer->toArray());
    }

    /**
     * Handle the Customer "updated" event.
     */
    public function updated(Customer $customer): void
    {
        $this->elasticsearch->indexDocument('customers', $customer->id, $customer->toArray());
    }

    /**
     * Handle the Customer "deleted" event.
     */
    public function deleted(Customer $customer): void
    {
        try {
            $this->elasticsearch->deleteDocument('customers', $customer->id);
        } catch (\Exception $e) {
            // Ignore if document doesn't exist
        }
    }
} 