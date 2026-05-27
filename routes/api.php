<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\MaquinasController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\GimnasiosController;

// Rutas públicas
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/gyms', [GimnasiosController::class, 'index']);
Route::get('/gyms/{gimnasio}', [GimnasiosController::class, 'show']);

// Rutas protegidas con autenticación
Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json($request->user()->load('gimnasio'));
    });
    Route::patch('/user', [UsuariosController::class, 'updateAuthenticatedUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gimnasios - lectura para usuarios autenticados


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

        Route::get('/machines', [MachineController::class, 'index']);
        Route::post('/machines', [MachineController::class, 'store']);
        Route::put('/machines/{maquina}', [MachineController::class, 'update']);
        Route::post('/machines/{maquina}', [MachineController::class, 'update']);
        Route::delete('/machines/{maquina}', [MachineController::class, 'destroy']);
        Route::post('/uploads', [FilesController::class, 'store']);
        Route::post('/gym', [AdminController::class, 'createGym']);
        Route::get('/gym', [AdminController::class, 'getGym']);
        Route::get('/gym/{id}', [AdminController::class, 'showGym']);
        Route::delete('/gym/{id}', [AdminController::class, 'destroyGym']);
        Route::put('/gym/{id}', [AdminController::class, 'updateGym']);

        // CRUD de usuarios para admin
        Route::apiResource('users', UsuariosController::class)
            ->parameters(['users' => 'usuario']);
        Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole']);
        Route::patch('/users/{id}/gym', [AdminController::class, 'updateUserGym']);

        // CRUD de reservas para admin
        Route::apiResource('reservations', ReservasController::class)
            ->parameters(['reservations' => 'reserva']);
    });
});
