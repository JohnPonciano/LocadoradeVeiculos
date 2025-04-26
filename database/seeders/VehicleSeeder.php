<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Array com veículos predefinidos
        $vehicles = [
            // Toyota
            [
                'plate' => 'ABC1234',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'daily_rate' => 150.00,
                'available' => true,
            ],
            [
                'plate' => 'DEF5678',
                'make' => 'Toyota',
                'model' => 'Hilux',
                'daily_rate' => 220.00,
                'available' => true,
            ],
            [
                'plate' => 'GHI9012',
                'make' => 'Toyota',
                'model' => 'RAV4',
                'daily_rate' => 200.00,
                'available' => true,
            ],
            
            // Volkswagen
            [
                'plate' => 'JKL3456',
                'make' => 'Volkswagen',
                'model' => 'Golf',
                'daily_rate' => 180.00,
                'available' => true,
            ],
            [
                'plate' => 'MNO7890',
                'make' => 'Volkswagen',
                'model' => 'Polo',
                'daily_rate' => 130.00,
                'available' => true,
            ],
            
            // Honda
            [
                'plate' => 'PQR1235',
                'make' => 'Honda',
                'model' => 'Civic',
                'daily_rate' => 160.00,
                'available' => true,
            ],
            [
                'plate' => 'STU6789',
                'make' => 'Honda',
                'model' => 'HR-V',
                'daily_rate' => 190.00,
                'available' => true,
            ],
            
            // Chevrolet
            [
                'plate' => 'VWX9012',
                'make' => 'Chevrolet',
                'model' => 'Onix',
                'daily_rate' => 120.00,
                'available' => true,
            ],
            [
                'plate' => 'YZA3456',
                'make' => 'Chevrolet',
                'model' => 'Tracker',
                'daily_rate' => 180.00,
                'available' => true,
            ],
            
            // Hyundai
            [
                'plate' => 'BCD7890',
                'make' => 'Hyundai',
                'model' => 'HB20',
                'daily_rate' => 120.00,
                'available' => true,
            ],
            [
                'plate' => 'EFG1234',
                'make' => 'Hyundai',
                'model' => 'Creta',
                'daily_rate' => 170.00,
                'available' => true,
            ],
            
            // Nissan
            [
                'plate' => 'HIJ5678',
                'make' => 'Nissan',
                'model' => 'Kicks',
                'daily_rate' => 170.00,
                'available' => true,
            ],
            
            // Fiat
            [
                'plate' => 'KLM9012',
                'make' => 'Fiat',
                'model' => 'Argo',
                'daily_rate' => 110.00,
                'available' => true,
            ],
            [
                'plate' => 'NOP3456',
                'make' => 'Fiat',
                'model' => 'Cronos',
                'daily_rate' => 125.00,
                'available' => true,
            ],
        ];

        // Criação dos veículos
        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }
    }
} 