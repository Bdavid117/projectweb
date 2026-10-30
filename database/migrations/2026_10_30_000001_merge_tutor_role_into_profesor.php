<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** El rol «tutor» deja de existir: quienes lo tenían pasan a ser profesores (las tutorías son una función del profesor). */
    public function up(): void
    {
        DB::table('users')->where('role', 'tutor')->update(['role' => 'profesor']);
    }

    /** Irreversible: no hay forma de saber qué profesores eran tutores antes de la fusión. */
    public function down(): void
    {
        //
    }
};
