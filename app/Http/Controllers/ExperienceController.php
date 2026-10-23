<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreExperienceRequest;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\User;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Experience::class);

        $user = $request->user();
        $isStudent = $user->role === Role::Estudiante;
        $filters = ['estado' => Input::text($request, 'estado')];

        $experiences = Experience::query()
            ->with(['type', 'student.user'])
            ->when($isStudent, fn ($q) => $q->where('student_id', $user->student?->id ?? 0))
            ->when($filters['estado'] === 'validada', fn ($q) => $q->where('validada', true))
            ->when($filters['estado'] === 'pendiente', fn ($q) => $q->where('validada', false))
            ->latest('fecha_inicio')->latest('id')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('experiences.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'experiencias'),
            'experiences' => $experiences->through(fn (Experience $e) => [
                'id' => $e->id,
                'name' => $e->nombre,
                'type' => $e->type->nombre,
                'student' => $e->student->user->name ?? $e->student->codigo,
                'period' => Labels::date($e->fecha_inicio).' – '.($e->fecha_fin ? Labels::date($e->fecha_fin) : 'en curso'),
                'validated' => $e->validada,
                'canEdit' => $user->can('update', $e),
                'canValidate' => $user->can('validate', $e),
            ]),
            'filters' => $filters,
            'statusOptions' => ['validada' => 'Validada', 'pendiente' => 'Pendiente de validación'],
            'isStudent' => $isStudent,
            'canCreate' => $user->can('create', Experience::class),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Experience::class);

        return $this->form($request, null);
    }

    public function store(StoreExperienceRequest $request): RedirectResponse
    {
        $request->user()->student->experiences()->create($request->validated());

        return redirect()->route('experiencias.index')->with('status', 'Experiencia registrada. Quedó pendiente de validación.');
    }

    public function edit(Request $request, Experience $experiencia): View
    {
        $this->authorize('update', $experiencia);

        return $this->form($request, $experiencia);
    }

    public function update(StoreExperienceRequest $request, Experience $experiencia): RedirectResponse
    {
        $experiencia->update($request->validated());

        return redirect()->route('experiencias.index')->with('status', 'Experiencia actualizada correctamente.');
    }

    public function destroy(Experience $experiencia): RedirectResponse
    {
        $this->authorize('delete', $experiencia);
        $experiencia->delete();

        return redirect()->route('experiencias.index')->with('status', 'Experiencia eliminada.');
    }

    public function validateExperience(Request $request, Experience $experiencia): RedirectResponse
    {
        $this->authorize('validate', $experiencia);
        $experiencia->update(['validada' => true, 'validada_por' => $request->user()->id]);

        return redirect()->route('experiencias.index')->with('status', 'Experiencia validada.');
    }

    private function form(Request $request, ?Experience $experience): View
    {
        $user = $request->user();

        return view('experiences.form', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'experiencias'),
            'experience' => $experience,
            'types' => ExperienceType::orderBy('categoria')->orderBy('nombre')->get()->groupBy('categoria'),
            'teachers' => User::where('role', Role::Profesor->value)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
