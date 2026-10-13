<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $filters = [
            'q' => Input::text($request, 'q'),
            'cohorte' => Input::text($request, 'cohorte'),
            'estado' => Input::text($request, 'estado'),
            'tutor' => Input::id($request, 'tutor'),
        ];

        $students = Student::query()
            ->select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with(['user', 'academicRecords' => fn ($q) => $q->where('term_id', $term?->id)])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $query->where(fn ($w) => $w
                    ->where('users.name', 'ilike', $like)
                    ->orWhere('students.codigo', 'ilike', $like)
                    ->orWhere('students.documento', 'ilike', $like));
            })
            ->when($filters['cohorte'] !== '', fn ($q) => $q->where('students.cohorte', $filters['cohorte']))
            ->when($filters['estado'] !== '', fn ($q) => $q->where('students.estado', $filters['estado']))
            ->when($filters['tutor'] > 0 && $term, fn ($q) => $q->whereExists(fn ($e) => $e
                ->selectRaw('1')
                ->from('tutor_assignments')
                ->whereColumn('tutor_assignments.student_id', 'students.id')
                ->where('tutor_assignments.term_id', $term->id)
                ->where('tutor_assignments.tutor_id', $filters['tutor'])))
            ->orderBy('users.name')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('students.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'estudiantes'),
            'students' => $students->through(fn (Student $student) => $this->row($student)),
            'filters' => $filters,
            'cohortOptions' => Student::query()->distinct()->orderBy('cohorte')->pluck('cohorte', 'cohorte'),
            'statusOptions' => ['activo' => 'Activo', 'en_riesgo' => 'En riesgo', 'intercambio' => 'Intercambio', 'egresado' => 'Egresado'],
            'tutorOptions' => User::where('role', 'tutor')->orderBy('name')->pluck('name', 'id'),
            'activeCount' => Student::where('estado', 'activo')->count(),
        ]);
    }

    private function row(Student $student): array
    {
        $record = $student->academicRecords->first();
        [$status, $tone] = Labels::studentStatus($student->estado);
        $papa = $record ? (float) $record->papa : null;

        return [
            'id' => $student->id,
            'code' => $student->codigo,
            'name' => $student->user->name,
            'cohort' => $student->cohorte,
            'papa' => $papa === null ? '—' : number_format($papa, 1),
            'papaLow' => $papa !== null && $papa < config('program.papa_alert'),
            'advance' => $record ? (int) round((float) $record->porcentaje_avance) : 0,
            'status' => $status,
            'tone' => $tone,
        ];
    }

    public function show(string $id): View
    {
        return view('students.show', [
            'user' => auth()->user()->viewData(),
            'navGroups' => Navigation::forRole(auth()->user()->role, 'estudiantes'),
            'student' => [
                'initials' => 'JP',
                'name' => 'Pérez Rojas, Juan Sebastián',
                'code' => 'Código 2020123',
                'cohort' => 'Cohorte 2020-1',
                'enrollment' => 'Séptima matrícula',
                'tutor' => 'Tutor asignado: Carlos Pineda',
            ],
            'tabs' => ['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'],
            'papaChart' => [
                ['period' => '2020-1', 'value' => '3.4', 'height' => 76],
                ['period' => '2021-1', 'value' => '3.6', 'height' => 94],
                ['period' => '2021-2', 'value' => '3.5', 'height' => 85],
                ['period' => '2022-1', 'value' => '3.9', 'height' => 119],
                ['period' => '2022-2', 'value' => '4.2', 'height' => 145],
                ['period' => '2023-1', 'value' => '4.0', 'height' => 128],
                ['period' => '2026-2', 'value' => '4.1', 'height' => 136],
            ],
            'timeline' => [
                ['icon' => 'sparkles', 'description' => 'Semillero de investigación SIGMA', 'period' => '2022-1', 'tag' => 'Investigación', 'tone' => 'primary'],
                ['icon' => 'book-open', 'description' => 'Monitoría de Bases de Datos', 'period' => '2023-2', 'tag' => 'Vinculación', 'tone' => 'accent'],
                ['icon' => 'plane', 'description' => 'Pasantía Sígueme · Univ. de Antioquia', 'period' => '2024-1', 'tag' => 'Pasantía', 'tone' => 'restricted'],
                ['icon' => 'mic', 'description' => 'Ponencia en Congreso Colombiano de Computación', 'period' => '2025-2', 'tag' => 'Evento', 'tone' => 'warning'],
                ['icon' => 'briefcase', 'description' => 'Práctica empresarial · Softlogic S.A.S.', 'period' => '2026-2', 'tag' => 'Práctica', 'tone' => 'success'],
            ],
            'processes' => [
                ['title' => 'Trabajo de grado · modalidad pasantía', 'meta' => 'Director: Prof. María Londoño · 2026-2S', 'status' => 'En desarrollo', 'tone' => 'warning'],
                ['title' => 'Práctica empresarial · Softlogic S.A.S.', 'meta' => 'Asesor: Prof. Carlos Pineda · jul–dic 2026', 'status' => 'En curso', 'tone' => 'info'],
            ],
            'progress' => [
                'percent' => 72,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => '116 de 160'],
                    ['label' => 'Cupo del periodo', 'value' => '21 créditos'],
                    ['label' => 'PAPA actual', 'value' => '4.1'],
                    ['label' => 'Promedio anterior', 'value' => '4.0'],
                ],
            ],
            'tutorials' => [
                ['date' => '12/09/2026', 'reason' => 'Seguimiento a carga académica', 'note' => 'Acordó reducir a 15 créditos el próximo periodo.'],
                ['date' => '03/05/2026', 'reason' => 'Orientación sobre modalidad de grado', 'note' => 'Interesado en pasantía; se remitió a Coordinación.'],
                ['date' => '18/11/2025', 'reason' => 'Revisión de avance', 'note' => 'Sin novedades; continúa según plan.'],
            ],
        ]);
    }
}
