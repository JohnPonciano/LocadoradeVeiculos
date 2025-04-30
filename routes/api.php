<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas de autenticação
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');

// Rotas protegidas
Route::middleware('auth:api')->group(function () {
    // Rota de usuário autenticado
    Route::get('user', function (Request $request) {
        return $request->user();
    });
    
    // Rotas para veículos
    Route::get('/vehicles/search', [VehicleController::class, 'search']);
    Route::apiResource('vehicles', VehicleController::class);
    
    // Rotas para clientes
    Route::apiResource('customers', CustomerController::class);
    
    // Rotas para aluguéis
    Route::apiResource('rentals', RentalController::class);
    Route::post('rentals/{rental}/start', [RentalController::class, 'start']);
    Route::post('rentals/{rental}/end', [RentalController::class, 'end']);
    Route::post('rentals/{rental}/cancel', [RentalController::class, 'cancel']);
    
    // Rotas para relatórios
    Route::get('reports/revenue', [ReportController::class, 'revenue']);

    // Health check
    Route::get('health', function () {
        return response()->json(['status' => 'ok']);
    });
}); 