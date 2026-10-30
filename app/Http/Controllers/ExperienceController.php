<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreExperienceRequest;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\Student;
use App\Models\User;
use App\Support\ExperienceFields;
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
            ->when($user->role !== Role::Coordinacion, fn ($q) => $q->whereIn('student_id', Student::query()->visibleTo($user)->select('students.id')))
            ->when($filters['estado'] === 'validada', fn ($q) => $q->where('validada', true))
            ->when($filters['estado'] === 'pendiente', fn ($q) => $q->where('validada', false)->whereNull('motivo_rechazo'))
            ->when($filters['estado'] === 'rechazada', fn ($q) => $q->where('validada', false)->whereNotNull('motivo_rechazo'))
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
                'status' => $e->estado(),
                'reason' => $e->isRejected() ? $e->motivo_rechazo : null,
                'canEdit' => $user->can('update', $e),
                'canValidate' => $user->can('validate', $e),
                'canReject' => $user->can('reject', $e),
            ]),
            'filters' => $filters,
            'statusOptions' => ['validada' => 'Validada', 'pendiente' => 'Pendiente de validación', 'rechazada' => 'Rechazada'],
            'isStudent' => $isStudent,
            'canCreate' => $user->can('create', Experience::class),
            'canReview' => $user->role === Role::Coordinacion,
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
        // Al corregir una experiencia rechazada vuelve a quedar pendiente de validación.
        $experiencia->update($request->validated() + ['motivo_rechazo' => null, 'rechazada_por' => null]);

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

    public function reject(Request $request, Experience $experiencia): RedirectResponse
    {
        $this->authorize('reject', $experiencia);
        $data = $request->validate(
            ['motivo' => ['required', 'string', 'max:1000']],
            ['motivo.required' => 'Indique el motivo del rechazo.', 'motivo.max' => 'El motivo no puede superar los :max caracteres.'],
        );
        $experiencia->update(['motivo_rechazo' => $data['motivo'], 'rechazada_por' => $request->user()->id]);

        return redirect()->route('experiencias.index')->with('status', 'Experiencia rechazada. El estudiante verá el motivo.');
    }

    private function form(Request $request, ?Experience $experience): View
    {
        $user = $request->user();

        return view('experiences.form', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'experiencias'),
            'experience' => $experience,
            'types' => ExperienceType::orderBy('categoria')->orderBy('nombre')->get()->groupBy('categoria'),
            'fieldsByCategory' => ExperienceFields::all(),
            'typeCategories' => ExperienceType::pluck('categoria', 'id'),
            'teachers' => User::where('role', Role::Profesor->value)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
