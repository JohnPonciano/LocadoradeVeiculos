<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para gerenciamento de relatórios
 * 
 * Este controller é responsável por fornecer endpoints da API
 * que retornam relatórios sobre aluguéis e veículos, integrando
 * com o serviço Python de relatórios.
 */
class ReportController extends Controller
{
    protected $reportService;

    /**
     * Construtor do controller
     * 
     * @param ReportService $reportService Serviço de relatórios
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Retorna um relatório de receita por veículo no período especificado
     * 
     * Este endpoint chama o serviço Python para obter dados agregados de receita
     * e quantidade de aluguéis por veículo no intervalo de datas fornecido.
     * 
     * @param Request $request Requisição HTTP contendo parâmetros de data
     * @return JsonResponse Dados do relatório ou mensagem de erro
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            Log::info('Iniciando processamento de solicitação de relatório de receita', [
                'params' => $request->all()
            ]);
            
            // Validação dos parâmetros de data
            $validator = Validator::make($request->all(), [
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            ]);

            if ($validator->fails()) {
                Log::warning('Falha na validação de parâmetros para relatório', [
                    'errors' => $validator->errors()->toArray()
                ]);
                
                return response()->json([
                    'message' => 'Parâmetros inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('Parâmetros válidos, verificando disponibilidade do serviço');
            
            // Verificar se o serviço está disponível
            $isHealthy = $this->reportService->isHealthy();
            
            Log::info('Status de saúde do serviço de relatórios', [
                'isHealthy' => $isHealthy
            ]);
            
            if (!$isHealthy) {
                Log::error('Serviço de relatórios não está disponível');
                
                return response()->json([
                    'message' => 'Serviço de relatórios temporariamente indisponível'
                ], 503);
            }

            // Obter os dados do relatório
            $startDate = $request->get('start');
            $endDate = $request->get('end');
            
            Log::info('Solicitando relatório para o período', [
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            
            $reportData = $this->reportService->getRevenueReport($startDate, $endDate);
            
            Log::info('Relatório obtido com sucesso', [
                'count' => count($reportData),
                'sample' => array_slice($reportData, 0, 2) // Log apenas as duas primeiras entradas como amostra
            ]);

            // Retornar os dados
            return response()->json([
                'data' => $reportData,
                'meta' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_vehicles' => count($reportData),
                    'total_revenue' => array_sum(array_column($reportData, 'total_revenue')),
                    'total_rentals' => array_sum(array_column($reportData, 'total_rentals')),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar relatório de receitas', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'Erro ao gerar relatório',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);
        }
    }
} 