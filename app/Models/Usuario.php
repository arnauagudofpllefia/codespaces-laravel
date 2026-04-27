<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Model implements JWTSubject
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
    ];

    protected $hidden = [
        'contrasena',
    ];
}
