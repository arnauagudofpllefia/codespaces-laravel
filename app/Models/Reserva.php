<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Reserva de una máquina en un rango horario específico.
 */
class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuario_id',
        'maquina_id',
        'gimnasio_id',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];

    protected $casts = [
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
    ];

    // Usuario que realiza la reserva.
    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    // Máquina reservada.
    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    // Gimnasio donde ocurre la reserva.
    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }
}
