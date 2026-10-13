<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('experience_type_id')->constrained()->restrictOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('alcance')->nullable();
            $table->string('institucion')->nullable();
            $table->string('convenio')->nullable();
            $table->foreignId('docente_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('rol')->nullable();
            $table->boolean('validada')->default(false);
            $table->foreignId('validada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
