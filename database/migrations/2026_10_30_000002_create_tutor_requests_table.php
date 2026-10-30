<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
            // pendiente | aceptada | rechazada | cancelada (la retira el profesor) | revocada (el estudiante deja de compartir)
            $table->string('estado', 20)->default('pendiente');
            $table->text('mensaje')->nullable();
            $table->timestamp('respondida_en')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'estado']);
        });

        // Un profesor no puede tener dos solicitudes pendientes al mismo estudiante.
        DB::statement("create unique index tutor_requests_pendiente_unique on tutor_requests (tutor_id, student_id) where estado = 'pendiente'");
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_requests');
    }
};
