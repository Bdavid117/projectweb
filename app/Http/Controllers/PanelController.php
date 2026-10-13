<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\ProgramMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request, ProgramMetrics $metrics): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === Role::Estudiante) {
            return redirect()->route('requests.index');
        }

        return view('panel.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'panel'),
            'cutoff' => $metrics->cutoff(),
            'termLabel' => Labels::termLabel($metrics->term()),
            'showAttention' => $user->role !== Role::Profesor,
            ...$metrics->panel($user),
            'quickLinks' => [
                ['icon' => 'upload', 'label' => 'Cargar archivo de Excel'],
                ['icon' => 'file-plus', 'label' => 'Registrar decisión del comité', 'href' => '/solicitudes'],
                ['icon' => 'user-plus', 'label' => 'Crear usuario y asignar rol'],
            ],
        ]);
    }
}
