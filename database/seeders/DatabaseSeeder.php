<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar os seeders na ordem correta
        // 1. Usuários (autenticação da API)
        $this->call(UserSeeder::class);
        
        // 2. Veículos (necessários para criar aluguéis)
        $this->call(VehicleSeeder::class);
        
        // 3. Clientes (necessários para criar aluguéis)
        $this->call(CustomerSeeder::class);
        
        // 4. Aluguéis (dependem de veículos e clientes)
        $this->call(RentalSeeder::class);
    }
}
