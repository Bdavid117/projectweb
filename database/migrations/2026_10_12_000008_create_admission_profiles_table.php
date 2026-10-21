<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('puntaje_saber11', 6, 2)->nullable();
            $table->decimal('puntaje_admision', 6, 2)->nullable();
            $table->string('hobby_formal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_profiles');
    }
};
