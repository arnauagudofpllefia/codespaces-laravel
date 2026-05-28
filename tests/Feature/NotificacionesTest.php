<?php

namespace Tests\Feature;

use App\Models\Gimnasio;
use App\Models\Maquina;
use App\Models\Notificacion;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_notificacion_al_crear_reserva(): void
    {
        [$usuario, $maquina] = $this->crearUsuarioYMaquina();
        $token = JWTAuth::fromUser($usuario);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservations', [
                'maquina_id' => $maquina->id,
                'hora_inicio' => now()->addHour()->toIso8601String(),
                'hora_fin' => now()->addHours(2)->toIso8601String(),
            ]);

        $response->assertCreated();

        $notificacion = Notificacion::query()->where('user_id', $usuario->id)->latest('id')->first();

        $this->assertNotNull($notificacion);
        $this->assertSame('reservation_created', $notificacion->type);
        $this->assertSame('in_app', $notificacion->channel);
        $this->assertNotNull($notificacion->delivered_at);
        $this->assertArrayHasKey('reservation_id', $notificacion->data ?? []);
    }

    public function test_crea_notificacion_al_cancelar_reserva(): void
    {
        [$usuario, $maquina, $gimnasio] = $this->crearUsuarioYMaquina();

        $reserva = Reserva::create([
            'usuario_id' => $usuario->id,
            'maquina_id' => $maquina->id,
            'gimnasio_id' => $gimnasio->id,
            'hora_inicio' => now()->addHour(),
            'hora_fin' => now()->addHours(2),
            'estado' => 'activa',
        ]);

        $token = JWTAuth::fromUser($usuario);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/reservations/' . $reserva->id);

        $response->assertOk();

        $notificacion = Notificacion::query()->where('user_id', $usuario->id)->latest('id')->first();

        $this->assertNotNull($notificacion);
        $this->assertSame('reservation_cancelled', $notificacion->type);
        $this->assertSame($reserva->id, $notificacion->data['reservation_id'] ?? null);
    }

    public function test_lista_y_marca_notificaciones_como_leidas(): void
    {
        [$usuario] = $this->crearUsuarioYMaquina();
        $otroUsuario = Usuario::create([
            'nombre' => 'Otro',
            'email' => 'otro@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'usuario',
            'gimnasio_id' => $usuario->gimnasio_id,
            'gimnasio_cambiado_en' => now(),
        ]);

        $n1 = Notificacion::create([
            'user_id' => $usuario->id,
            'type' => 'system',
            'title' => 'Sistema 1',
            'message' => 'Mensaje 1',
            'data' => ['foo' => 'bar'],
            'delivered_at' => now(),
        ]);

        Notificacion::create([
            'user_id' => $usuario->id,
            'type' => 'system',
            'title' => 'Sistema 2',
            'message' => 'Mensaje 2',
            'data' => ['foo' => 'baz'],
            'read_at' => now(),
            'delivered_at' => now(),
        ]);

        Notificacion::create([
            'user_id' => $otroUsuario->id,
            'type' => 'system',
            'title' => 'Sistema 3',
            'message' => 'Mensaje 3',
            'delivered_at' => now(),
        ]);

        $token = JWTAuth::fromUser($usuario);

        $listResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications');

        $listResponse->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('unread_count', 1);

        $countResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/notifications/unread-count');

        $countResponse->assertOk()->assertJsonPath('unread_count', 1);

        $markOneResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/notifications/' . $n1->id . '/read');

        $markOneResponse->assertOk();

        $markAllResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson('/api/notifications/read-all');

        $markAllResponse->assertOk();

        $this->assertDatabaseMissing('notificaciones', [
            'user_id' => $usuario->id,
            'read_at' => null,
        ]);
    }

    /**
     * @return array{0: Usuario, 1: Maquina, 2: Gimnasio}
     */
    private function crearUsuarioYMaquina(): array
    {
        $gimnasio = Gimnasio::create([
            'nombre' => 'Gym Notif',
            'direccion' => 'Calle Notif 1',
            'telefono' => '600101010',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Usuario Notif',
            'email' => 'notif@example.com',
            'contrasena' => Hash::make('secret123'),
            'rol' => 'usuario',
            'gimnasio_id' => $gimnasio->id,
            'gimnasio_cambiado_en' => now(),
        ]);

        $maquina = Maquina::create([
            'gimnasio_id' => $gimnasio->id,
            'nombre' => 'Maquina Notif',
            'descripcion' => 'Demo',
            'activa' => true,
        ]);

        return [$usuario, $maquina, $gimnasio];
    }
}
