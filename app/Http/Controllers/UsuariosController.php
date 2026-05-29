<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * CRUD de usuarios y edición del perfil del usuario autenticado.
 */
class UsuariosController extends Controller
{
    /**
     * Lista usuarios con su gimnasio (uso administrativo).
     */
    public function index(): JsonResponse
    {
        $usuarios = Usuario::query()
            ->with('gimnasio')
            ->orderBy('id')
            ->get();

        return response()->json($usuarios);
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
     * Crea un usuario y almacena la contraseña en hash.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['contrasena'] = Hash::make($data['contrasena']);

        // Si nace con gimnasio asignado, se registra la fecha para aplicar la regla de 2 semanas.
        if (array_key_exists('gimnasio_id', $data) && $data['gimnasio_id'] !== null) {
            $data['gimnasio_cambiado_en'] = now();
        }
        $usuario = Usuario::create($data);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $usuario->load('gimnasio'),
        ], 201);
    }

    /**
     * Muestra un usuario individual.
     */
    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json($usuario->load('gimnasio'));
    }

    /**
     * Endpoint no utilizado en API REST (se mantiene por compatibilidad de resource).
     */
    public function edit(Usuario $usuario): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }

    /**
     * Actualiza un usuario (flujo administrativo).
     */
    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $data = $this->validatedDataUpdate($request, $usuario);
        $gymChanged = array_key_exists('gimnasio_id', $data) && ((int) $usuario->gimnasio_id !== (int) $data['gimnasio_id']);

        if (isset($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        }

        if ($gymChanged) {
            $data['gimnasio_cambiado_en'] = now();
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $usuario->fresh()->load('gimnasio'),
        ]);
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(Usuario $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Actualiza el perfil propio y limita cambio de gimnasio a una vez cada 2 semanas.
     */
    public function updateAuthenticatedUser(Request $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        $data = $this->validatedDataSelfUpdate($request, $usuario);

        $gymRequested = array_key_exists('gimnasio_id', $data);
        $gymChanged = $gymRequested && ((int) $usuario->gimnasio_id !== (int) $data['gimnasio_id']);

        // Regla de negocio: el usuario no puede cambiar de gimnasio antes de 14 días.
        if ($gymChanged && $usuario->gimnasio_cambiado_en !== null) {
            $nextAllowedChangeAt = $usuario->gimnasio_cambiado_en->copy()->addWeeks(2);

            if ($nextAllowedChangeAt->isFuture()) {
                return response()->json([
                    'message' => 'Debes esperar 2 semanas para volver a cambiar de gimnasio.',
                    'next_allowed_change_at' => $nextAllowedChangeAt->toIso8601String(),
                ], 422);
            }
        }

        if (isset($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        }

        if ($gymChanged) {
            $data['gimnasio_cambiado_en'] = now();
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $usuario->fresh()->load('gimnasio'),
        ]);
    }

    /**
     * Validación de creación (campos obligatorios).
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:usuarios,email'],
            'contrasena' => ['required', 'string', 'min:6'],
            'rol' => ['sometimes', 'string', 'in:admin,usuario'],
            'gimnasio_id' => ['sometimes', 'nullable', 'integer', 'exists:gimnasios,id'],
        ]);
    }

    /**
     * Validación de actualización administrativa (campos opcionales).
     */
    private function validatedDataUpdate(Request $request, Usuario $usuario): array
    {
        return $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:usuarios,email,' . $usuario->id],
            'contrasena' => ['sometimes', 'string', 'min:6'],
            'rol' => ['sometimes', 'string', 'in:admin,usuario'],
            'gimnasio_id' => ['sometimes', 'nullable', 'integer', 'exists:gimnasios,id'],
        ]);
    }

    /**
     * Validación de autoedición: no permite cambiar rol desde el propio perfil.
     */
    private function validatedDataSelfUpdate(Request $request, Usuario $usuario): array
    {
        return $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:usuarios,email,' . $usuario->id],
            'contrasena' => ['sometimes', 'string', 'min:6'],
            'gimnasio_id' => ['sometimes', 'nullable', 'integer', 'exists:gimnasios,id'],
        ]);
    }
}
