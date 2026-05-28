<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MachineImagesEndpointTest extends TestCase
{
    public function test_lista_imagenes_disponibles_para_frontend(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/test-avatar-1.jpg', 'contenido-falso');

        $response = $this->getJson('/api/avatar-images');

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('images.0.filename', 'test-avatar-1.jpg')
            ->assertJsonStructure([
                'count',
                'images' => [
                    ['filename', 'url'],
                ],
            ]);
    }

    public function test_devuelve_404_si_la_imagen_no_existe(): void
    {
        Storage::fake('public');

        $response = $this->get('/api/avatar-images/no-existe.jpg');

        $response->assertNotFound();
    }
}
