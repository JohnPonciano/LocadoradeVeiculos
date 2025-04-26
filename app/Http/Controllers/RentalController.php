<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Vehicle;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    /**
     * Aplica middleware de autenticação em todos os endpoints
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Lista todos os aluguéis com opção de filtro por status
     */
    public function index(Request $request)
    {
        $query = Rental::with(['vehicle', 'customer']);
        
        // Filtro por status (opcional)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json($query->orderBy('created_at', 'desc')->paginate(10));
    }

    /**
     * Cria uma nova reserva de aluguel
     */
    public function store(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verifica disponibilidade do veículo
        $vehicle = Vehicle::findOrFail($request->vehicle_id);
        if (!$vehicle->available) {
            return response()->json(['error' => 'O veículo não está disponível para aluguel'], 400);
        }

        // Cria a reserva
        $rental = Rental::create([
            'vehicle_id' => $request->vehicle_id,
            'customer_id' => $request->customer_id,
            'status' => 'reserved'
        ]);

        $rental->load(['vehicle', 'customer']);

        return response()->json([
            'message' => 'Reserva criada com sucesso',
            'data' => $rental
        ], 201);
    }

    /**
     * Exibe detalhes de um aluguel específico
     */
    public function show(string $id)
    {
        try {
            $rental = Rental::with(['vehicle', 'customer'])->findOrFail($id);
            return response()->json($rental);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Aluguel não encontrado'], 404);
        }
    }

    /**
     * Inicia um aluguel, definindo a data de início
     */
    public function start(string $id)
    {
        $rental = Rental::with(['vehicle', 'customer'])->findOrFail($id);

        // Verifica se o aluguel está no estado correto
        if ($rental->status !== 'reserved') {
            return response()->json(['error' => 'Este aluguel não está no estado reservado'], 400);
        }

        // Marca o veículo como indisponível
        $vehicle = Vehicle::findOrFail($rental->vehicle_id);
        $vehicle->available = false;
        $vehicle->save();

        // Atualiza o aluguel
        $rental->start_date = Carbon::now();
        $rental->status = 'in_progress';
        $rental->save();

        return response()->json([
            'message' => 'Aluguel iniciado com sucesso',
            'data' => $rental
        ]);
    }

    /**
     * Encerra um aluguel, calculando o valor total
     */
    public function end(string $id)
    {
        $rental = Rental::with(['vehicle', 'customer'])->findOrFail($id);

        // Verifica se o aluguel está no estado correto
        if ($rental->status !== 'in_progress') {
            return response()->json(['error' => 'Este aluguel não está em andamento'], 400);
        }

        // Define a data de término
        $rental->end_date = Carbon::now();
        
        // Calcula a duração e o valor total
        $startDate = Carbon::parse($rental->start_date);
        $endDate = Carbon::parse($rental->end_date);
        
        // Calcula os dias, considerando cobrança mínima de 1 dia
        $diffInHours = $endDate->diffInHours($startDate);
        $days = $diffInHours < 24 ? 1 : ceil($diffInHours / 24);
        
        // Atualiza o aluguel
        $rental->total_amount = $days * $rental->vehicle->daily_rate;
        $rental->status = 'completed';
        $rental->save();

        // Marca o veículo como disponível novamente
        $vehicle = Vehicle::findOrFail($rental->vehicle_id);
        $vehicle->available = true;
        $vehicle->save();

        return response()->json([
            'message' => 'Aluguel finalizado com sucesso',
            'data' => [
                'rental' => $rental,
                'details' => [
                    'start_date' => $startDate->format('d/m/Y H:i:s'),
                    'end_date' => $endDate->format('d/m/Y H:i:s'),
                    'duration' => [
                        'days' => $days,
                        'hours' => $diffInHours,
                    ],
                    'daily_rate' => $rental->vehicle->daily_rate,
                    'total_amount' => $rental->total_amount
                ]
            ]
        ]);
    }

    /**
     * Cancela uma reserva de aluguel
     */
    public function cancel(string $id)
    {
        $rental = Rental::findOrFail($id);

        // Verifica se a reserva pode ser cancelada
        if ($rental->status !== 'reserved') {
            return response()->json(['error' => 'Apenas aluguéis reservados podem ser cancelados'], 400);
        }

        // Atualiza o status
        $rental->status = 'cancelled';
        $rental->save();

        return response()->json([
            'message' => 'Reserva cancelada com sucesso',
            'data' => $rental
        ]);
    }
}
