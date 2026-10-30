<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\TutorRequest;
use App\Services\TutorLinkWorkflow;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lado del profesor: pide acompañar a un estudiante y sigue el estado de sus solicitudes. */
class TutorRequestController extends Controller
{
    private const MIN_SEARCH = 3;

    private const MAX_RESULTS = 10;

    public function index(Request $request): View
    {
        $this->authorize('create', TutorRequest::class);

        $user = $request->user();
        $search = Input::text($request, 'q');
        $searched = mb_strlen($search) >= self::MIN_SEARCH;

        $pending = TutorRequest::where('tutor_id', $user->id)->where('estado', TutorRequest::PENDING)->pluck('student_id')->all();
        $linked = TutorAssignment::studentIdsOf($user);

        // Solo nombre, código y cohorte: el profesor aún no tiene acceso a nada más del estudiante.
        $results = ! $searched ? collect() : Student::query()
            ->select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with('user')
            ->where(fn ($w) => $w
                ->where('users.name', 'ilike', '%'.addcslashes($search, '\\%_').'%')
                ->orWhere('students.codigo', 'ilike', '%'.addcslashes($search, '\\%_').'%'))
            ->orderBy('users.name')
            ->limit(self::MAX_RESULTS)
            ->get()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'name' => $s->user->name,
                'code' => $s->codigo,
                'cohort' => $s->cohorte,
                'state' => match (true) {
                    in_array($s->id, $linked, true) => 'linked',
                    in_array($s->id, $pending, true) => 'pending',
                    default => 'available',
                },
            ]);

        $sent = TutorRequest::with('student.user')->where('tutor_id', $user->id)
            ->latest()->latest('id')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('tutor-requests.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'solicitar-tutoria'),
            'search' => $search,
            'searched' => $searched,
            'minSearch' => self::MIN_SEARCH,
            'results' => $results,
            'sent' => $sent->through(fn (TutorRequest $t) => $this->row($t, $linked)),
        ]);
    }

    public function store(Request $request, TutorLinkWorkflow $workflow): RedirectResponse
    {
        $this->authorize('create', TutorRequest::class);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'mensaje' => ['nullable', 'string', 'max:500'],
        ], [
            'student_id.required' => 'Elija al estudiante al que desea acompañar.',
            'student_id.integer' => 'El estudiante elegido no es válido.',
            'student_id.exists' => 'El estudiante elegido no existe.',
            'mensaje.string' => 'El mensaje no es válido.',
            'mensaje.max' => 'El mensaje no puede superar los :max caracteres.',
        ]);

        try {
            $workflow->send($request, Student::findOrFail($data['student_id']), $data['mensaje'] ?? null);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['student_id' => $e->getMessage()]);
        }

        return redirect()->route('tutor-requests.index')->with('status', 'Solicitud enviada. El estudiante decidirá si la acepta; hasta entonces no podrá ver su información.');
    }

    public function destroy(Request $request, TutorRequest $solicitud, TutorLinkWorkflow $workflow): RedirectResponse
    {
        $this->authorize('cancel', $solicitud);

        try {
            $workflow->cancel($request, $solicitud);
        } catch (DomainException $e) {
            return redirect()->route('tutor-requests.index')->withErrors(['student_id' => $e->getMessage()]);
        }

        return redirect()->route('tutor-requests.index')->with('status', 'Solicitud retirada.');
    }

    /** @param  list<int>  $linked */
    private function row(TutorRequest $request, array $linked): array
    {
        [$status, $tone] = Labels::tutorRequestStatus($request->estado);

        return [
            'id' => $request->id,
            'student' => $request->student->user?->name ?? $request->student->codigo,
            'code' => $request->student->codigo,
            'status' => $status,
            'tone' => $tone,
            'sentAt' => Labels::date($request->created_at),
            'answeredAt' => Labels::date($request->respondida_en),
            'canCancel' => $request->isPending(),
            'profileUrl' => in_array($request->student_id, $linked, true) ? route('students.show', $request->student_id) : null,
        ];
    }
}
