<?php

namespace App\Http\Controllers;

use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilesController extends Controller
{
    public function __construct(private readonly FileUploadService $fileUploadService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'directory' => ['sometimes', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\/-]+$/'],
        ]);

        $upload = $this->fileUploadService->store(
            $request->file('file'),
            $data['directory'] ?? 'uploads'
        );

        return response()->json([
            'message' => 'Archivo subido correctamente.',
            'data' => $upload,
        ], 201);
    }
}
