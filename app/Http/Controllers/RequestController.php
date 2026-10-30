<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\DecideRequestRequest;
use App\Http\Requests\StoreSolicitudRequest;
use App\Models\AcademicTerm;
use App\Models\CommitteeRequest;
use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\Student;
use App\Services\RequestWorkflow;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\StudentProfile;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestController extends Controller
{
    public function create(Request $request, RequestWorkflow $workflow): View
    {
        $user = $request->user();
        $term = AcademicTerm::current();
        $student = $user->role === Role::Estudiante ? $user->student : null;

        return view('requests.create', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'isStaff' => $user->role === Role::Coordinacion,
            'requestTypes' => RequestType::orderBy('id')->pluck('nombre', 'id'),
            'termLabel' => $term?->codigo ?? 'Sin periodo',
            'existing' => $student && $term ? $workflow->existingTypeNames($student, $term) : [],
            'steps' => [
                ['number' => 1, 'title' => 'Radicada', 'detail' => 'Queda registrada con fecha y ya no puede editarla.'],
                ['number' => 2, 'title' => 'En revisión', 'detail' => 'El comité asesor estudia el caso.'],
                ['number' => 3, 'title' => 'Con decisión', 'detail' => 'Coordinación carga la decisión y el número de acta.'],
            ],
        ]);
    }

    public function store(StoreSolicitudRequest $request, RequestWorkflow $workflow): RedirectResponse
    {
        $user = $request->user();
        $student = $user->role === Role::Estudiante
            ? $user->student
            : Student::where('codigo', $request->input('codigo_estudiante'))->first();

        abort_if($student === null, 403, 'Su usuario no tiene una ficha de estudiante.');

        $term = AcademicTerm::current();
        if ($term === null) {
            return back()->withInput()->withErrors(['tipo' => 'No hay un periodo académico vigente configurado.']);
        }

        $solicitud = $workflow->file(
            $student,
            RequestType::findOrFail((int) $request->input('tipo')),
            $term,
            (string) $request->input('descripcion'),
            $request->file('soportes', []),
            $user,
        );

        return redirect()->route('requests.show', $solicitud)->with('status', 'Solicitud radicada correctamente.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $isStudent = $user->role === Role::Estudiante;
        $term = AcademicTerm::current();
        $filters = [
            'q' => Input::text($request, 'q'),
            'tipo' => Input::text($request, 'tipo'),
            'estado' => Input::text($request, 'estado'),
            'periodo' => Input::text($request, 'periodo'),
        ];

        $scope = fn () => CommitteeRequest::query()
            ->when($user->role !== Role::Coordinacion, fn ($q) => $q->whereIn('student_id', Student::query()->visibleTo($user)->select('students.id')));

        $requests = $scope()
            ->with(['student.user', 'type', 'term'])
            ->when($filters['q'] !== '' && ! $isStudent, function ($q) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $q->whereHas('student', fn ($s) => $s->where(fn ($w) => $w
                    ->where('codigo', 'ilike', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', $like))));
            })
            ->when($filters['tipo'] !== '', fn ($q) => $q->where('request_type_id', (int) $filters['tipo']))
            ->when($filters['periodo'] !== '', fn ($q) => $q->where('term_id', (int) $filters['periodo']))
            ->when($filters['estado'] !== '', fn ($q) => match ($filters['estado']) {
                'radicada', 'en_revision' => $q->where('estado', $filters['estado']),
                'aprobada', 'rechazada', 'aplazada' => $q->where('estado', 'con_decision')->where('decision', $filters['estado']),
                default => $q,
            })
            ->orderByDesc('fecha_radicacion')
            ->orderByDesc('id')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        $inTerm = fn () => $scope()->where('term_id', $term?->id ?? 0);
        $total = $inTerm()->count();
        $pending = $inTerm()->where('estado', '!=', 'con_decision')->count();

        return view('requests.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'title' => $isStudent ? 'Mis solicitudes' : 'Solicitudes',
            'subtitle' => $total.' solicitudes en el periodo '.Labels::termLabel($term).' · '.$pending.' sin decisión · '.($isStudent ? 'RF-27' : 'RF-28'),
            'isStudent' => $isStudent,
            'canCreate' => $user->can('create', CommitteeRequest::class),
            'requests' => $requests->through(fn (CommitteeRequest $r) => $this->row($r)),
            'filters' => $filters,
            'typeOptions' => RequestType::orderBy('nombre')->pluck('nombre', 'id'),
            'statusOptions' => ['radicada' => 'Radicada', 'en_revision' => 'En revisión', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'aplazada' => 'Aplazada'],
            'termOptions' => AcademicTerm::whereIn('id', CommitteeRequest::query()->select('term_id'))->orderByDesc('fecha_inicio')->pluck('codigo', 'id'),
        ]);
    }

    public function show(Request $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): View
    {
        $this->authorize('view', $solicitud);

        $user = $request->user();
        $isStaff = $user->role !== Role::Estudiante;
        $solicitud->load(['student.user', 'type', 'term', 'attachments', 'statusHistory.user', 'minute']);
        [$status, $tone] = Labels::requestStatus($solicitud);
        $repeated = $user->role === Role::Coordinacion ? $workflow->repeatedCount($solicitud) : 0;

        $dots = [
            'radicada' => 'bg-[var(--color-primary-600)]',
            'en_revision' => 'bg-[var(--color-warning)]',
            'con_decision' => 'bg-[var(--color-success)]',
        ];
        $trail = $solicitud->statusHistory->sortBy('fecha')->map(fn ($h) => [
            'label' => Labels::stateLabel($h->estado_nuevo),
            'meta' => $h->fecha->format('d/m/Y H:i').' · '.$h->user->name,
            'dot' => $dots[$h->estado_nuevo] ?? 'bg-[var(--color-neutral-300)]',
        ])->values()->all();
        if ($solicitud->estado !== 'con_decision') {
            $trail[] = ['label' => 'Con decisión', 'meta' => 'Pendiente · la registra Coordinación', 'dot' => 'bg-[var(--color-neutral-300)]'];
        }

        return view('requests.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitudes'),
            'solicitud' => [
                'id' => $solicitud->id,
                'number' => $solicitud->fecha_radicacion->year.'-'.str_pad((string) $solicitud->id, 4, '0', STR_PAD_LEFT),
                'type' => $solicitud->type->nombre,
                'status' => $status,
                'tone' => $tone,
                'filedAt' => $solicitud->fecha_radicacion->format('d/m/Y H:i'),
                'student' => $solicitud->student->user?->name ?? $solicitud->student->codigo,
                'code' => $solicitud->student->codigo,
                'term' => $solicitud->term->codigo,
                'description' => $solicitud->descripcion,
            ],
            'attachments' => $solicitud->attachments->map(fn (RequestAttachment $a) => [
                'name' => $a->nombre_original,
                'size' => Labels::bytes($a->tamano),
                'url' => route('requests.attachments.show', [$solicitud, $a]),
            ])->all(),
            'trail' => $trail,
            'studentCard' => $isStaff ? StudentProfile::header($solicitud->student) : null,
            'repeatedWarning' => $repeated > 0
                ? 'Es la '.($repeated + 1).'.ª solicitud de este tipo del estudiante en el periodo '.$solicitud->term->codigo.' · RF-29'
                : null,
            'decision' => $solicitud->estado === 'con_decision' ? [
                'text' => $status,
                'tone' => $tone,
                'acta' => $solicitud->minute?->numero_acta,
                'observations' => $solicitud->observaciones,
            ] : null,
            'canReview' => $user->role === Role::Coordinacion && $solicitud->estado === 'radicada',
            'canDecide' => $user->role === Role::Coordinacion && $solicitud->estado === 'en_revision',
            'decisionOptions' => ['aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'aplazada' => 'Aplazada'],
        ]);
    }

    public function startReview(Request $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): RedirectResponse
    {
        $this->authorize('decide', $solicitud);

        try {
            $workflow->startReview($solicitud, $request->user());
        } catch (DomainException $e) {
            return redirect()->route('requests.show', $solicitud)->withErrors(['estado' => $e->getMessage()]);
        }

        return redirect()->route('requests.show', $solicitud)->with('status', 'La solicitud pasó a revisión.');
    }

    public function decide(DecideRequestRequest $request, RequestWorkflow $workflow, CommitteeRequest $solicitud): RedirectResponse
    {
        try {
            $workflow->decide(
                $solicitud,
                (string) $request->input('decision'),
                trim((string) $request->input('numero_acta')),
                $request->filled('observaciones') ? (string) $request->input('observaciones') : null,
                $request->user(),
            );
        } catch (DomainException $e) {
            return redirect()->route('requests.show', $solicitud)->withErrors(['estado' => $e->getMessage()])->withInput();
        }

        return redirect()->route('requests.show', $solicitud)->with('status', 'Decisión registrada correctamente.');
    }

    public function download(CommitteeRequest $solicitud, RequestAttachment $soporte): StreamedResponse
    {
        $this->authorize('view', $solicitud);
        abort_unless($soporte->request_id === $solicitud->id, 404);
        abort_unless(Storage::exists($soporte->ruta), 404);

        return Storage::download($soporte->ruta, $soporte->nombre_original);
    }

    private function row(CommitteeRequest $request): array
    {
        [$status, $tone] = Labels::requestStatus($request);

        return [
            'date' => Labels::date($request->fecha_radicacion),
            'student' => $request->student->user?->name ?? $request->student->codigo,
            'type' => $request->type->nombre,
            'period' => $request->term->codigo,
            'status' => $status,
            'tone' => $tone,
            'url' => route('requests.show', $request),
        ];
    }
}
