<?php

namespace App\Http\Controllers;

use App\Repositories\VehicleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * Controller responsável pelo gerenciamento de veículos
 * 
 * Este controller gerencia todas as operações CRUD relacionadas aos veículos,
 * utilizando o padrão Repository para separar a lógica de acesso a dados da lógica de negócios.
 * Implementa funcionalidades de busca avançada usando Elasticsearch via repositório.
 */
class VehicleController extends Controller
{
    protected $vehicleRepository;

    /**
     * Construtor do controller
     * 
     * Inicializa o repositório de veículos que encapsula o acesso a dados e Elasticsearch
     * 
     * @param VehicleRepository $vehicleRepository Repositório de veículos
     */
    public function __construct(VehicleRepository $vehicleRepository)
    {
        $this->vehicleRepository = $vehicleRepository;
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

            // Determina se deve usar busca simples ou Elasticsearch
            if ($search) {
                // Busca avançada com Elasticsearch via repositório
                $result = $this->vehicleRepository->searchWithElasticsearch($search, $perPage, $page);
                
                return response()->json([
                    'data' => $result['items'],
                    'meta' => [
                        'total' => $result['total'],
                        'per_page' => $result['per_page'],
                        'current_page' => $result['current_page'],
                        'last_page' => $result['last_page'],
                    ]
                ]);
            }

            // Listagem simples quando não há termo de busca
            $vehicles = $this->vehicleRepository->getAllPaginated($perPage, $page);
            
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
            // Log detalhado do erro para diagnóstico
            Log::error('Erro na listagem de veículos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Resposta de erro amigável
            return response()->json([
                'message' => 'Erro ao listar veículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pesquisa veículos com base em um termo de busca
     * 
     * Utiliza Elasticsearch para realizar uma busca mais eficiente e relevante
     * nos veículos, considerando diversos campos como placa, fabricante e modelo.
     * 
     * @param Request $request Requisição HTTP contendo parâmetro de busca 'q'
     * @return JsonResponse Lista de veículos encontrados com base no termo de busca
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->get('q');
            $perPage = $request->get('per_page', 10);
            $page = $request->get('page', 1);

            if (empty($searchTerm)) {
                return response()->json([
                    'message' => 'É necessário fornecer um termo de busca (parâmetro q)',
                ], 400);
            }

            $result = $this->vehicleRepository->searchWithElasticsearch($searchTerm, $perPage, $page);
            
            return response()->json([
                'data' => $result['items'],
                'meta' => [
                    'total' => $result['total'],
                    'per_page' => $result['per_page'],
                    'current_page' => $result['current_page'],
                    'last_page' => $result['last_page'],
                    'search_term' => $searchTerm
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro na pesquisa de veículos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao pesquisar veículos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Armazena um novo veículo no sistema
     * 
     * Valida os dados do veículo e, se válidos, cria um novo registro via repositório.
     * A indexação no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param Request $request Requisição HTTP contendo os dados do veículo
     * @return JsonResponse Dados do veículo criado ou erros de validação
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validação dos dados recebidos
            $validator = Validator::make($request->all(), [
                'plate' => 'required|string|max:10|unique:vehicles,plate',
                'make' => 'required|string|max:100',
                'model' => 'required|string|max:100',
                'daily_rate' => 'required|numeric|min:0',
                'available' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Criação do veículo via repositório
            $vehicle = $this->vehicleRepository->create($request->all());
            
            return response()->json([
                'message' => 'Veículo criado com sucesso',
                'data' => $vehicle
            ], 201);
        } catch (\Throwable $e) {
            // Log detalhado do erro
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
            // Busca o veículo via repositório
            $vehicle = $this->vehicleRepository->findById($id);
            
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
     * Valida os dados recebidos e atualiza o registro do veículo via repositório.
     * A atualização no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param Request $request Requisição HTTP com os dados atualizados
     * @param int $id ID do veículo a ser atualizado
     * @return JsonResponse Veículo atualizado ou mensagem de erro
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validação dos dados recebidos
            $validator = Validator::make($request->all(), [
                'plate' => 'sometimes|required|string|max:10|unique:vehicles,plate,' . $id,
                'make' => 'sometimes|required|string|max:100',
                'model' => 'sometimes|required|string|max:100',
                'daily_rate' => 'sometimes|required|numeric|min:0',
                'available' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Atualização do veículo via repositório
            $vehicle = $this->vehicleRepository->update($id, $request->all());
            
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
     * Exclui o registro do veículo do banco de dados via repositório.
     * A remoção do índice no Elasticsearch é feita automaticamente pelo observer.
     * 
     * @param int $id ID do veículo a ser removido
     * @return JsonResponse Confirmação da remoção ou mensagem de erro
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Exclusão do veículo via repositório
            $this->vehicleRepository->delete($id);
            
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
