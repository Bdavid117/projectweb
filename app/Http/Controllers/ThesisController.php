<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpdateThesisRequest;
use App\Models\Thesis;
use App\Models\User;
use App\Support\Audit;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThesisController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $thesis = $this->current($request);

        return view('thesis.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'trabajo-grado'),
            'thesis' => $thesis,
            'teachers' => User::where('role', Role::Profesor->value)->orderBy('name')->pluck('name', 'id'),
            'status' => $thesis ? match ($thesis->estado) {
                'en_desarrollo' => ['En desarrollo', 'warning'],
                'finalizado' => ['Finalizado', 'success'],
                default => [ucfirst(str_replace('_', ' ', $thesis->estado)), 'info'],
            } : null,
        ]);
    }

    /** RF-19: el estudiante edita el título, el repositorio y el codirector de su trabajo de grado. */
    public function update(UpdateThesisRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Audit::update($request, $this->current($request), [
            'titulo' => $data['titulo'],
            'repositorio_url' => $data['repositorio_url'] ?? null,
            'codirector_id' => $data['codirector_id'] ?? null,
        ], 'trabajo_grado.actualizado');

        return redirect()->route('thesis.show')->with('status', 'Trabajo de grado actualizado.');
    }

    private function current(Request $request): ?Thesis
    {
        $student = $request->user()->student;

        return $student ? Thesis::where('student_id', $student->id)->with(['director', 'codirector', 'term'])->latest('id')->first() : null;
    }
}
