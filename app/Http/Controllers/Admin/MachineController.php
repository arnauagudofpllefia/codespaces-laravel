<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $machine = Maquina::create($data);

        return response()->json($this->transform($machine), 201);
    }

    public function update(Request $request, Maquina $maquina): JsonResponse
    {
        $data = $this->validateMachine($request, true);

        $maquina->update($data);

        return response()->json($this->transform($maquina->fresh()));
    }

    public function destroy(Maquina $maquina): JsonResponse
    {
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
            'imagen' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'imagen_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'image_url' => ['sometimes', 'nullable', 'string', 'url', 'max:2048'],
            'archivo' => ['prohibited'],
            'file' => ['prohibited'],
            'imagen_archivo' => ['prohibited'],
        ]);

        if (! array_key_exists('imagen', $data)) {
            $data['imagen'] = $data['imagen_url'] ?? $data['image_url'] ?? null;
        }

        unset($data['imagen_url'], $data['image_url']);

        if (array_key_exists('estado', $data) && ! array_key_exists('activa', $data)) {
            $estado = mb_strtolower((string) $data['estado']);
            $data['activa'] = in_array($estado, ['activa', 'activo', 'disponible', 'enabled', 'true', '1'], true);
        }

        return $data;
    }

    private function transform(Maquina $machine): array
    {
        $imagePath = $machine->imagen;
        $imageUrl = $imagePath;

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
