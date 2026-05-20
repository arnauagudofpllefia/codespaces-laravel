<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\MaquinasController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GimnasiosController;

// Rutas públicas
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Rutas protegidas con autenticación
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gimnasios - lectura para usuarios autenticados
    Route::get('/gyms', [GimnasiosController::class, 'index']);
    Route::get('/gyms/{gimnasio}', [GimnasiosController::class, 'show']);

    // Máquinas - lectura para usuarios normales
    Route::get('/machines', [MaquinasController::class, 'index']);
    Route::get('/machines/{maquina}', [MaquinasController::class, 'show']);
    Route::get('/machines/{maquina}/slots', [MaquinasController::class, 'getSlots']);
    Route::get('/machines/{id}/reservations', [ReservasController::class, 'getMachineReservations']);

    // Reservas - para usuarios normales
    Route::post('/reservations', [ReservasController::class, 'store']);
    Route::get('/reservations/my', [ReservasController::class, 'getMyReservations']);
    Route::delete('/reservations/{reserva}', [ReservasController::class, 'destroy']);

    // Rutas de administrador
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::apiResource('machines', MaquinasController::class);
        Route::post('/gym', [AdminController::class, 'createGym']);
        Route::get('/gym', [AdminController::class, 'getGym']);
        Route::get('/gym/{id}', [AdminController::class, 'showGym']);
        Route::delete('/gym/{id}', [AdminController::class, 'destroyGym']);
        Route::put('/gym/{id}', [AdminController::class, 'updateGym']);

        // CRUD de usuarios para admin
        Route::apiResource('users', UsuariosController::class)
            ->parameters(['users' => 'usuario']);
        Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole']);

        // CRUD de reservas para admin
        Route::apiResource('reservations', ReservasController::class)
            ->parameters(['reservations' => 'reserva']);
    });
});
