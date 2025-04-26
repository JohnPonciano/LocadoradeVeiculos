<?php

namespace App\Repositories;

use App\Models\Vehicle;
use App\Services\ElasticsearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Repositório para o modelo Vehicle
 * 
 * Este repositório implementa o padrão Repository para encapsular a lógica de acesso a dados,
 * separando as regras de negócio da lógica de persistência e acesso aos dados.
 * Fornece métodos para pesquisa avançada com Elasticsearch e operações CRUD.
 */
class VehicleRepository implements RepositoryInterface
{
    protected $elasticsearchService;
    protected $model;

    /**
     * Construtor do repositório
     * 
     * @param ElasticsearchService $elasticsearchService Serviço que gerencia a conexão com Elasticsearch
     * @param Vehicle $vehicle Modelo Vehicle injetado automaticamente
     */
    public function __construct(ElasticsearchService $elasticsearchService, Vehicle $vehicle)
    {
        $this->elasticsearchService = $elasticsearchService;
        $this->model = $vehicle;
    }

    /**
     * Obtém todos os veículos com paginação
     * 
     * @param int $perPage Quantidade de itens por página
     * @param int $page Número da página atual
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getAllPaginated(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Busca veículos usando Elasticsearch
     * 
     * @param string $searchTerm Termo de busca
     * @param int $perPage Quantidade de itens por página
     * @param int $page Número da página atual
     * @return array Resultado contendo items e informação de paginação
     */
    public function searchWithElasticsearch(string $searchTerm, int $perPage = 10, int $page = 1): array
    {
        // Log da operação para diagnóstico
        Log::info('Buscando veículos com Elasticsearch', ['termo' => $searchTerm]);
        
        // Definição de campos e pesos para busca
        $searchFields = [
            'model^3', // O modelo tem peso maior na relevância
            'make^2',
            'plate'
        ];
        
        // Executa a busca no Elasticsearch
        $searchResults = $this->elasticsearchService->search('vehicles', [
            'query' => [
                'multi_match' => [
                    'query' => $searchTerm,
                    'fields' => $searchFields,
                    'fuzziness' => 'AUTO'
                ]
            ]
        ]);

        // Coleta os IDs dos resultados para buscar no banco de dados
        $ids = collect($searchResults['hits']['hits'])->pluck('_id')->toArray();
        
        // Retorna array vazio se não encontrou resultados
        if (empty($ids)) {
            return [
                'items' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0,
            ];
        }

        // Busca os registros completos do banco usando os IDs do Elasticsearch
        $query = $this->model->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, " . implode(',', $ids) . ")"); // Mantém a ordem de relevância
        
        $total = $query->count();
        
        // Aplica paginação manual
        $items = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Retorna resultado formatado
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * Encontra um veículo pelo ID
     * 
     * @param int $id ID do veículo
     * @return Model Veículo encontrado
     */
    public function findById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Cria um novo veículo
     * 
     * @param array $data Dados do veículo
     * @return Model Veículo criado
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Atualiza um veículo existente
     * 
     * @param int $id ID do veículo
     * @param array $data Dados atualizados
     * @return Model Veículo atualizado
     */
    public function update(int $id, array $data): Model
    {
        $vehicle = $this->findById($id);
        $vehicle->update($data);
        return $vehicle;
    }

    /**
     * Remove um veículo
     * 
     * @param int $id ID do veículo
     * @return bool Resultado da operação
     */
    public function delete(int $id): bool
    {
        $vehicle = $this->findById($id);
        return $vehicle->delete();
    }
} 