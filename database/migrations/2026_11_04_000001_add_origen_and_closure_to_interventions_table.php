<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            // La tabla guardaba solo el tipo de origen (solicitud | alerta | tutoria | observacion_profesor).
            // Con `origen_id` la intervención puede enlazar el registro concreto de origen (RF-36). Es una relación
            // polimórfica «a mano»: el tipo lo dice `origen`, así que no hace falta FK. «observacion_profesor» no enlaza nada.
            $table->unsignedBigInteger('origen_id')->nullable()->after('origen');
            // Momento del cierre, para la bitácora y el orden; el resultado se guarda en la columna `resultado` ya existente.
            $table->timestamp('cerrada_en')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropColumn(['origen_id', 'cerrada_en']);
        });
    }
};
