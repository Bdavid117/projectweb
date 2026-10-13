<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('origen'); // solicitud | alerta | tutoria | observacion_profesor
            $table->text('descripcion');
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->text('resultado')->nullable();
            $table->string('estado')->default('abierta');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
