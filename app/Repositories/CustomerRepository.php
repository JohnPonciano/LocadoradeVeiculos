<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Repositório para o modelo Customer
 * 
 * Este repositório implementa o padrão Repository para encapsular a lógica de acesso a dados,
 * separando as regras de negócio da lógica de persistência e acesso aos dados.
 * Fornece métodos para pesquisa e operações CRUD de clientes.
 */
class CustomerRepository implements RepositoryInterface
{
    protected $model;

    /**
     * Construtor do repositório
     * 
     * @param Customer $customer Modelo Customer injetado automaticamente
     */
    public function __construct(Customer $customer)
    {
        $this->model = $customer;
    }

    /**
     * Obtém todos os clientes com paginação
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
     * Busca clientes por termo de pesquisa
     * 
     * Pesquisa por nome, email, telefone ou CNH usando operadores LIKE
     * 
     * @param string $searchTerm Termo de busca
     * @param int $perPage Quantidade de itens por página
     * @param int $page Número da página atual
     * @return LengthAwarePaginator Resultado paginado
     */
    public function search(string $searchTerm, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        // Log da operação para diagnóstico
        Log::info('Buscando clientes', ['termo' => $searchTerm]);
        
        // Aplica filtro de pesquisa nos campos relevantes
        return $this->model->where(function($query) use ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('cnh', 'like', "%{$searchTerm}%");
        })->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Encontra um cliente pelo ID
     * 
     * @param int $id ID do cliente
     * @return Model Cliente encontrado
     */
    public function findById(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Cria um novo cliente
     * 
     * @param array $data Dados do cliente
     * @return Model Cliente criado
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Atualiza um cliente existente
     * 
     * @param int $id ID do cliente
     * @param array $data Dados atualizados
     * @return Model Cliente atualizado
     */
    public function update(int $id, array $data): Model
    {
        $customer = $this->findById($id);
        $customer->update($data);
        return $customer;
    }

    /**
     * Remove um cliente
     * 
     * @param int $id ID do cliente
     * @return bool Resultado da operação
     */
    public function delete(int $id): bool
    {
        $customer = $this->findById($id);
        return $customer->delete();
    }
} 