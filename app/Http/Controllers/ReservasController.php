<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservasController extends Controller
{

    public function index(): JsonResponse
    {
        $reservas = Reserva::query()
            ->with(['usuario', 'maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }


    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function store(Request $request): JsonResponse
    {
        $reserva = Reserva::create($this->validatedData($request));

        return response()->json([
            'message' => 'Reserva creada correctamente.',
            'data' => $reserva->load(['usuario', 'maquina', 'gimnasio']),
        ], 201);
    }


    public function show(Reserva $reserva): JsonResponse
    {
        return response()->json($reserva->load(['usuario', 'maquina', 'gimnasio']));
    }


    public function edit(Reserva $reserva): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function update(Request $request, Reserva $reserva): JsonResponse
    {
        $reserva->update($this->validatedData($request));

        return response()->json([
            'message' => 'Reserva actualizada correctamente.',
            'data' => $reserva->fresh()->load(['usuario', 'maquina', 'gimnasio']),
        ]);
    }


    public function destroy(Reserva $reserva): JsonResponse
    {
        $reserva->delete();

        return response()->json([
            'message' => 'Reserva eliminada correctamente.',
        ]);
    }

    public function getMyReservations(Request $request): JsonResponse
    {
        $reservas = Reserva::query()
            ->where('usuario_id', $request->user()->id)
            ->with(['maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'maquina_id' => ['required', 'integer', 'exists:maquinas,id'],
            'gimnasio_id' => ['required', 'integer', 'exists:gimnasios,id'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['required', 'date', 'after:hora_inicio'],
            'estado' => ['sometimes', 'string', 'in:activa,cancelada,completada'],
        ]);
    }
}
