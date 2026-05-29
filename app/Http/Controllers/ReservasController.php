<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Models\Notificacion;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona reservas de máquinas con validaciones de solapamiento y reglas por rol.
 */
class ReservasController extends Controller
{

    /**
     * Lista global de reservas (uso administrativo).
     */
    public function index(): JsonResponse
    {
        $reservas = Reserva::query()
            ->with(['usuario', 'maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }


    /**
     * Endpoint no utilizado en API REST (se mantiene por compatibilidad de resource).
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    /**
     * Crea una reserva y notifica al usuario cuando se confirma.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $this->resolveValidatedData($request);

        // Evita reservas activas solapadas para la misma máquina en el mismo tramo horario.
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
        $reserva->load(['maquina']);

        // Notificación in-app para reflejar la creación en la bandeja del usuario.
        Notificacion::create([
            'user_id' => $reserva->usuario_id,
            'type' => 'reservation_created',
            'title' => 'Reserva creada',
            'message' => 'Reserva confirmada para ' . ($reserva->maquina->nombre ?? 'la máquina') . ' el ' . ($reserva->hora_inicio?->format('d/m/Y H:i') ?? ''),
            'data' => [
                'reservation_id' => $reserva->id,
                'machine_id' => $reserva->maquina_id,
                'gym_id' => $reserva->gimnasio_id,
                'start_time' => optional($reserva->hora_inicio)->toIso8601String(),
                'end_time' => optional($reserva->hora_fin)->toIso8601String(),
            ],
            'channel' => 'in_app',
            'delivered_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reserva creada correctamente.',
            'data' => $reserva->load(['usuario', 'maquina', 'gimnasio']),
        ], 201);
    }


    /**
     * Devuelve el detalle de una reserva con sus relaciones principales.
     */
    public function show(Reserva $reserva): JsonResponse
    {
        return response()->json($reserva->load(['usuario', 'maquina', 'gimnasio']));
    }


    /**
     * Endpoint no utilizado en API REST (se mantiene por compatibilidad de resource).
     */
    public function edit(Reserva $reserva): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    /**
     * Actualiza una reserva manteniendo las mismas reglas de conflicto que en creación.
     */
    public function update(Request $request, Reserva $reserva): JsonResponse
    {
        $datos = $this->resolveValidatedData($request);

        // Ignora la propia reserva al comprobar colisiones horarias.
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


    /**
     * Elimina una reserva y deja trazabilidad mediante notificación al usuario.
     */
    public function destroy(Reserva $reserva): JsonResponse
    {
        $reserva->loadMissing('maquina');

        // Notificación de cancelación para que el usuario vea el cambio de estado.
        Notificacion::create([
            'user_id' => $reserva->usuario_id,
            'type' => 'reservation_cancelled',
            'title' => 'Reserva cancelada',
            'message' => 'Reserva cancelada para ' . ($reserva->maquina->nombre ?? 'la máquina') . ' que estaba programada para ' . ($reserva->hora_inicio?->format('d/m/Y H:i') ?? ''),
            'data' => [
                'reservation_id' => $reserva->id,
                'machine_id' => $reserva->maquina_id,
                'gym_id' => $reserva->gimnasio_id,
                'start_time' => optional($reserva->hora_inicio)->toIso8601String(),
                'end_time' => optional($reserva->hora_fin)->toIso8601String(),
            ],
            'channel' => 'in_app',
            'delivered_at' => now(),
        ]);

        $reserva->delete();

        return response()->json([
            'message' => 'Reserva eliminada correctamente.',
        ]);
    }

    /**
     * Devuelve solo las reservas del usuario autenticado.
     */
    public function getMyReservations(Request $request): JsonResponse
    {
        $reservas = Reserva::query()
            ->where('usuario_id', $request->user()->id)
            ->with(['maquina', 'gimnasio'])
            ->orderBy('id')
            ->get();

        return response()->json($reservas);
    }

    /**
     * Lista intervalos ya reservados de una máquina para construir calendarios en frontend.
     */
    public function getMachineReservations(int $id): JsonResponse
    {
        Maquina::query()->findOrFail($id);

        $reservas = Reserva::query()
            ->where('maquina_id', $id)
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin', 'estado'])
            ->map(fn (Reserva $reserva) => [
                'hora' => optional($reserva->hora_inicio)->toIso8601String(),
                'hora_inicio' => optional($reserva->hora_inicio)->toIso8601String(),
                'hora_fin' => optional($reserva->hora_fin)->toIso8601String(),
                'end_time' => optional($reserva->hora_fin)->toIso8601String(),
                'estado' => $reserva->estado,
                'plazas' => 1,
            ])
            ->values();

        return response()->json([
            'maquina_id' => $id,
            'reservas' => $reservas,
        ]);
    }

    /**
     * Valida estructura/base del payload; reglas finas se aplican en resolveValidatedData.
     */
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

    /**
     * Aplica reglas de negocio:
     * - Usuarios normales solo reservan para sí mismos.
     * - Solo pueden reservar máquinas de su gimnasio.
     * - El gimnasio final siempre se toma de la máquina seleccionada.
     */
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
