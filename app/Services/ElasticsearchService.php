<?php

namespace App\Services;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Support\Facades\Log;

/**
 * Service class for Elasticsearch operations
 */
class ElasticsearchService
{
    protected Client $client;

    public function __construct()
    {
        try {
            $host = env('ELASTICSEARCH_HOST', 'elasticsearch');
            $port = env('ELASTICSEARCH_PORT', 9200);
            $scheme = env('ELASTICSEARCH_SCHEME', 'http');
            $connectionUrl = "{$scheme}://{$host}:{$port}";
            
            Log::debug("Inicializando cliente ElasticSearch", [
                'connection_url' => $connectionUrl
            ]);
            
            $this->client = ClientBuilder::create()
                ->setHosts([$connectionUrl])
                ->build();
                
            Log::info("Cliente ElasticSearch inicializado com sucesso");
        } catch (\Exception $e) {
            Log::error('Falha ao inicializar cliente ElasticSearch: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            // Create a mock client for development environment
            if (app()->environment('local')) {
                Log::info('Criando cliente mock para ambiente de desenvolvimento');
                $this->client = new class implements Client {
                    public function index($params) { return []; }
                    public function search($params) { return ['hits' => ['hits' => [], 'total' => ['value' => 0]]]; }
                    public function delete($params) { return []; }
                    public function indices() { 
                        return new class {
                            public function exists($params) { return false; }
                            public function create($params) { return []; }
                            public function delete($params) { return []; }
                        };
                    }
                    public function __call($method, $args) { return []; }
                };
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get the Elasticsearch client instance
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Index a document in Elasticsearch
     */
    public function indexDocument(string $index, string $id, array $body): array
    {
        try {
            Log::debug("Indexando documento no ElasticSearch", [
                'index' => $index, 
                'id' => $id,
                'body_size' => strlen(json_encode($body))
            ]);
            
            $response = $this->client->index([
                'index' => $index,
                'id' => $id,
                'body' => $body
            ]);
            
            Log::info("Documento indexado com sucesso", [
                'index' => $index,
                'id' => $id,
                'result' => $response['result'] ?? 'unknown'
            ]);
            
            return $response->asArray();
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro de indexação ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'id' => $id,
                'status' => $e->getCode()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'id' => $id
            ]);
            return [];
        }
    }

    /**
     * Search for documents in Elasticsearch
     */
    public function search(string $index, array $query): array
    {
        try {
            Log::debug("Executando busca no ElasticSearch", [
                'index' => $index,
                'query' => json_encode($query)
            ]);
            
            $start = microtime(true);
            $response = $this->client->search([
                'index' => $index,
                'body' => $query
            ]);
            $end = microtime(true);
            $time = round(($end - $start) * 1000, 2);
            
            $result = $response->asArray();
            $totalHits = $result['hits']['total']['value'] ?? 0;
            
            Log::info("Busca ElasticSearch executada", [
                'index' => $index,
                'tempo_ms' => $time,
                'total_hits' => $totalHits,
                'took_ms' => $result['took'] ?? 0,
                'shards' => $result['_shards'] ?? []
            ]);
            
            return $result;
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro de busca ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'status' => $e->getCode(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ['hits' => ['hits' => [], 'total' => ['value' => 0]]];
        }
    }

    /**
     * Delete a document from Elasticsearch
     */
    public function deleteDocument(string $index, string $id): array
    {
        try {
            Log::debug("Removendo documento do ElasticSearch", [
                'index' => $index,
                'id' => $id
            ]);
            
            $response = $this->client->delete([
                'index' => $index,
                'id' => $id
            ]);
            
            Log::info("Documento removido com sucesso", [
                'index' => $index,
                'id' => $id
            ]);
            
            return $response->asArray();
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro ao remover documento ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'id' => $id,
                'status' => $e->getCode()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'id' => $id
            ]);
            return [];
        }
    }

    /**
     * Delete an index from Elasticsearch
     */
    public function deleteIndex(string $index): array
    {
        try {
            Log::debug("Removendo índice do ElasticSearch", [
                'index' => $index
            ]);
            
            $response = $this->client->indices()->delete([
                'index' => $index
            ]);
            
            Log::info("Índice removido com sucesso", [
                'index' => $index
            ]);
            
            return $response->asArray();
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro ao remover índice ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'status' => $e->getCode()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index
            ]);
            return [];
        }
    }

    /**
     * Check if an index exists
     */
    public function indexExists(string $index): bool
    {
        try {
            Log::debug("Verificando se índice existe", ['index' => $index]);
            
            $response = $this->client->indices()->exists(['index' => $index]);
            $exists = $response->asBool();
            
            Log::info("Verificação de índice concluída", [
                'index' => $index, 
                'exists' => $exists ? 'SIM' : 'NÃO'
            ]);
            
            return $exists;
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro ao verificar índice ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'status' => $e->getCode()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index
            ]);
            return false;
        }
    }

    /**
     * Create an index with specific mappings
     */
    public function createIndex(string $index, array $mappings = []): array
    {
        try {
            Log::debug("Criando índice no ElasticSearch", [
                'index' => $index,
                'mappings' => $mappings
            ]);
            
            $params = ['index' => $index];
            
            if (!empty($mappings)) {
                $params['body'] = ['mappings' => $mappings];
            }
            
            $response = $this->client->indices()->create($params);
            
            Log::info("Índice criado com sucesso", ['index' => $index]);
            
            return $response->asArray();
        } catch (ClientResponseException | ServerResponseException $e) {
            Log::error('Erro ao criar índice ElasticSearch: ' . $e->getMessage(), [
                'index' => $index,
                'status' => $e->getCode()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Erro geral ElasticSearch: ' . $e->getMessage(), [
                'index' => $index
            ]);
            return [];
        }
    }
} 