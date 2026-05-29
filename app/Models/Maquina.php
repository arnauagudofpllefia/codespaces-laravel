<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Representa una máquina disponible para reserva dentro de un gimnasio.
 */
class Maquina extends Model
{
    use HasFactory;

    protected $table = 'maquinas';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'gimnasio_id',
        'nombre',
        'descripcion',
        'imagen',
        'activa',
    ];

    protected $appends = [
        'imagen_url',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // Gimnasio al que pertenece la máquina.
    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    // Reservas asociadas a la máquina.
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    // Alias calculado para mantener compatibilidad con clientes que esperan `imagen_url`.
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen;
    }
}
