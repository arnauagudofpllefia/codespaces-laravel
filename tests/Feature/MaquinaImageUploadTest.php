<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MaquinaImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_crear_una_maquina_con_imagen(): void
    {
        $admin = $this->createAdmin();
        $token = JWTAuth::fromUser($admin);
        $imageUrl = 'https://cdn.example.com/maquinas/eliptica-4.jpg';
        $gimnasio = Gimnasio::create([
            'nombre' => 'Gym Imagen',
            'direccion' => 'Calle Foto 10',
            'telefono' => '600123123',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/machines', [
                'gimnasio_id' => $gimnasio->id,
                'nombre' => 'Eliptica 4',
                'descripcion' => 'Con pantalla',
                'imagen' => $imageUrl,
                'activa' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('imagen', $imageUrl)
            ->assertJsonPath('imagen_url', $imageUrl)
            ->assertJsonPath('image_url', $imageUrl);

        $this->assertDatabaseHas('maquinas', [
            'gimnasio_id' => $gimnasio->id,
            'nombre' => 'Eliptica 4',
            'imagen' => $imageUrl,
        ]);
    }

    public function test_admin_no_puede_crear_una_maquina_con_archivo_en_campo_archivo(): void
    {
        $admin = $this->createAdmin();
        $token = JWTAuth::fromUser($admin);
        $gimnasio = Gimnasio::create([
            'nombre' => 'Gym Archivo',
            'direccion' => 'Calle Archivo 9',
            'telefono' => '600000000',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/machines', [
                'gimnasio_id' => $gimnasio->id,
                'nombre' => 'Remo 2',
                'descripcion' => 'Con resistencia magnetica',
                'archivo' => 'cualquier-valor',
                'activa' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('archivo');

        $this->assertDatabaseMissing('maquinas', [
            'gimnasio_id' => $gimnasio->id,
            'nombre' => 'Remo 2',
        ]);
    }

    private function createAdmin(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Admin',
            'email' => 'admin@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'admin',
        ]);
    }
}
