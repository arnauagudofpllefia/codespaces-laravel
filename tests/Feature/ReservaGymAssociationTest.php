<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Maquina;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReservaGymAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserva_usa_el_gimnasio_de_la_maquina_aunque_no_se_envie_en_la_peticion(): void
    {
        $gimnasio = Gimnasio::create([
            'nombre' => 'Gym Centro',
            'direccion' => 'Calle Uno 123',
            'telefono' => '600111222',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'usuario',
            'gimnasio_id' => $gimnasio->id,
            'gimnasio_cambiado_en' => now(),
        ]);

        $maquina = Maquina::create([
            'gimnasio_id' => $gimnasio->id,
            'nombre' => 'Cinta 1',
            'descripcion' => 'Cinta principal',
            'activa' => true,
        ]);

        $token = JWTAuth::fromUser($usuario);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'usuario_id' => $usuario->id,
                'maquina_id' => $maquina->id,
                'hora_inicio' => now()->addHour()->toIso8601String(),
                'hora_fin' => now()->addHours(2)->toIso8601String(),
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reservas', [
            'usuario_id' => $usuario->id,
            'maquina_id' => $maquina->id,
            'gimnasio_id' => $gimnasio->id,
        ]);
    }

    public function test_reserva_rechaza_un_gimnasio_distinto_al_de_la_maquina(): void
    {
        $gimnasioMaquina = Gimnasio::create([
            'nombre' => 'Gym Norte',
            'direccion' => 'Avenida Norte 10',
            'telefono' => '600333444',
        ]);

        $gimnasioIncorrecto = Gimnasio::create([
            'nombre' => 'Gym Sur',
            'direccion' => 'Avenida Sur 20',
            'telefono' => '600555666',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Luis',
            'email' => 'luis@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'usuario',
            'gimnasio_id' => $gimnasioMaquina->id,
            'gimnasio_cambiado_en' => now(),
        ]);

        $maquina = Maquina::create([
            'gimnasio_id' => $gimnasioMaquina->id,
            'nombre' => 'Bici 2',
            'descripcion' => 'Bicicleta estatica',
            'activa' => true,
        ]);

        $token = JWTAuth::fromUser($usuario);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'usuario_id' => $usuario->id,
                'maquina_id' => $maquina->id,
                'gimnasio_id' => $gimnasioIncorrecto->id,
                'hora_inicio' => now()->addHour()->toIso8601String(),
                'hora_fin' => now()->addHours(2)->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('gimnasio_id');

        $this->assertDatabaseMissing('reservas', [
            'usuario_id' => $usuario->id,
            'maquina_id' => $maquina->id,
            'gimnasio_id' => $gimnasioIncorrecto->id,
        ]);
    }

    public function test_usuario_no_puede_reservar_una_maquina_de_otro_gimnasio(): void
    {
        $gimnasioUsuario = Gimnasio::create([
            'nombre' => 'Gym Este',
            'direccion' => 'Calle Este 45',
            'telefono' => '600777888',
        ]);

        $gimnasioMaquina = Gimnasio::create([
            'nombre' => 'Gym Oeste',
            'direccion' => 'Calle Oeste 67',
            'telefono' => '600999000',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Marta',
            'email' => 'marta@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'usuario',
            'gimnasio_id' => $gimnasioUsuario->id,
            'gimnasio_cambiado_en' => now(),
        ]);

        $maquina = Maquina::create([
            'gimnasio_id' => $gimnasioMaquina->id,
            'nombre' => 'Remo 3',
            'descripcion' => 'Maquina de remo',
            'activa' => true,
        ]);

        $token = JWTAuth::fromUser($usuario);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'usuario_id' => 999999,
                'maquina_id' => $maquina->id,
                'hora_inicio' => now()->addHour()->toIso8601String(),
                'hora_fin' => now()->addHours(2)->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('maquina_id');

        $this->assertDatabaseCount('reservas', 0);
    }
}
