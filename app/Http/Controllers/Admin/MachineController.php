<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MachineController extends Controller
{
    public function index(): JsonResponse
    {
        $machines = Maquina::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Maquina $machine) => $this->transform($machine));

        return response()->json($machines);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateMachine($request, false);
        $uploaded = $this->handleImageUpload($request);

        if ($uploaded !== null) {
            $data['imagen'] = $uploaded;
        }

        $machine = Maquina::create($data);

        return response()->json($this->transform($machine), 201);
    }

    public function update(Request $request, Maquina $maquina): JsonResponse
    {
        $data = $this->validateMachine($request, true);
        $uploaded = $this->handleImageUpload($request);

        if ($uploaded !== null) {
            $this->deleteOldImage($maquina->imagen);
            $data['imagen'] = $uploaded;
        }

        $maquina->update($data);

        return response()->json($this->transform($maquina->fresh()));
    }

    public function destroy(Maquina $maquina): JsonResponse
    {
        $this->deleteOldImage($maquina->imagen);
        $maquina->delete();

        return response()->json(['message' => 'ok']);
    }

    private function validateMachine(Request $request, bool $isUpdate): array
    {
        $requiredOrSometimes = $isUpdate ? 'sometimes' : 'required';

        $data = $request->validate([
            'nombre' => [$requiredOrSometimes, 'string', 'max:120'],
            'zona' => ['sometimes', 'nullable', 'string', 'max:60'],
            'estado' => ['sometimes', 'nullable', 'string', 'max:30'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'gimnasio_id' => [$requiredOrSometimes, 'integer', 'exists:gimnasios,id'],
            'activa' => ['sometimes', 'boolean'],
            'imagen' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'archivo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'file' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'imagen_archivo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        unset($data['imagen'], $data['archivo'], $data['file'], $data['imagen_archivo']);

        if (array_key_exists('estado', $data) && ! array_key_exists('activa', $data)) {
            $estado = mb_strtolower((string) $data['estado']);
            $data['activa'] = in_array($estado, ['activa', 'activo', 'disponible', 'enabled', 'true', '1'], true);
        }

        return $data;
    }

    private function handleImageUpload(Request $request): ?string
    {
        $file = $this->extractImageFile($request);

        if ($file === null) {
            return null;
        }

        return $file->store('machines', 'public');
    }

    private function extractImageFile(Request $request): ?UploadedFile
    {
        foreach (['imagen', 'archivo', 'file', 'imagen_archivo'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }

    private function deleteOldImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function transform(Maquina $machine): array
    {
        $imagePath = $machine->imagen;
        $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;

        return [
            'id' => $machine->id,
            'nombre' => $machine->nombre,
            'zona' => null,
            'estado' => $machine->activa ? 'activa' : 'inactiva',
            'descripcion' => $machine->descripcion,
            'gimnasio_id' => $machine->gimnasio_id,
            'activa' => (bool) $machine->activa,
            'imagen' => $imagePath,
            'imagen_url' => $imageUrl,
            'image_url' => $imageUrl,
        ];
    }
}
