<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trabajo de grado + práctica empresarial (Corte 1 §5.1.b, RF-18 a RF-24).
     * Director/asesor apuntan a users (rol profesor|tutor). Los seguimientos
     * son entidad propia para conservar todos, no solo el último.
     */
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('modalidad', 60);
            $table->string('estado', 30)->default('propuesta');
            $table->string('titulo', 255)->nullable();
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('codirector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->string('repositorio_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('jefe_nombre', 150)->nullable();
            $table->string('jefe_cargo', 120)->nullable();
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->text('actividades')->nullable();
            $table->text('apreciacion_jefe')->nullable();
            $table->timestamp('apreciacion_jefe_fecha')->nullable();
            $table->text('apreciacion_asesor')->nullable();
            $table->timestamp('apreciacion_asesor_fecha')->nullable();
            $table->timestamps();
        });

        Schema::create('internship_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained('internships')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('observacion');
            $table->foreignId('autor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_follow_ups');
        Schema::dropIfExists('internships');
        Schema::dropIfExists('theses');
    }
};
