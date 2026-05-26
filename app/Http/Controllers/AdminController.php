<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Gimnasio;
use App\Models\Usuario;
use App\Models\Reserva;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'message' => 'Dashboard del administrador.',
        ]);
    }

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

    public function getGym(): JsonResponse
    {
        $gimnasios = Gimnasio::query()->orderBy('id')->get();

        return response()->json([
            'message' => 'Listado de gimnasios.',
            'data' => $gimnasios,
        ]);
    }

    public function showGym(int $id): JsonResponse
    {
        $gimnasio = Gimnasio::query()->findOrFail($id);

        return response()->json([
            'message' => 'Gimnasio encontrado.',
            'data' => $gimnasio,
        ]);
    }

    public function destroyGym(int $id): JsonResponse
    {
        $gimnasio = Gimnasio::query()->findOrFail($id);
        $gimnasio->delete();

        return response()->json([
            'message' => 'Gimnasio eliminado correctamente.',
        ]);
    }

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

    public function getUsers(): JsonResponse
    {
        $usuarios = Usuario::query()->with('gimnasio')->orderBy('id')->get();
        return response()->json($usuarios);
    }

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

    public function updateUserGym(Request $request, int $id): JsonResponse
    {
        $usuario = Usuario::query()->findOrFail($id);

        $data = $request->validate([
            'gimnasio_id' => ['nullable', 'integer', 'exists:gimnasios,id'],
        ]);

        $usuario->update($data);

        return response()->json([
            'message' => 'Gimnasio del usuario actualizado.',
            'data' => $usuario->fresh()->load('gimnasio'),
        ]);
    }

    public function getReservations(): JsonResponse
    {
        $reservas = Reserva::query()
            ->with(['usuario', 'maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }
}
