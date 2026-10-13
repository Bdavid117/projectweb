<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('archivo');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('filas_ok')->default(0);
            $table->unsignedInteger('filas_error')->default(0);
            $table->text('log')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
