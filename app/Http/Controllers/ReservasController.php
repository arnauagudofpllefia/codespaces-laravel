<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $datos = $this->resolveValidatedData($request);

        $hayConflicto = Reserva::query()
            ->where('maquina_id', $datos['maquina_id'])
            ->where('estado', 'activa')
            ->where(function ($q) use ($datos) {
                $q->where('hora_inicio', '<', $datos['hora_fin'])
                    ->where('hora_fin', '>', $datos['hora_inicio']);
            })
            ->exists();

        if ($hayConflicto) {
            return response()->json([
                'message' => 'La máquina ya tiene una reserva activa en ese tramo horario.',
            ], 422);
        }

        $reserva = Reserva::create($datos);

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
        $datos = $this->resolveValidatedData($request);

        $hayConflicto = Reserva::query()
            ->where('maquina_id', $datos['maquina_id'])
            ->where('estado', 'activa')
            ->where('id', '!=', $reserva->id)
            ->where(function ($q) use ($datos) {
                $q->where('hora_inicio', '<', $datos['hora_fin'])
                    ->where('hora_fin', '>', $datos['hora_inicio']);
            })
            ->exists();

        if ($hayConflicto) {
            return response()->json([
                'message' => 'La máquina ya tiene una reserva activa en ese tramo horario.',
            ], 422);
        }

        $reserva->update($datos);

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

    public function getMachineReservations(int $id): JsonResponse
    {
        Maquina::query()->findOrFail($id);

        $reservas = Reserva::query()
            ->where('maquina_id', $id)
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'estado'])
            ->map(fn (Reserva $reserva) => [
                'hora' => optional($reserva->hora_inicio)->toIso8601String(),
                'estado' => $reserva->estado,
                'plazas' => 1,
            ])
            ->values();

        return response()->json([
            'maquina_id' => $id,
            'reservas' => $reservas,
        ]);
    }

    private function validatedData(Request $request): array
    {
        $usuarioAutenticado = $request->user();
        $reglasUsuarioId = $usuarioAutenticado !== null && $usuarioAutenticado->rol !== 'admin'
            ? ['sometimes', 'nullable', 'integer']
            : ['required', 'integer', 'exists:usuarios,id'];

        return $request->validate([
            'usuario_id' => $reglasUsuarioId,
            'maquina_id' => ['required', 'integer', 'exists:maquinas,id'],
            'gimnasio_id' => ['sometimes', 'nullable', 'integer', 'exists:gimnasios,id'],
            'hora_inicio' => ['required', 'date'],
            'hora_fin' => ['required', 'date', 'after:hora_inicio'],
            'estado' => ['sometimes', 'string', 'in:activa,cancelada,completada'],
        ]);
    }

    private function resolveValidatedData(Request $request): array
    {
        $datos = $this->validatedData($request);
        $maquina = Maquina::query()->findOrFail($datos['maquina_id']);
        $usuarioAutenticado = $request->user();

        if ($usuarioAutenticado !== null && $usuarioAutenticado->rol !== 'admin') {
            if ($usuarioAutenticado->gimnasio_id === null) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'Debes tener un gimnasio asignado para crear reservas.',
                ]);
            }

            if ((int) $usuarioAutenticado->gimnasio_id !== (int) $maquina->gimnasio_id) {
                throw ValidationException::withMessages([
                    'maquina_id' => 'Solo puedes reservar maquinas de tu propio gimnasio.',
                ]);
            }

            $datos['usuario_id'] = $usuarioAutenticado->id;
        }

        if (
            array_key_exists('gimnasio_id', $datos)
            && $datos['gimnasio_id'] !== null
            && (int) $datos['gimnasio_id'] !== (int) $maquina->gimnasio_id
        ) {
            throw ValidationException::withMessages([
                'gimnasio_id' => 'La reserva debe pertenecer al mismo gimnasio que la maquina seleccionada.',
            ]);
        }

        $datos['gimnasio_id'] = $maquina->gimnasio_id;

        return $datos;
    }
}
