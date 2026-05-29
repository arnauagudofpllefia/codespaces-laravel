<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MachineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\MaquinasController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\ReservasController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GimnasiosController;
use App\Http\Controllers\MachineImagesController;

// Endpoints públicos: salud del servicio, autenticación inicial y datos visibles sin token.
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Catálogo público de gimnasios y avatares para formularios/perfiles.
Route::get('/gyms', [GimnasiosController::class, 'index']);
Route::get('/gyms/{gimnasio}', [GimnasiosController::class, 'show']);
Route::get('/avatar-images', [MachineImagesController::class, 'index']);
Route::get('/avatar-images/{filename}', [MachineImagesController::class, 'show'])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('avatar-images.show');

// Endpoints que requieren JWT válido.
Route::middleware('auth:api')->group(function () {
    // Perfil del usuario autenticado (incluye la relación de gimnasio para consumo directo del frontend).
    Route::get('/user', function (Request $request) {
        return response()->json($request->user()->load('gimnasio'));
    });

    // Edición de perfil y cierre de sesión.
    Route::patch('/user', [UsuariosController::class, 'updateAuthenticatedUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Máquinas y disponibilidad para usuarios autenticados.
    Route::get('/machines', [MaquinasController::class, 'index']);
    Route::get('/machines/{maquina}', [MaquinasController::class, 'show']);
    Route::get('/machines/{maquina}/slots', [MaquinasController::class, 'getSlots']);
    Route::get('/machines/{id}/reservations', [ReservasController::class, 'getMachineReservations']);

    // Gestión de reservas del usuario.
    Route::post('/reservations', [ReservasController::class, 'store']);
    Route::get('/reservations/my', [ReservasController::class, 'getMyReservations']);
    Route::delete('/reservations/{reserva}', [ReservasController::class, 'destroy']);

    // Bandeja de notificaciones in-app.
    Route::get('/notifications', [NotificacionesController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificacionesController::class, 'unreadCount']);
    Route::patch('/notifications/read-all', [NotificacionesController::class, 'markAllAsRead']);
    Route::patch('/notifications/{notificacion}/read', [NotificacionesController::class, 'markAsRead']);

    // Módulo de administración: solo rol admin.
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // CRUD administrativo de máquinas.
        Route::get('/machines', [MachineController::class, 'index']);
        Route::post('/machines', [MachineController::class, 'store']);
        Route::put('/machines/{maquina}', [MachineController::class, 'update']);
        Route::post('/machines/{maquina}', [MachineController::class, 'update']);
        Route::delete('/machines/{maquina}', [MachineController::class, 'destroy']);

        // CRUD administrativo de gimnasios.
        Route::post('/gym', [AdminController::class, 'createGym']);
        Route::get('/gym', [AdminController::class, 'getGym']);
        Route::get('/gym/{id}', [AdminController::class, 'showGym']);
        Route::delete('/gym/{id}', [AdminController::class, 'destroyGym']);
        Route::put('/gym/{id}', [AdminController::class, 'updateGym']);

        // CRUD de usuarios para admin.
        Route::apiResource('users', UsuariosController::class)
            ->parameters(['users' => 'usuario']);
        Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole']);
        Route::patch('/users/{id}/gym', [AdminController::class, 'updateUserGym']);

        // CRUD de reservas para admin.
        Route::apiResource('reservations', ReservasController::class)
            ->parameters(['reservations' => 'reserva']);
    });
});
