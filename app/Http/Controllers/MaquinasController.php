<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class MaquinasController extends Controller
{
    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }


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

    public function getSlots(Maquina $maquina): JsonResponse
    {

        return response()->json([
            'maquina_id' => $maquina->id,
            'slots' => [],
        ]);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'gimnasio_id' => ['required', 'integer', 'exists:gimnasios,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'imagen_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'image_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'archivo' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
            'file' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
            'imagen_archivo' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
            'activa' => ['sometimes', 'boolean'],
        ]);

        $uploadedFile = $this->extractImageFile($request);

        if ($uploadedFile !== null) {
            $upload = $this->fileUploadService->store($uploadedFile, 'machines');
            $data['imagen'] = $upload['path'];
        } elseif (! array_key_exists('imagen', $data)) {
            $data['imagen'] = $data['imagen_url'] ?? $data['image_url'] ?? null;
        }

        unset($data['archivo'], $data['file'], $data['imagen_archivo'], $data['imagen_url'], $data['image_url']);

        return $data;
    }

    private function extractImageFile(Request $request): ?UploadedFile
    {
        foreach (['archivo', 'file', 'imagen_archivo'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }
}
