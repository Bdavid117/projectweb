<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Réplica en BD de los mocks actuales de StudentController::STUDENTS
     * para que la fase 2 (UI → Eloquent) tenga datos equivalentes.
     */
    public function run(): void
    {
        $term = AcademicTerm::firstOrCreate(['code' => '2026-2S'], ['name' => 'Periodo 2026-2S']);

        $rows = [
            ['code' => '2019087', 'name' => 'Ruiz Salazar, Andrés', 'cohort' => '2019-2', 'papa' => '3.2', 'advance' => 88, 'status' => 'Activo'],
            ['code' => '2020123', 'name' => 'Pérez Rojas, Juan', 'cohort' => '2020-1', 'papa' => '4.1', 'advance' => 72, 'status' => 'Activo'],
            ['code' => '2021045', 'name' => 'Gómez Ruiz, Laura', 'cohort' => '2021-1', 'papa' => '3.7', 'advance' => 55, 'status' => 'Activo'],
            ['code' => '2021098', 'name' => 'Cardona Vélez, Mateo', 'cohort' => '2021-2', 'papa' => '2.9', 'advance' => 41, 'status' => 'En riesgo'],
            ['code' => '2022011', 'name' => 'Martínez Cruz, Diana', 'cohort' => '2022-1', 'papa' => '4.4', 'advance' => 31, 'status' => 'Activo'],
            ['code' => '2022076', 'name' => 'Ospina Lara, Sofía', 'cohort' => '2022-2', 'papa' => '3.5', 'advance' => 27, 'status' => 'Activo'],
            ['code' => '2023019', 'name' => 'Herrera Niño, Camilo', 'cohort' => '2023-1', 'papa' => '3.8', 'advance' => 19, 'status' => 'Activo'],
            ['code' => '2023044', 'name' => 'Valencia Toro, Ana', 'cohort' => '2023-2', 'papa' => '4.0', 'advance' => 15, 'status' => 'Intercambio'],
        ];

        foreach ($rows as $i => $row) {
            $user = User::firstOrCreate(
                ['email' => "estudiante{$row['code']}@example.com"],
                ['name' => $row['name'], 'password' => 'password', 'role' => 'estudiante']
            );

            $student = Student::firstOrCreate(
                ['codigo' => $row['code']],
                [
                    'user_id' => $user->id,
                    'documento' => 'DOC'.$row['code'],
                    'cohorte' => $row['cohort'],
                    'estado' => $row['status'],
                ]
            );

            AcademicRecord::firstOrCreate(
                ['student_id' => $student->id, 'term_id' => $term->id],
                [
                    'papa' => $row['papa'],
                    'promedio_periodo' => $row['papa'],
                    'porcentaje_avance' => $row['advance'],
                    'creditos_cursados' => (int) round($row['advance'] * 1.6),
                    'cupo_creditos' => 21,
                    'numero_matricula' => $i + 1,
                    'sincronizado_en' => now(),
                ]
            );
        }

        User::firstOrCreate(
            ['email' => 'coordinacion@example.com'],
            ['name' => 'Ana Restrepo', 'password' => 'password', 'role' => 'coordinacion']
        );
        User::firstOrCreate(
            ['email' => 'tutor@example.com'],
            ['name' => 'Carlos Pineda', 'password' => 'password', 'role' => 'tutor']
        );
    }
}
