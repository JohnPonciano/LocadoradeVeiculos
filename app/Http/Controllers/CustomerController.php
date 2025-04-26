<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador para gerenciamento de clientes
 * 
 * Esta classe é responsável pelo CRUD de clientes e implementa pesquisa
 * usando o banco de dados com filtros %like% para maior flexibilidade.
 * A busca por Elasticsearch foi removida para simplificar e melhorar a performance.
 */
class CustomerController extends Controller
{
    /**
     * Create a new CustomerController instance.
     * 
     * Aplica middleware de autenticação a todas as rotas do controlador.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource with optional search.
     * 
     * Retorna todos os clientes paginados com opção de filtro por nome, email, 
     * telefone e CNH usando o operador LIKE do SQL (equivalente a %termo%).
     * 
     * @param Request $request Request HTTP contendo parâmetros de consulta
     * @return \Illuminate\Http\JsonResponse Lista paginada de clientes
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);
        
        // Iniciar a query
        $query = Customer::query();
        
        // Aplicar filtro de pesquisa se fornecido
        if ($search) {
            Log::info('Pesquisando clientes com termo', ['search' => $search]);
            
            // Aplicar %like% em todos os campos relevantes
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('cnh', 'like', "%{$search}%");
            });
        }
        
        // Executar a query com paginação
        $customers = $query->paginate($perPage);
        
        Log::info('Resultado da pesquisa de clientes', [
            'termo' => $search ?? 'todos',
            'total' => $customers->total(),
            'pagina' => $customers->currentPage()
        ]);
        
        // Retornar resposta formatada
        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'search_term' => $search ?? null
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Cria um novo cliente no banco de dados após validar os dados recebidos.
     * 
     * @param Request $request Dados do cliente a ser criado
     * @return \Illuminate\Http\JsonResponse Cliente criado ou erros de validação
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:customers',
            'phone' => 'required|string',
            'cnh' => 'required|string|unique:customers',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer = Customer::create($validator->validated());

        return response()->json($customer, 201);
    }

    /**
     * Display the specified resource.
     * 
     * Retorna um cliente específico pelo seu ID.
     * 
     * @param string $id ID do cliente a ser exibido
     * @return \Illuminate\Http\JsonResponse Dados do cliente
     */
    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     * 
     * Atualiza os dados de um cliente existente após validar os dados.
     * 
     * @param Request $request Dados a serem atualizados
     * @param string $id ID do cliente a ser atualizado
     * @return \Illuminate\Http\JsonResponse Cliente atualizado ou erros de validação
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'email' => 'email|unique:customers,email,' . $id,
            'phone' => 'string',
            'cnh' => 'string|unique:customers,cnh,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer->update($request->only(['name', 'email', 'phone', 'cnh']));

        return response()->json($customer);
    }

    /**
     * Remove the specified resource from storage.
     * 
     * Exclui um cliente do banco de dados.
     * 
     * @param string $id ID do cliente a ser excluído
     * @return \Illuminate\Http\JsonResponse Resposta vazia com código 204
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(null, 204);
    }
}
