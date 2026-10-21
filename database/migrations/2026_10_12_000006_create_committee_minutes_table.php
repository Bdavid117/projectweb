<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_minutes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_acta')->unique();
            $table->date('fecha');
            $table->string('instancia');
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_minutes');
    }
};
