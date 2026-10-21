<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
            $table->decimal('papa', 3, 2);
            $table->decimal('promedio_periodo', 3, 2)->nullable();
            $table->unsignedSmallInteger('cupo_creditos')->nullable();
            $table->unsignedSmallInteger('creditos_cursados')->default(0);
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->unsignedSmallInteger('numero_matricula')->nullable();
            $table->timestamp('sincronizado_en')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
