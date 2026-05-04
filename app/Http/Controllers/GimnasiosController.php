<?php

namespace App\Http\Controllers;

use App\Models\Gimnasio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GimnasiosController extends Controller
{

    public function index(): JsonResponse
    {
        $gimnasios = Gimnasio::query()->orderBy('id')->get();

        return response()->json($gimnasios);
    }


    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function store(Request $request): JsonResponse
    {
        $gimnasio = Gimnasio::create($this->validatedData($request));

        return response()->json([
            'message' => 'Gimnasio creado correctamente.',
            'data' => $gimnasio,
        ], 201);
    }


    public function show(Gimnasio $gimnasio): JsonResponse
    {
        return response()->json($gimnasio);
    }


    public function edit(Gimnasio $gimnasio): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    public function update(Request $request, Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->update($this->validatedData($request));

        return response()->json([
            'message' => 'Gimnasio actualizado correctamente.',
            'data' => $gimnasio->fresh(),
        ]);
    }

    
    public function destroy(Gimnasio $gimnasio): JsonResponse
    {
        $gimnasio->delete();

        return response()->json([
            'message' => 'Gimnasio eliminado correctamente.',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:30'],
        ]);
    }
}
