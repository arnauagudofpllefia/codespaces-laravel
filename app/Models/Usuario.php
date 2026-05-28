<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

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

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }
}
