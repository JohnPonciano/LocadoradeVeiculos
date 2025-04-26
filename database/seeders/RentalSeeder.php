<?php

namespace Database\Seeders;

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RentalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Aluguéis já finalizados
        $this->createCompletedRentals();
        
        // Aluguéis em andamento
        $this->createInProgressRentals();
        
        // Aluguéis reservados para o futuro
        $this->createReservedRentals();
        
        // Aluguéis cancelados
        $this->createCancelledRentals();
    }
    
    /**
     * Cria aluguéis já finalizados
     */
    private function createCompletedRentals(): void
    {
        // Aluguel 1 - Duração 3 dias (mês passado)
        $startDate = Carbon::now()->subMonth()->startOfMonth()->addDays(5);
        $endDate = (clone $startDate)->addDays(3);
        
        Rental::create([
            'vehicle_id' => 1, // Toyota Corolla
            'customer_id' => 1, // João Silva
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 450.00, // 3 dias x R$ 150,00
            'status' => 'completed',
        ]);
        
        // Aluguel 2 - Duração 5 dias (semana passada)
        $startDate = Carbon::now()->subWeeks(2);
        $endDate = (clone $startDate)->addDays(5);
        
        Rental::create([
            'vehicle_id' => 3, // Toyota RAV4
            'customer_id' => 2, // Maria Oliveira
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 1000.00, // 5 dias x R$ 200,00
            'status' => 'completed',
        ]);
        
        // Aluguel 3 - Duração 2 dias (há 3 dias)
        $startDate = Carbon::now()->subDays(5);
        $endDate = (clone $startDate)->addDays(2);
        
        Rental::create([
            'vehicle_id' => 5, // Volkswagen Polo
            'customer_id' => 3, // Pedro Santos
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 260.00, // 2 dias x R$ 130,00
            'status' => 'completed',
        ]);
    }
    
    /**
     * Cria aluguéis em andamento
     */
    private function createInProgressRentals(): void
    {
        // Aluguel 4 - Em andamento (começou ontem, termina em 2 dias)
        $startDate = Carbon::now()->subDay();
        $endDate = Carbon::now()->addDays(2);
        
        Rental::create([
            'vehicle_id' => 2, // Toyota Hilux
            'customer_id' => 4, // Ana Costa
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 660.00, // 3 dias x R$ 220,00
            'status' => 'in_progress',
        ]);
        
        // Aluguel 5 - Em andamento (começou há 2 dias, termina amanhã)
        $startDate = Carbon::now()->subDays(2);
        $endDate = Carbon::now()->addDay();
        
        Rental::create([
            'vehicle_id' => 7, // Honda HR-V
            'customer_id' => 5, // Lucas Pereira
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amount' => 570.00, // 3 dias x R$ 190,00
            'status' => 'in_progress',
        ]);
    }
    
    /**
     * Cria aluguéis reservados para o futuro
     */
    private function createReservedRentals(): void
    {
        // Aluguel 6 - Reservado para começar em 2 dias
        $startDate = Carbon::now()->addDays(2);
        $endDate = (clone $startDate)->addDays(4);
        
        Rental::create([
            'vehicle_id' => 9, // Chevrolet Tracker
            'customer_id' => 6, // Julia Fernandes
            'start_date' => null, // Ainda não iniciado
            'end_date' => null, // Ainda não finalizado
            'total_amount' => null, // Valor será calculado ao finalizar
            'status' => 'reserved',
        ]);
        
        // Aluguel 7 - Reservado para começar na próxima semana
        $startDate = Carbon::now()->addWeek();
        $endDate = (clone $startDate)->addDays(3);
        
        Rental::create([
            'vehicle_id' => 11, // Hyundai Creta
            'customer_id' => 7, // Marcos Rodrigues
            'start_date' => null, // Ainda não iniciado
            'end_date' => null, // Ainda não finalizado
            'total_amount' => null, // Valor será calculado ao finalizar
            'status' => 'reserved',
        ]);
        
        // Aluguel 8 - Reservado para começar em 3 dias
        $startDate = Carbon::now()->addDays(3);
        $endDate = (clone $startDate)->addDays(7);
        
        Rental::create([
            'vehicle_id' => 4, // Volkswagen Golf
            'customer_id' => 8, // Camila Almeida
            'start_date' => null, // Ainda não iniciado
            'end_date' => null, // Ainda não finalizado
            'total_amount' => null, // Valor será calculado ao finalizar
            'status' => 'reserved',
        ]);
    }
    
    /**
     * Cria aluguéis cancelados
     */
    private function createCancelledRentals(): void
    {
        // Aluguel 9 - Cancelado (estava agendado para a semana passada)
        Rental::create([
            'vehicle_id' => 6, // Honda Civic
            'customer_id' => 9, // Rafael Carvalho
            'start_date' => null,
            'end_date' => null,
            'total_amount' => null,
            'status' => 'cancelled',
        ]);
        
        // Aluguel 10 - Cancelado (estava agendado para hoje)
        Rental::create([
            'vehicle_id' => 8, // Chevrolet Onix
            'customer_id' => 10, // Fernanda Lima
            'start_date' => null,
            'end_date' => null,
            'total_amount' => null,
            'status' => 'cancelled',
        ]);
    }
} 