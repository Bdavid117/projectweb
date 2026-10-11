<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogos base + users con rol (Corte 1 §5.1-5.3).
     * Roles: coordinacion | profesor | tutor | estudiante.
     */
    public function up(): void
    {
        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Ej: 2026-2S');
            $table->string('name', 60);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('caracter', 20)->comment('publico|privado (3FN: depende del colegio)');
            $table->string('municipio', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('nit', 30)->nullable()->unique();
            $table->string('sector', 100)->nullable();
            $table->string('tamano', 30)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('experience_types', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('categoria', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('committee_minutes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_acta', 40)->unique();
            $table->date('fecha');
            $table->string('instancia', 80)->comment('comite asesor|consejo');
            $table->string('url', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('estudiante')->after('password');
            $table->boolean('active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'active']);
        });
        Schema::dropIfExists('committee_minutes');
        Schema::dropIfExists('experience_types');
        Schema::dropIfExists('request_types');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('academic_terms');
    }
};
