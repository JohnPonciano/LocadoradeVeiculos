<?php

namespace App\Http\Controllers;

use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para gerenciamento de clientes
 * 
 * Esta classe é responsável pelo CRUD de clientes e implementa pesquisa
 * usando o padrão Repository para separar a lógica de acesso a dados da
 * lógica de negócios.
 */
class CustomerController extends Controller
{
    protected $customerRepository;

    /**
     * Construtor do controlador
     * 
     * Inicializa o repositório e aplica middleware de autenticação
     * 
     * @param CustomerRepository $customerRepository Repositório de clientes
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->middleware('auth:api');
        $this->customerRepository = $customerRepository;
    }

    /**
     * Exibe uma lista paginada de clientes com opção de pesquisa
     * 
     * Retorna todos os clientes paginados com opção de filtro por nome, email, 
     * telefone e CNH usando o operador LIKE do SQL (equivalente a %termo%).
     * 
     * @param Request $request Request HTTP contendo parâmetros de consulta
     * @return JsonResponse Lista paginada de clientes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Determina se deve usar busca ou listagem simples
            if ($search) {
                // Busca com filtro via repositório
                $customers = $this->customerRepository->search($search, $perPage, $page);
            } else {
                // Listagem simples com paginação
                $customers = $this->customerRepository->getAllPaginated($perPage, $page);
            }
            
            // Log para diagnóstico
            Log::info('Resultado da pesquisa de clientes', [
                'termo' => $search ?? 'todos',
                'total' => $customers->total(),
                'pagina' => $customers->currentPage()
            ]);
            
            // Retorna resposta formatada
            return response()->json([
                'data' => $customers->items(),
                'meta' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'search_term' => $search ?? null
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro na listagem de clientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao listar clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Armazena um novo cliente no sistema
     * 
     * Cria um novo cliente no banco de dados após validar os dados recebidos.
     * 
     * @param Request $request Dados do cliente a ser criado
     * @return JsonResponse Cliente criado ou erros de validação
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validação dos dados recebidos
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:customers',
                'phone' => 'required|string',
                'cnh' => 'required|string|unique:customers',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Criação do cliente via repositório
            $customer = $this->customerRepository->create($validator->validated());

            return response()->json($customer, 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar cliente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao criar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe os detalhes de um cliente específico
     * 
     * Retorna um cliente específico pelo seu ID.
     * 
     * @param string $id ID do cliente a ser exibido
     * @return JsonResponse Dados do cliente
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Busca o cliente via repositório
            $customer = $this->customerRepository->findById($id);
            return response()->json($customer);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar cliente', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao buscar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza os dados de um cliente existente
     * 
     * Atualiza os dados de um cliente existente após validar os dados.
     * 
     * @param Request $request Dados a serem atualizados
     * @param string $id ID do cliente a ser atualizado
     * @return JsonResponse Cliente atualizado ou erros de validação
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Validação dos dados recebidos
            $validator = Validator::make($request->all(), [
                'name' => 'string',
                'email' => 'email|unique:customers,email,' . $id,
                'phone' => 'string',
                'cnh' => 'string|unique:customers,cnh,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Atualização do cliente via repositório
            $customer = $this->customerRepository->update($id, $request->only(['name', 'email', 'phone', 'cnh']));

            return response()->json($customer);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar cliente', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao atualizar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um cliente do sistema
     * 
     * Exclui um cliente do banco de dados.
     * 
     * @param string $id ID do cliente a ser excluído
     * @return JsonResponse Resposta vazia com código 204
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Exclusão do cliente via repositório
            $this->customerRepository->delete($id);
            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente não encontrado'
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir cliente', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao excluir cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
