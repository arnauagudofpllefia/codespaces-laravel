<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Modelo de usuario autenticable de la API.
 *
 * Usa JWT como mecanismo de autenticación y mantiene la relación
 * con gimnasio para reglas de negocio (reservas y cambio de centro).
 */
class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'usuarios';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'email',
        'contrasena',
        'rol',
        'gimnasio_id',
        'gimnasio_cambiado_en',
    ];

    protected $casts = [
        'gimnasio_cambiado_en' => 'datetime',
    ];

    protected $hidden = [
        'contrasena',
    ];

    // Informa a Laravel qué campo usar como password al autenticar.
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    // Claim "sub" del token JWT.
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    // Claims personalizados adicionales (actualmente ninguno).
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    // Gimnasio al que pertenece el usuario.
    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    // Notificaciones in-app asociadas al usuario.
    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }
}
