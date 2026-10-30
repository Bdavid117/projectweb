<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\TutorAssignment;
use App\Models\TutorRequest;
use App\Services\TutorLinkWorkflow;
use App\Support\Labels;
use App\Support\Navigation;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lado del estudiante: decide quién puede ver su información como tutor. */
class MyTutorController extends Controller
{
    private const HISTORY_LIMIT = 10;

    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;
        $term = AcademicTerm::current();

        $current = $student && $term
            ? TutorAssignment::with('tutor')->where('student_id', $student->id)->where('term_id', $term->id)->first()
            : null;

        $pending = $student
            ? TutorRequest::with('tutor')->where('student_id', $student->id)->where('estado', TutorRequest::PENDING)->latest()->get()
            : collect();

        $history = $student
            ? TutorRequest::with('tutor')->where('student_id', $student->id)->where('estado', '!=', TutorRequest::PENDING)
                ->latest('updated_at')->limit(self::HISTORY_LIMIT)->get()
            : collect();

        return view('my-tutor.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'mi-tutor'),
            'hasStudent' => $student !== null,
            'termLabel' => Labels::termLabel($term),
            'current' => $current ? [
                'name' => $current->tutor->name,
                'since' => Labels::date($current->created_at),
            ] : null,
            'pending' => $pending->map(fn (TutorRequest $t) => [
                'tutor' => $t->tutor->name,
                'message' => $t->mensaje,
                'sentAt' => Labels::date($t->created_at),
                'acceptUrl' => route('my-tutor.accept', $t),
                'rejectUrl' => route('my-tutor.reject', $t),
            ])->all(),
            'history' => $history->map(function (TutorRequest $t) {
                [$status, $tone] = Labels::tutorRequestStatus($t->estado);

                return ['tutor' => $t->tutor->name, 'status' => $status, 'tone' => $tone, 'date' => Labels::date($t->updated_at)];
            })->all(),
        ]);
    }

    public function accept(Request $request, TutorRequest $solicitud, TutorLinkWorkflow $workflow): RedirectResponse
    {
        $this->authorize('respond', $solicitud);

        try {
            $workflow->accept($request, $solicitud);
        } catch (DomainException $e) {
            return redirect()->route('my-tutor.show')->withErrors(['tutor' => $e->getMessage()]);
        }

        return redirect()->route('my-tutor.show')->with('status', 'Aceptó la solicitud: '.$solicitud->tutor->name.' ya puede ver su información y registrar tutorías.');
    }

    public function reject(Request $request, TutorRequest $solicitud, TutorLinkWorkflow $workflow): RedirectResponse
    {
        $this->authorize('respond', $solicitud);

        try {
            $workflow->reject($request, $solicitud);
        } catch (DomainException $e) {
            return redirect()->route('my-tutor.show')->withErrors(['tutor' => $e->getMessage()]);
        }

        return redirect()->route('my-tutor.show')->with('status', 'Rechazó la solicitud. No se compartió su información.');
    }

    public function revoke(Request $request, TutorLinkWorkflow $workflow): RedirectResponse
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'Su usuario no tiene una ficha de estudiante.');

        try {
            $workflow->revoke($request, $student);
        } catch (DomainException $e) {
            return redirect()->route('my-tutor.show')->withErrors(['tutor' => $e->getMessage()]);
        }

        return redirect()->route('my-tutor.show')->with('status', 'Dejó de compartir su información: su tutor ya no puede verla.');
    }
}
