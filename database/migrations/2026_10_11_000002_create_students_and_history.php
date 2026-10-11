<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Núcleo del estudiante + perfiles (Corte 1 §5.1.a, §5.3).
     * health_profiles va en tabla separada por RNF-05 (datos sensibles).
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('codigo', 20)->unique();
            $table->string('documento', 30)->unique();
            $table->string('cohorte', 10)->comment('Ej: 2020-1');
            $table->string('estado', 20)->default('Activo')->comment('Activo|En riesgo|Intercambio|Inactivo...');
            $table->date('fecha_ingreso')->nullable();
            $table->timestamps();
        });

        Schema::create('admission_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->decimal('puntaje_saber11', 5, 2)->nullable();
            $table->decimal('puntaje_admision', 5, 2)->nullable();
            $table->string('hobby_formal', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->text('resultado_oso')->nullable();
            $table->text('resultado_salud_mental')->nullable();
            $table->text('resultado_salud_fisica')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('saber_pro_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('fecha_presentacion');
            $table->decimal('puntaje_global', 5, 2)->nullable();
            $table->jsonb('resultados_json')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'fecha_presentacion']);
        });

        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->decimal('papa', 3, 2)->nullable();
            $table->decimal('promedio_periodo', 3, 2)->nullable();
            $table->smallInteger('cupo_creditos')->nullable();
            $table->smallInteger('creditos_cursados')->nullable();
            $table->decimal('porcentaje_avance', 5, 2)->nullable();
            $table->smallInteger('numero_matricula')->nullable();
            $table->timestamp('sincronizado_en')->nullable();
            $table->timestamps();
            // Restricción que sostiene la historicidad (RNF-08):
            // un solo registro por estudiante y periodo, sin sobrescritura.
            $table->unique(['student_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
        Schema::dropIfExists('saber_pro_results');
        Schema::dropIfExists('health_profiles');
        Schema::dropIfExists('admission_profiles');
        Schema::dropIfExists('students');
    }
};
