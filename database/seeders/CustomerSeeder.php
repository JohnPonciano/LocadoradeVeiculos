<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array com clientes predefinidos
        $customers = [
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@email.com',
                'phone' => '(11) 98765-4321',
                'cnh' => '12345678900',
            ],
            [
                'name' => 'Maria Oliveira',
                'email' => 'maria.oliveira@email.com',
                'phone' => '(11) 97654-3210',
                'cnh' => '09876543211',
            ],
            [
                'name' => 'Pedro Santos',
                'email' => 'pedro.santos@email.com',
                'phone' => '(21) 98765-4321',
                'cnh' => '23456789012',
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana.costa@email.com',
                'phone' => '(21) 97654-3210',
                'cnh' => '34567890123',
            ],
            [
                'name' => 'Lucas Pereira',
                'email' => 'lucas.pereira@email.com',
                'phone' => '(31) 98765-4321',
                'cnh' => '45678901234',
            ],
            [
                'name' => 'Julia Fernandes',
                'email' => 'julia.fernandes@email.com',
                'phone' => '(31) 97654-3210',
                'cnh' => '56789012345',
            ],
            [
                'name' => 'Marcos Rodrigues',
                'email' => 'marcos.rodrigues@email.com',
                'phone' => '(41) 98765-4321',
                'cnh' => '67890123456',
            ],
            [
                'name' => 'Camila Almeida',
                'email' => 'camila.almeida@email.com',
                'phone' => '(41) 97654-3210',
                'cnh' => '78901234567',
            ],
            [
                'name' => 'Rafael Carvalho',
                'email' => 'rafael.carvalho@email.com',
                'phone' => '(51) 98765-4321',
                'cnh' => '89012345678',
            ],
            [
                'name' => 'Fernanda Lima',
                'email' => 'fernanda.lima@email.com',
                'phone' => '(51) 97654-3210',
                'cnh' => '90123456789',
            ],
        ];

        // Criação dos clientes
        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
} 