<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->text('motivo_rechazo')->nullable()->after('validada_por');
            $table->foreignId('rechazada_por')->nullable()->after('motivo_rechazo')->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rechazada_por');
            $table->dropColumn('motivo_rechazo');
        });
    }
};
