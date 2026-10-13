<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreTutorshipRequest;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\Tutorship;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutorshipController extends Controller
{
    public function create(Request $request, Student $student): View
    {
        $this->authorize('create', [Tutorship::class, $student]);

        return $this->form($request, $student, null);
    }

    public function store(StoreTutorshipRequest $request, Student $student): RedirectResponse
    {
        $student->tutorships()->create($request->validated() + ['tutor_id' => $request->user()->id]);

        return redirect()->route('students.show', $student)->with('status', 'Tutoría registrada correctamente.');
    }

    public function edit(Request $request, Tutorship $tutoria): View
    {
        $this->authorize('update', $tutoria);

        return $this->form($request, $tutoria->student, $tutoria);
    }

    public function update(StoreTutorshipRequest $request, Tutorship $tutoria): RedirectResponse
    {
        $tutoria->update($request->validated());

        return redirect()->route('students.show', $tutoria->student_id)->with('status', 'Tutoría actualizada correctamente.');
    }

    public function destroy(Request $request, Tutorship $tutoria): RedirectResponse
    {
        $this->authorize('delete', $tutoria);
        $tutoria->delete();

        return redirect()->route('students.show', $tutoria->student_id)->with('status', 'Tutoría eliminada.');
    }

    private function form(Request $request, Student $student, ?Tutorship $tutorship): View
    {
        $user = $request->user();

        return view('tutorships.form', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, $user->role === Role::Tutor ? 'registrar-tutoria' : 'estudiantes'),
            'student' => StudentProfile::header($student),
            'tutorship' => $tutorship,
            'reasons' => config('program.tutorship_reasons'),
            'history' => $student->tutorships()->latest('fecha')->limit(3)->get()
                ->map(fn (Tutorship $t) => ['date' => Labels::date($t->fecha), 'reason' => $t->motivo])->all(),
            'termLabel' => AcademicTerm::current()?->codigo ?? 'Sin periodo',
            'hours' => config('program.tutorship_edit_hours'),
        ]);
    }
}
