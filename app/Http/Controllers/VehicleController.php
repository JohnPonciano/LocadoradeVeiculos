<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * Controller responsável pelo gerenciamento de veículos
 * 
 * Este controller gerencia todas as operações CRUD relacionadas aos veículos,
 * além de oferecer funcionalidades de busca avançada usando Elasticsearch.
 * A implementação utiliza o Elasticsearch para realizar buscas eficientes
 * enquanto mantém o banco de dados relacional como fonte primária de dados.
 */
class VehicleController extends Controller
{
    protected $elasticsearchService;

    /**
     * Construtor do controller
     * 
     * Inicializa o serviço de Elasticsearch necessário para as operações de busca
     * 
     * @param ElasticsearchService $elasticsearchService Serviço que gerencia a conexão com Elasticsearch
     */
    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Exibe uma lista paginada de veículos
     * 
     * Este método retorna uma lista de veículos, podendo ser filtrada por 
     * termos de busca aplicados sobre vários campos usando Elasticsearch.
     * Os resultados são paginados para melhor performance.
     * 
     * @param Request $request Requisição HTTP contendo parâmetros de busca e paginação
     * @return JsonResponse Lista de veículos com paginação
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);
            $search = $request->get('search');

            // Utilizando Elasticsearch para busca, se houver um termo de busca
            if ($search) {
                Log::info('Realizando busca com Elasticsearch', ['termo' => $search]);
                
                // Definição de campos e pesos para busca
                $searchFields = [
                    'model^3', // O modelo tem peso maior na relevância
                    'brand^2',
                    'year',
                    'color',
                    'license_plate',
                    'description'
                ];
                
                // Executa a busca no Elasticsearch
                $searchResults = $this->elasticsearchService->search('vehicles', [
                    'query' => [
                        'multi_match' => [
                            'query' => $search,
                            'fields' => $searchFields,
                            'fuzziness' => 'AUTO'
                        ]
                    ]
                ]);

                // Coleta os IDs dos resultados para buscar no banco de dados
                $ids = collect($searchResults['hits']['hits'])->pluck('_id')->toArray();
                if (empty($ids)) {
                    return response()->json([
                        'data' => [],
                        'meta' => [
                            'total' => 0,
                            'per_page' => (int)$perPage,
                            'current_page' => (int)$page,
                            'last_page' => 0,
                        ]
                    ]);
                }

                // Busca os registros completos do banco usando os IDs do Elasticsearch
                $query = Vehicle::whereIn('id', $ids)
                    ->orderByRaw("FIELD(id, " . implode(',', $ids) . ")"); // Mantém a ordem de relevância
                
                $total = $query->count();
                $vehicles = $query->skip(($page - 1) * $perPage)
                    ->take($perPage)
                    ->get();

                return response()->json([
                    'data' => $vehicles,
                    'meta' => [
                        'total' => $total,
                        'per_page' => (int)$perPage,
                        'current_page' => (int)$page,
                        'last_page' => ceil($total / $perPage),
                    ]
                ]);
            }

            // Listagem simples quando não há termo de busca
            $vehicles = Vehicle::paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'data' => $vehicles->items(),
                'meta' => [
                    'total' => $vehicles->total(),
                    'per_page' => $vehicles->perPage(),
                    'current_page' => $vehicles->currentPage(),
                    'last_page' => $vehicles->lastPage(),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro na listagem de veículos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao listar veículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Armazena um novo veículo no sistema
     * 
     * Valida os dados do veículo e, se válidos, cria um novo registro.
     * A indexação no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param Request $request Requisição HTTP contendo os dados do veículo
     * @return JsonResponse Dados do veículo criado ou erros de validação
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand' => 'required|string|max:100',
                'model' => 'required|string|max:100',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'color' => 'required|string|max:50',
                'license_plate' => 'required|string|max:10|unique:vehicles,license_plate',
                'daily_rate' => 'required|numeric|min:0',
                'is_available' => 'boolean',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicle = Vehicle::create($request->all());
            
            // A indexação no Elasticsearch é feita pelo observer

            return response()->json([
                'message' => 'Veículo criado com sucesso',
                'data' => $vehicle
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar veículo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao criar veículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe os detalhes de um veículo específico
     * 
     * @param int $id ID do veículo a ser exibido
     * @return JsonResponse Dados do veículo ou mensagem de erro
     */
    public function show(int $id): JsonResponse
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            
            return response()->json([
                'data' => $vehicle
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Veículo não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar veículo', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao buscar veículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza os dados de um veículo específico
     * 
     * Valida os dados recebidos e atualiza o registro do veículo.
     * A atualização no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param Request $request Requisição HTTP com os dados atualizados
     * @param int $id ID do veículo a ser atualizado
     * @return JsonResponse Veículo atualizado ou mensagem de erro
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'brand' => 'sometimes|required|string|max:100',
                'model' => 'sometimes|required|string|max:100',
                'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
                'color' => 'sometimes|required|string|max:50',
                'license_plate' => 'sometimes|required|string|max:10|unique:vehicles,license_plate,' . $id,
                'daily_rate' => 'sometimes|required|numeric|min:0',
                'is_available' => 'sometimes|boolean',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $vehicle->update($request->all());
            
            // A atualização no Elasticsearch é feita pelo observer

            return response()->json([
                'message' => 'Veículo atualizado com sucesso',
                'data' => $vehicle
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Veículo não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar veículo', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao atualizar veículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um veículo do sistema
     * 
     * Exclui o registro do veículo do banco de dados.
     * A remoção do índice no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param int $id ID do veículo a ser removido
     * @return JsonResponse Confirmação da remoção ou mensagem de erro
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            $vehicle->delete();
            
            // A remoção do documento no Elasticsearch é feita pelo observer
            
            return response()->json([
                'message' => 'Veículo removido com sucesso'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Veículo não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao remover veículo', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao remover veículo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
