<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de consulta y gestión básica de máquinas.
 */
class MaquinasController extends Controller
{
    /**
     * Lista máquinas con su gimnasio asociado.
     */
    public function index(): JsonResponse
    {
        $maquinas = Maquina::query()
            ->with('gimnasio')
            ->orderBy('id')
            ->get();

        return response()->json($maquinas);
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
     * Crea una máquina validando datos y normalizando el campo de imagen.
     */
    public function store(Request $request): JsonResponse
    {
        $maquina = Maquina::create($this->validatedData($request));

        return response()->json([
            'message' => 'Maquina creada correctamente.',
            'data' => $maquina->load('gimnasio'),
        ], 201);
    }


    /**
     * Muestra una máquina concreta por route-model binding.
     */
    public function show(Maquina $maquina): JsonResponse
    {
        return response()->json($maquina->load('gimnasio'));
    }


    /**
     * Endpoint no utilizado en API REST (se mantiene por compatibilidad de resource).
     */
    public function edit(Maquina $maquina): JsonResponse
    {
        return response()->json([
            'message' => 'Este endpoint no está disponible en la API.',
        ], 405);
    }


    /**
     * Actualiza una máquina y devuelve su estado final con relaciones.
     */
    public function update(Request $request, Maquina $maquina): JsonResponse
    {
        $maquina->update($this->validatedData($request));

        return response()->json([
            'message' => 'Maquina actualizada correctamente.',
            'data' => $maquina->fresh()->load('gimnasio'),
        ]);
    }


    /**
     * Elimina una máquina.
     */
    public function destroy(Maquina $maquina): JsonResponse
    {
        $maquina->delete();

        return response()->json([
            'message' => 'Maquina eliminada correctamente.',
        ]);
    }

    /**
     * Punto de extensión para disponibilidad por slots (actualmente vacío).
     */
    public function getSlots(Maquina $maquina): JsonResponse
    {

        return response()->json([
            'maquina_id' => $maquina->id,
            'slots' => [],
        ]);
    }

    /**
     * Valida payload y consolida aliases de imagen en el campo canonical `imagen`.
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'gimnasio_id' => ['required', 'integer', 'exists:gimnasios,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'imagen_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'image_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'archivo' => ['prohibited'],
            'file' => ['prohibited'],
            'imagen_archivo' => ['prohibited'],
            'activa' => ['sometimes', 'boolean'],
        ]);

        // Se aceptan imagen_url/image_url por compatibilidad, pero se guarda siempre como `imagen`.
        if (! array_key_exists('imagen', $data)) {
            $data['imagen'] = $data['imagen_url'] ?? $data['image_url'] ?? null;
        }

        unset($data['imagen_url'], $data['image_url']);

        return $data;
    }
}
