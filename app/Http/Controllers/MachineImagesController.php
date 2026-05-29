<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Publica avatares almacenados en storage/public/avatars para consumo del frontend.
 */
class MachineImagesController extends Controller
{
    /**
     * Lista avatares disponibles para consumo desde el frontend.
     */
    public function index(): JsonResponse
    {
        $images = collect(Storage::disk('public')->files('avatars'))
            ->filter(fn (string $path) => (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $path))
            ->map(function (string $path): array {
                $filename = basename($path);

                return [
                    'filename' => $filename,
                    'url' => route('avatar-images.show', ['filename' => $filename]),
                ];
            })
            ->values();

        return response()->json([
            'count' => $images->count(),
            'images' => $images,
        ]);
    }

    /**
     * Devuelve una imagen concreta por su nombre de archivo.
     */
    public function show(string $filename): StreamedResponse|Response
    {
        // Sanitiza el nombre para evitar path traversal.
        if (! preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
            abort(404);
        }

        $path = 'avatars/' . $filename;
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($path) ?? 'application/octet-stream';
        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ];

        // Stream para no cargar el archivo completo en memoria.
        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}
