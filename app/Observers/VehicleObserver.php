<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Log;

/**
 * Observer para o modelo Vehicle
 * 
 * Este observer é responsável por manter os registros de veículos sincronizados
 * com o Elasticsearch. Ele gerencia a indexação, atualização e exclusão 
 * de documentos no Elasticsearch quando operações CRUD são realizadas no modelo Vehicle.
 * 
 * Diferente da implementação original, este observer indexa os documentos diretamente,
 * sem utilizar filas, para garantir que os dados estejam imediatamente disponíveis para busca.
 */
class VehicleObserver
{
    protected $elasticsearchService;

    /**
     * Construtor do observer
     * 
     * Inicializa o serviço do Elasticsearch que será utilizado para indexação
     * 
     * @param ElasticsearchService $elasticsearchService Serviço que gerencia a conexão com Elasticsearch
     */
    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Manipula o evento "created" do modelo Vehicle
     * 
     * Este método é chamado automaticamente quando um novo veículo é criado.
     * Ele indexa diretamente o documento no Elasticsearch para disponibilidade imediata
     * na busca, sem utilizar filas.
     * 
     * @param Vehicle $vehicle O veículo que foi criado
     */
    public function created(Vehicle $vehicle)
    {
        try {
            // Indexação direta no Elasticsearch (sem utilizar filas)
            $this->elasticsearchService->indexDocument('vehicles', $vehicle->id, $vehicle->toArray());
            Log::info('Veículo indexado diretamente no Elasticsearch', ['id' => $vehicle->id, 'model' => $vehicle->model]);
        } catch (\Throwable $e) {
            Log::error('Erro ao indexar veículo no Elasticsearch', [
                'id' => $vehicle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Manipula o evento "updated" do modelo Vehicle
     * 
     * Este método é chamado automaticamente quando um veículo existente é atualizado.
     * Ele atualiza o documento correspondente no Elasticsearch diretamente.
     * 
     * @param Vehicle $vehicle O veículo que foi atualizado
     */
    public function updated(Vehicle $vehicle)
    {
        try {
            // Atualização direta no Elasticsearch
            $this->elasticsearchService->indexDocument('vehicles', $vehicle->id, $vehicle->toArray());
            Log::info('Documento do veículo atualizado no Elasticsearch', ['id' => $vehicle->id]);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar veículo no Elasticsearch', [
                'id' => $vehicle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Manipula o evento "deleted" do modelo Vehicle
     * 
     * Este método é chamado automaticamente quando um veículo é excluído.
     * Ele remove o documento correspondente do Elasticsearch.
     * 
     * @param Vehicle $vehicle O veículo que foi excluído
     */
    public function deleted(Vehicle $vehicle)
    {
        try {
            // Exclusão do documento no Elasticsearch
            $this->elasticsearchService->deleteDocument('vehicles', $vehicle->id);
            Log::info('Documento do veículo excluído do Elasticsearch', ['id' => $vehicle->id]);
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir veículo do Elasticsearch', [
                'id' => $vehicle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 