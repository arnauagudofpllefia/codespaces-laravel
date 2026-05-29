<?php

namespace App\Http\Controllers;

use App\Models\Gimnasio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD básico de gimnasios.
 */
class GimnasiosController extends Controller
{

    /**
     * Lista gimnasios ordenados por id.
     */
    public function index(): JsonResponse
    {
        $gimnasios = Gimnasio::query()->orderBy('id')->get();

        return response()->json($gimnasios);
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
     * Crea un gimnasio.
     */
    public function store(Request $request): JsonResponse
    {
        $gimnasio = Gimnasio::create($this->validatedData($request));

        return response()->json([
            'message' => 'Gimnasio creado correctamente.',
            'data' => $gimnasio,
        ], 201);
    }


    /**
     * Devuelve un gimnasio por route-model binding.
     */
    public function show(Gimnasio $gimnasio): JsonResponse
    {
        return response()->json($gimnasio);
    }


    /**
     * Endpoint no utilizado en API REST (se mantiene por compatibilidad de resource).
     */
    public function edit(Gimnasio $gimnasio): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    /**
     * Actualiza un gimnasio existente.
     */
    public function update(Request $request, Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->update($this->validatedData($request));

        return response()->json([
            'message' => 'Gimnasio actualizado correctamente.',
            'data' => $gimnasio->fresh(),
        ]);
    }

    /**
     * Elimina un gimnasio.
     */
    public function destroy(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->delete();

        return response()->json([
            'message' => 'Gimnasio eliminado correctamente.',
        ]);
    }

    /**
     * Reglas de validación compartidas entre creación y actualización.
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
        ]);
    }
}
