<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateExperienceRequest;
use App\Http\Requests\StoreExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function index()
    {
        $experiences = Experience::latest('updated_at')->get();

        return view('experiences.index', [
            'experiences' => $experiences,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Experience::class);

        return view('experiences.form', [
            'navGroups' => app('app.support.Navigation')->forRole(
                auth()->user()->role,
                auth()->user()->role === 'tutor' ? 'registrar-tutoria' : 'estudiantes'
            ),
        ]);
    }

    public function store(StoreExperienceRequest $request): RedirectResponse
    {
        $experience = Experience::create(array_merge($request->validated(), [
            'student_id' => auth()->user()->id,
        ]));

        return redirect()->route('experiencias.index')->with('status', 'Experiencia registrada correctamente.');
    }

    public function edit(int $id): View
    {
        $experience = Experience::findOrFail($id);

        $this->authorize('update', $experience);

        return view('experiences.form', [
            'experience' => $experience,
            'navGroups' => app('app.support.Navigation')->forRole(
                auth()->user()->role,
                auth()->user()->role === 'tutor' ? 'registrar-tutoria' : 'estudiantes'
            ),
        ]);
    }

    public function update(UpdateExperienceRequest $request, int $id): RedirectResponse
    {
        $experience = Experience::findOrFail($id);
        $this->authorize('update', $experience);

        $experience->update($request->validated());

        return redirect()->route('experiencias.index')->with('status', 'Experiencia actualizada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $experience = Experience::findOrFail($id);
        $this->authorize('delete', $experience);

        $experience->delete();

        return redirect()->route('experiencias.index')->with('status', 'Experiencia eliminada.');
    }
}