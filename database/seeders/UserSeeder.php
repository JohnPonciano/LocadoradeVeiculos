<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuário admin padrão
        User::create([
            'name' => 'Admin',
            'email' => 'admin@locadora.com',
            'password' => Hash::make('senha123'),
        ]);

        // Usuário gestor
        User::create([
            'name' => 'Gerente',
            'email' => 'gerente@locadora.com',
            'password' => Hash::make('senha456'),
        ]);

        // Usuário atendente
        User::create([
            'name' => 'Atendente',
            'email' => 'atendente@locadora.com',
            'password' => Hash::make('senha789'),
        ]);

        // Criar mais 5 usuários aleatórios
        User::factory(5)->create();
    }
} 