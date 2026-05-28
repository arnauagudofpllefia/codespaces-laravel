<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen;
    }
}
