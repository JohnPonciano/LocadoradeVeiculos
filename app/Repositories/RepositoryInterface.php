<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface para repositórios
 * 
 * Define um contrato comum para todos os repositórios da aplicação,
 * seguindo o princípio de design por contrato e facilitando a implementação
 * do padrão repository.
 */
interface RepositoryInterface
{
    /**
     * Obtém todos os registros com paginação
     * 
     * @param int $perPage Quantidade de itens por página
     * @param int $page Número da página atual
     * @return LengthAwarePaginator Resultado paginado
     */
    public function getAllPaginated(int $perPage = 10, int $page = 1): LengthAwarePaginator;
    
    /**
     * Encontra um registro pelo ID
     * 
     * @param int $id ID do registro
     * @return Model Modelo encontrado
     */
    public function findById(int $id): Model;
    
    /**
     * Cria um novo registro
     * 
     * @param array $data Dados do registro
     * @return Model Modelo criado
     */
    public function create(array $data): Model;
    
    /**
     * Atualiza um registro existente
     * 
     * @param int $id ID do registro
     * @param array $data Dados atualizados
     * @return Model Modelo atualizado
     */
    public function update(int $id, array $data): Model;
    
    /**
     * Remove um registro
     * 
     * @param int $id ID do registro
     * @return bool Resultado da operação
     */
    public function delete(int $id): bool;
} 