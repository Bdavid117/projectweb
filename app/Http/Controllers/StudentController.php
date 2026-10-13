<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Tutorship;
use App\Models\User;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\StudentProfile;
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

    public function show(Request $request, Student $student): View
    {
        $user = $request->user();

        return view('students.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'estudiantes'),
            'canRegisterTutorship' => $user->can('create', Tutorship::class),
            ...StudentProfile::build($student, $user),
        ]);
    }
}
