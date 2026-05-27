<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function store(UploadedFile $file, string $directory = 'uploads', string $disk = 'public'): array
    {
        $cleanDirectory = trim($directory, '/');
        $extension = $file->getClientOriginalExtension();
        $filename = (string) Str::uuid();

        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        $path = $file->storeAs($cleanDirectory, $filename, $disk);

        return [
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ];
    }
}
