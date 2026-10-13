<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\AdmissionProfile;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Company;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\Internship;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\School;
use App\Models\Student;
use App\Models\Thesis;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('Ana Restrepo', 'coordinacion@unal.edu.co', Role::Coordinacion);
        $tutor = $this->user('Carlos Pineda', 'tutor@unal.edu.co', Role::Tutor);
        $profesor = $this->user('María Londoño', 'profesor@unal.edu.co', Role::Profesor);

        $term = AcademicTerm::where('codigo', '2026-2')->firstOrFail();

        // [codigo, nombre, cohorte, papa, avance, estado]
        $roster = [
            ['2019087', 'Ruiz Salazar, Andrés', '2019-2', '3.20', 88, 'activo'],
            ['2020123', 'Pérez Rojas, Juan Sebastián', '2020-1', '4.10', 72, 'activo'],
            ['2021045', 'Gómez Ruiz, Laura', '2021-1', '3.70', 55, 'activo'],
            ['2021098', 'Cardona Vélez, Mateo', '2021-2', '2.90', 41, 'en_riesgo'],
            ['2022011', 'Martínez Cruz, Diana', '2022-1', '4.40', 31, 'activo'],
            ['2022076', 'Ospina Lara, Sofía', '2022-2', '3.50', 27, 'activo'],
            ['2023019', 'Herrera Niño, Camilo', '2023-1', '3.80', 19, 'activo'],
            ['2023044', 'Valencia Toro, Ana', '2023-2', '4.00', 15, 'intercambio'],
        ];

        $students = [];
        foreach ($roster as [$codigo, $nombre, $cohorte, $papa, $avance, $estado]) {
            $email = $codigo === '2020123' ? 'estudiante@unal.edu.co' : "est{$codigo}@unal.edu.co";
            $user = $this->user($nombre, $email, Role::Estudiante);

            $student = Student::updateOrCreate(['codigo' => $codigo], [
                'user_id' => $user->id, 'documento' => '10'.$codigo, 'cohorte' => $cohorte,
                'estado' => $estado, 'fecha_ingreso' => substr($cohorte, 0, 4).(str_ends_with($cohorte, '-1') ? '-02-03' : '-08-03'),
            ]);
            $students[$codigo] = $student;

            AcademicRecord::updateOrCreate(['student_id' => $student->id, 'term_id' => $term->id], [
                'papa' => $papa, 'promedio_periodo' => $papa, 'cupo_creditos' => 21,
                'creditos_cursados' => (int) round($avance * 160 / 100), 'porcentaje_avance' => $avance,
                'numero_matricula' => 7, 'sincronizado_en' => now(),
            ]);
        }

        $this->seedJuanHistory($students['2020123'], $term, $tutor, $profesor);
        $this->seedRequests($students, $term);
    }

    private function seedJuanHistory(Student $juan, AcademicTerm $term, User $tutor, User $profesor): void
    {
        $juan->academicRecords()->where('term_id', $term->id)->update(['promedio_periodo' => '4.00']);

        foreach ([['2020-1', '3.40', 16], ['2021-1', '3.60', 32], ['2021-2', '3.50', 48], ['2022-1', '3.90', 66], ['2022-2', '4.20', 84], ['2023-1', '4.00', 100]] as [$periodo, $papa, $creditos]) {
            AcademicRecord::updateOrCreate(
                ['student_id' => $juan->id, 'term_id' => AcademicTerm::where('codigo', $periodo)->value('id')],
                ['papa' => $papa, 'promedio_periodo' => $papa, 'cupo_creditos' => 21, 'creditos_cursados' => $creditos,
                    'porcentaje_avance' => round($creditos / 160 * 100, 2), 'numero_matricula' => 1]
            );
        }

        AdmissionProfile::updateOrCreate(['student_id' => $juan->id], [
            'school_id' => School::where('nombre', 'Colegio Nacional de Manizales')->value('id'),
            'puntaje_saber11' => 340.5, 'puntaje_admision' => 78.4,
        ]);

        TutorAssignment::updateOrCreate(['student_id' => $juan->id, 'term_id' => $term->id], ['tutor_id' => $tutor->id]);

        foreach ([
            ['2026-09-12', 'Seguimiento a carga académica', 'Acordó reducir a 15 créditos el próximo periodo.'],
            ['2026-05-03', 'Orientación sobre modalidad de grado', 'Interesado en pasantía; se remitió a Coordinación.'],
            ['2025-11-18', 'Revisión de avance', 'Sin novedades; continúa según plan.'],
        ] as [$fecha, $motivo, $observaciones]) {
            Tutorship::updateOrCreate(
                ['student_id' => $juan->id, 'tutor_id' => $tutor->id, 'fecha' => $fecha],
                ['motivo' => $motivo, 'observaciones' => $observaciones]
            );
        }

        foreach ([
            ['Semillero de investigación SIGMA', 'Semillero o grupo de investigación', '2022-02-01', '2022-06-15'],
            ['Monitoría de Bases de Datos', 'Vinculación (auxiliar, becario o monitor)', '2023-08-01', '2023-12-15'],
            ['Pasantía Sígueme · Univ. de Antioquia', 'Pasantía', '2024-02-01', '2024-06-15'],
            ['Ponencia en Congreso Colombiano de Computación', 'Asistencia o ponencia en evento', '2025-08-20', '2025-08-22'],
        ] as [$nombre, $tipo, $inicio, $fin]) {
            Experience::updateOrCreate(
                ['student_id' => $juan->id, 'nombre' => $nombre],
                ['experience_type_id' => ExperienceType::where('nombre', $tipo)->value('id'), 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'validada' => true]
            );
        }

        Thesis::updateOrCreate(['student_id' => $juan->id, 'term_id' => $term->id], [
            'modalidad' => 'pasantia', 'estado' => 'en_desarrollo', 'director_id' => $profesor->id,
        ]);

        Internship::updateOrCreate(
            ['student_id' => $juan->id, 'company_id' => Company::where('nombre', 'Softlogic S.A.S.')->value('id')],
            ['asesor_id' => $tutor->id, 'fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-12-15']
        );

        Alert::updateOrCreate(['student_id' => $juan->id, 'tipo' => 'caida_papa'], [
            'detalle' => 'Caída del PAPA frente al periodo anterior', 'fecha_deteccion' => '2026-09-12',
        ]);
    }

    private function seedRequests(array $students, AcademicTerm $term): void
    {
        foreach ([
            ['2020123', 'Cancelación de asignatura', 'radicada', '2026-09-10'],
            ['2021045', 'Reingreso al programa', 'en_revision', '2026-09-05'],
        ] as [$codigo, $tipo, $estado, $fecha]) {
            $request = CommitteeRequest::updateOrCreate(
                ['student_id' => $students[$codigo]->id, 'request_type_id' => RequestType::where('nombre', $tipo)->value('id'), 'term_id' => $term->id],
                ['descripcion' => "Solicitud de ejemplo: $tipo.", 'estado' => $estado, 'fecha_radicacion' => $fecha]
            );

            RequestStatusHistory::firstOrCreate(
                ['request_id' => $request->id, 'estado_nuevo' => 'radicada'],
                ['estado_anterior' => null, 'user_id' => $students[$codigo]->user_id, 'fecha' => $fecha]
            );
        }
    }

    private function user(string $name, string $email, Role $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'role' => $role, 'password' => 'password', 'active' => true]
        );
    }
}
