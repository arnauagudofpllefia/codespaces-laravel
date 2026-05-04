<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaquinasController extends Controller
{

    public function index(): JsonResponse
    {
        $maquinas = Maquina::query()
            ->with('gimnasio')
            ->orderBy('id')
            ->get();

        return response()->json($maquinas);
    }


    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function store(Request $request): JsonResponse
    {
        $maquina = Maquina::create($this->validatedData($request));

        return response()->json([
            'message' => 'Maquina creada correctamente.',
            'data' => $maquina->load('gimnasio'),
        ], 201);
    }


    public function show(Maquina $maquina): JsonResponse
    {
        return response()->json($maquina->load('gimnasio'));
    }


    public function edit(Maquina $maquina): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function update(Request $request, Maquina $maquina): JsonResponse
    {
        $maquina->update($this->validatedData($request));

        return response()->json([
            'message' => 'Maquina actualizada correctamente.',
            'data' => $maquina->fresh()->load('gimnasio'),
        ]);
    }

    
    public function destroy(Maquina $maquina): JsonResponse
    {
        $maquina->delete();

        return response()->json([
            'message' => 'Maquina eliminada correctamente.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'gimnasio_id' => ['required', 'integer', 'exists:gimnasios,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['sometimes', 'boolean'],
        ]);
    }
}
