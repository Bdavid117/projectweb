<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInternshipRequest;
use App\Models\Internship;
use App\Support\Audit;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternshipController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $internship = $this->current($request);

        return view('internship.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'practica'),
            'internship' => $internship,
            'finished' => (bool) $internship?->fecha_fin?->isPast(),
        ]);
    }

    /** RF-22: el estudiante edita el jefe inmediato y las actividades de su práctica. */
    public function update(UpdateInternshipRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Audit::update($request, $this->current($request), [
            'jefe_nombre' => $data['jefe_nombre'],
            'jefe_cargo' => $data['jefe_cargo'] ?? null,
            'actividades' => $data['actividades'] ?? null,
        ], 'practica.actualizada');

        return redirect()->route('internship.show')->with('status', 'Práctica actualizada.');
    }

    private function current(Request $request): ?Internship
    {
        $student = $request->user()->student;

        return $student ? Internship::where('student_id', $student->id)->with(['company', 'asesor'])->latest('id')->first() : null;
    }
}
