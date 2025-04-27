<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Serviço para comunicação com o microserviço Python de relatórios
 * 
 * Este serviço encapsula a comunicação HTTP com o serviço Python
 * que fornece relatórios de receita e estatísticas sobre aluguéis
 */
class ReportService
{
    protected $baseUrl;

    /**
     * Construtor do serviço
     * 
     * Inicializa o serviço com a URL do serviço Python de relatórios
     * configurada no arquivo .env
     */
    public function __construct()
    {
        $this->baseUrl = env('REPORTS_SERVICE_URL', 'http://localhost:3000');
    }

    /**
     * Obtém o relatório de receita por veículo no período especificado
     * 
     * @param string $startDate Data inicial no formato YYYY-MM-DD
     * @param string $endDate Data final no formato YYYY-MM-DD
     * @return array Dados do relatório ou array vazio em caso de erro
     */
    public function getRevenueReport(string $startDate, string $endDate): array
    {
        try {
            $url = "{$this->baseUrl}/reports/revenue";
            $params = [
                'start' => $startDate,
                'end' => $endDate
            ];
            
            Log::info('Tentando obter relatório de receita', [
                'url' => $url,
                'params' => $params,
                'baseUrl' => $this->baseUrl
            ]);
            
            $response = Http::timeout(30)->get($url, $params);
            
            Log::info('Resposta da API de relatórios', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao obter relatório do serviço Python', [
                'url' => $url,
                'params' => $params,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'response' => $response->body()
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('Exceção ao comunicar com serviço Python', [
                'url' => "{$this->baseUrl}/reports/revenue",
                'params' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'baseUrl' => $this->baseUrl
            ]);

            return [];
        }
    }

    /**
     * Verifica se o serviço de relatórios está online
     * 
     * @return bool True se o serviço estiver respondendo, false caso contrário
     */
    public function isHealthy(): bool
    {
        try {
            $url = "{$this->baseUrl}/";
            
            Log::info('Tentando verificar saúde do serviço de relatórios', [
                'url' => $url,
                'baseUrl' => $this->baseUrl
            ]);
            
            $response = Http::timeout(5)->get($url);
            
            $isSuccessful = $response->successful();
            
            Log::info('Resposta da verificação de saúde do serviço de relatórios', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'isSuccessful' => $isSuccessful
            ]);
            
            return $isSuccessful;
        } catch (Exception $e) {
            Log::warning('Serviço de relatórios não está respondendo', [
                'url' => "{$this->baseUrl}/",
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'baseUrl' => $this->baseUrl
            ]);
            return false;
        }
    }
} 