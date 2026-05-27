<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gimnasio extends Model
{
    use HasFactory;

    protected $table = 'gimnasios';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    public function maquinas()
    {
        return $this->hasMany(Maquina::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
