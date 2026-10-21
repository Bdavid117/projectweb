<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvisoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $termId = $term?->id ?? 0;
        $filters = [
            'q' => Input::text($request, 'q'),
            'alertas' => Input::text($request, 'alertas'),
        ];

        $students = Student::query()
            ->select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('tutor_assignments')
                ->whereColumn('tutor_assignments.student_id', 'students.id')
                ->where('tutor_assignments.term_id', $termId)
                ->where('tutor_assignments.tutor_id', $user->id))
            ->with(['user', 'academicRecords' => fn ($q) => $q->where('term_id', $termId)])
            ->withCount(['alerts as open_alerts_count' => fn ($q) => $q->where('atendida', false)])
            ->withMax('tutorships as last_tutorship_at', 'fecha')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $query->where(fn ($w) => $w->where('users.name', 'ilike', $like)->orWhere('students.codigo', 'ilike', $like));
            })
            ->when($filters['alertas'] === 'con', fn ($q) => $q->whereHas('alerts', fn ($a) => $a->where('atendida', false)))
            ->when($filters['alertas'] === 'sin', fn ($q) => $q->whereDoesntHave('alerts', fn ($a) => $a->where('atendida', false)))
            ->orderBy('users.name')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('advisories.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'mis-asesorias'),
            'total' => TutorAssignment::where('tutor_id', $user->id)->where('term_id', $termId)->count(),
            'termLabel' => Labels::termLabel($term),
            'students' => $students->through(fn (Student $s) => $this->row($s)),
            'filters' => $filters,
            'alertOptions' => ['con' => 'Con alertas abiertas', 'sin' => 'Sin alertas abiertas'],
        ]);
    }

    private function row(Student $student): array
    {
        $record = $student->academicRecords->first();
        $papa = $record ? (float) $record->papa : null;
        $last = $student->last_tutorship_at ? Carbon::parse($student->last_tutorship_at) : null;
        $open = (int) $student->open_alerts_count;

        [$alert, $tone] = match (true) {
            $open > 0 => [$open.' '.($open === 1 ? 'alerta abierta' : 'alertas abiertas'), 'warning'],
            $last === null => ['Sin tutorías', 'warning'],
            $last->lt(now()->subDays(90)) => ['Sin tutoría reciente', 'warning'],
            default => ['Sin alertas', 'success'],
        };

        return [
            'name' => $student->user->name,
            'cohort' => $student->cohorte,
            'last' => Labels::date($last),
            'papa' => $papa === null ? '—' : number_format($papa, 1),
            'papaLow' => $papa !== null && $papa < config('program.papa_alert'),
            'alert' => $alert,
            'tone' => $tone,
            'profileUrl' => route('students.show', $student),
            'registerUrl' => route('tutorships.create', $student),
        ];
    }
}
