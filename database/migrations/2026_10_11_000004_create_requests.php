<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes al comité asesor (Corte 1 §5.1.c, RF-25 a RF-29).
     * minute_id es nullable: una solicitud recién radicada aún no tiene acta.
     * request_status_history conserva la traza completa (RNF-07).
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('request_type_id')->constrained('request_types')->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
            $table->text('descripcion');
            $table->string('estado', 30)->default('Radicada');
            $table->timestamp('fecha_radicacion')->useCurrent();
            $table->foreignId('minute_id')->nullable()->constrained('committee_minutes')->nullOnDelete();
            $table->text('decision')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'request_type_id', 'term_id']);
            $table->index('estado');
        });

        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('ruta', 500);
            $table->string('nombre_original', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('tamano')->comment('bytes, máx 5MB por archivo (RF-25)');
            $table->timestamps();
        });

        Schema::create('request_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_history');
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('requests');
    }
};
