<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Entidad raíz del dominio: agrupa usuarios, máquinas y reservas.
 */
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

    // Usuarios asignados a este gimnasio.
    public function usuarios()
    {
        return $this->hasMany(Usuario::class);
    }

    // Máquinas físicas registradas en el gimnasio.
    public function maquinas()
    {
        return $this->hasMany(Maquina::class);
    }

    // Reservas realizadas dentro del gimnasio.
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
