<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agenda de reservas entre usuarios y máquinas.
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('maquina_id')->constrained('maquinas');
            $table->foreignId('gimnasio_id')->constrained('gimnasios');
            // Intervalo reservado para detectar solapamientos en lógica de negocio.
            $table->dateTime('hora_inicio');
            $table->dateTime('hora_fin');
            $table->enum('estado', ['activa', 'cancelada', 'completada'])->default('activa');
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
