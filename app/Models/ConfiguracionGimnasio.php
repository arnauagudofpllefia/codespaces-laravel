<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros operativos del gimnasio para la lógica de agenda.
 */
class ConfiguracionGimnasio extends Model
{
    use HasFactory;

    protected $table = 'configuracion_gimnasio';

    public $timestamps = false;

    protected $fillable = [
        'gimnasio_id',
        'duracion_slot',
        'hora_apertura',
        'hora_cierre',
    ];

    // Configuración pertenece a un gimnasio concreto.
    public function gimnasio()
    {
        return $this->belongsTo(Gimnasio::class);
    }
}
