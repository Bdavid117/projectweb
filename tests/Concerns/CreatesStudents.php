<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;

trait CreatesStudents
{
    protected function makeStudent(string $codigo = '2020123'): Student
    {
        $user = User::factory()->create(['role' => Role::Estudiante]);

        return Student::create([
            'user_id' => $user->id,
            'codigo' => $codigo,
            'documento' => '10'.$codigo,
            'cohorte' => '2020-1',
            'estado' => 'activo',
            'fecha_ingreso' => '2020-02-03',
        ]);
    }

    protected function makeTerm(string $codigo = '2026-2'): AcademicTerm
    {
        return AcademicTerm::firstOrCreate(
            ['codigo' => $codigo],
            ['fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-12']
        );
    }
}
