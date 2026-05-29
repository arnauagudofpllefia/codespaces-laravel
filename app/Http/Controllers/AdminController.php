<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Gimnasio;
use App\Models\Usuario;
use App\Models\Reserva;

/**
 * Endpoints exclusivos de administración para gestión global del sistema.
 */
class AdminController extends Controller
{
    /**
     * Endpoint de resumen para panel admin (placeholder de dashboard).
     */
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard del administrador.',
        ]);
    }

    /**
     * Crea un gimnasio nuevo.
     */
    public function createGym(Request $request): JsonResponse
    {
        $gimnasio = Gimnasio::create($request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono'  => ['required', 'string', 'max:20'],
        ]));

        return response()->json([
            'message' => 'Gimnasio creado correctamente.',
            'data'    => $gimnasio,
        ], 201);
    }

    /**
     * Lista todos los gimnasios.
     */
    public function getGym(): JsonResponse
    {
        $gimnasios = Gimnasio::query()->orderBy('id')->get();

        return response()->json([
            'message' => 'Listado de gimnasios.',
            'data' => $gimnasios,
        ]);
    }

    /**
     * Muestra un gimnasio concreto por id.
     */
    public function showGym(int $id): JsonResponse
    {
        $gimnasio = Gimnasio::query()->findOrFail($id);

        return response()->json([
            'message' => 'Gimnasio encontrado.',
            'data' => $gimnasio,
        ]);
    }

    /**
     * Elimina un gimnasio.
     */
    public function destroyGym(int $id): JsonResponse
    {
        $gimnasio = Gimnasio::query()->findOrFail($id);
        $gimnasio->delete();

        return response()->json([
            'message' => 'Gimnasio eliminado correctamente.',
        ]);
    }

    /**
     * Actualiza un gimnasio existente.
     */
    public function updateGym(Request $request, int $id): JsonResponse
    {
        $gimnasio = Gimnasio::query()->findOrFail($id);

        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono'  => ['required', 'string', 'max:20'],
        ]);

        $gimnasio->update($data);

        return response()->json([
            'message' => 'Gimnasio actualizado correctamente.',
            'data' => $gimnasio->fresh(),
        ]);
    }

    /**
     * Lista usuarios con relación de gimnasio (uso de administración).
     */
    public function getUsers(): JsonResponse
    {
        $usuarios = Usuario::query()->with('gimnasio')->orderBy('id')->get();
        return response()->json($usuarios);
    }

    /**
     * Cambia el rol de un usuario.
     */
    public function updateUserRole(Request $request, $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'rol' => ['required', 'string', 'in:admin,usuario'],
        ]);

        $usuario->update($data);

        return response()->json([
            'message' => 'Rol del usuario actualizado.',
            'data' => $usuario->fresh()->load('gimnasio'),
        ]);
    }

    /**
     * Reasigna el gimnasio de un usuario y registra fecha de cambio.
     */
    public function updateUserGym(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::query()->findOrFail($id);

        $data = $request->validate([
            'gimnasio_id' => ['nullable', 'integer', 'exists:gimnasios,id'],
        ]);

        if ((int) $usuario->gimnasio_id !== (int) $data['gimnasio_id']) {
            $data['gimnasio_cambiado_en'] = now();
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Gimnasio del usuario actualizado.',
            'data' => $usuario->fresh()->load('gimnasio'),
        ]);
    }

    /**
     * Lista completa de reservas para vista administrativa.
     */
    public function getReservations(): JsonResponse
    {
        $reservas = Reserva::query()
            ->with(['usuario', 'maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }
}
