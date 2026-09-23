<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('requests.create');
    }

    public function create(): View
    {
        return view('requests.create', [
            'user' => ['initials' => 'JP', 'name' => 'Juan Pérez', 'role' => 'Estudiante'],
            'navGroups' => Navigation::studentPortal('solicitudes'),
            'requestTypes' => [
                'Cancelación de asignatura',
                'Reingreso al programa',
                'Homologación de créditos',
                'Ampliación de cupo de créditos',
                'Otras',
            ],
            'steps' => [
                ['number' => 1, 'title' => 'Radicada', 'detail' => 'Queda registrada con fecha y ya no puede editarla.'],
                ['number' => 2, 'title' => 'En revisión', 'detail' => 'El comité asesor estudia el caso.'],
                ['number' => 3, 'title' => 'Con decisión', 'detail' => 'Coordinación carga la decisión y el número de acta.'],
            ],
        ]);
    }
}
