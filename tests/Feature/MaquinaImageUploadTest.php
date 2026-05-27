<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MaquinaImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_subir_un_archivo(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $token = JWTAuth::fromUser($admin);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/admin/uploads', [
                'directory' => 'machines',
                'file' => UploadedFile::fake()->create('maquina.jpg', 120, 'image/jpeg'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.disk', 'public')
            ->assertJsonPath('data.original_name', 'maquina.jpg');

        Storage::disk('public')->assertExists($response->json('data.path'));
    }

    public function test_admin_puede_crear_una_maquina_con_imagen(): void
    {
        $admin = $this->createAdmin();
        $token = JWTAuth::fromUser($admin);
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
                'imagen' => 'machines/eliptica-4.jpg',
                'activa' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.imagen', 'machines/eliptica-4.jpg')
            ->assertJsonPath('data.imagen_url', Storage::disk('public')->url('machines/eliptica-4.jpg'));

        $this->assertDatabaseHas('maquinas', [
            'gimnasio_id' => $gimnasio->id,
            'nombre' => 'Eliptica 4',
            'imagen' => 'machines/eliptica-4.jpg',
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
