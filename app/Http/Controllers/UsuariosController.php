<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function index(): JsonResponse
    {
        $usuarios = Usuario::query()->orderBy('id')->get();

        return response()->json($usuarios);
    }

    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['contrasena'] = Hash::make($data['contrasena']);
        $usuario = Usuario::create($data);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $usuario,
        ], 201);
    }

    public function show(Usuario $usuario): JsonResponse
    {
        return response()->json($usuario);
    }

    public function edit(Usuario $usuario): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }

    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        $data = $this->validatedDataUpdate($request, $usuario);

        if (isset($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $usuario->fresh(),
        ]);
    }

    public function destroy(Usuario $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:usuarios,email'],
            'contrasena' => ['required', 'string', 'min:6'],
            'rol' => ['sometimes', 'string', 'in:admin,usuario'],
        ]);
    }

    private function validatedDataUpdate(Request $request, Usuario $usuario): array
    {
        return $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:usuarios,email,' . $usuario->id],
            'contrasena' => ['sometimes', 'string', 'min:6'],
            'rol' => ['sometimes', 'string', 'in:admin,usuario'],
        ]);
    }
}
