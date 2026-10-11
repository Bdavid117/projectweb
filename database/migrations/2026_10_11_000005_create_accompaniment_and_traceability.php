<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acompañamiento + experiencias + trazabilidad (Corte 1 §5.1.d, §5.3).
     * tutor_assignments: un estudiante tiene un único tutor por periodo
     *   (UK student_id+term_id), pero puede cambiar entre periodos.
     * audit_logs: relación polimórfica (auditable_type/id) para RNF-07.
     * sync_logs: cubre RF-17 (resultado de sincronización API).
     */
    public function up(): void
    {
        Schema::create('tutor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'term_id']);
        });

        Schema::create('tutorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('motivo', 255);
            $table->text('observaciones')->nullable();
            $table->text('compromisos')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'fecha']);
        });

        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('origen', 40)->comment('solicitud|alerta|tutoria|observacion');
            $table->text('descripcion');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->text('resultado')->nullable();
            $table->string('estado', 30)->default('abierta');
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('experience_type_id')->constrained('experience_types')->restrictOnDelete();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('alcance', 80)->nullable();
            $table->string('institucion', 200)->nullable();
            $table->string('convenio', 200)->nullable();
            $table->foreignId('docente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rol', 120)->nullable();
            $table->boolean('validada')->default(false);
            $table->foreignId('validada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'fecha_inicio']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('tipo', 60)->comment('caida_papa|matricula_sin_avance|solicitud_recurrente');
            $table->text('detalle')->nullable();
            $table->timestamp('fecha_deteccion')->useCurrent();
            $table->boolean('atendida')->default(false);
            $table->timestamps();
            $table->index(['atendida', 'fecha_deteccion']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 120);
            $table->unsignedBigInteger('auditable_id');
            $table->string('accion', 30);
            $table->jsonb('valores_anteriores')->nullable();
            $table->jsonb('valores_nuevos')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        // inet no existe en Blueprint: se agrega como tipo nativo PG.
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE audit_logs ADD COLUMN ip inet NULL'
        );

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40)->comment('saber_pro|academic|excel');
            $table->string('archivo', 500);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('filas_ok')->default(0);
            $table->unsignedInteger('filas_error')->default(0);
            $table->jsonb('log')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fuente', 60)->comment('api institucional (RF-13/RF-17)');
            $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->string('estado', 30)->default('ok');
            $table->text('mensaje_error')->nullable();
            $table->unsignedInteger('registros')->default(0);
            $table->timestamp('ejecutado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE audit_logs DROP COLUMN IF EXISTS ip'
        );
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('tutorships');
        Schema::dropIfExists('tutor_assignments');
    }
};
