<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use App\Support\ProgramMetrics;
use App\Support\StudentDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request, ProgramMetrics $metrics): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === Role::Estudiante) {
            return view('panel.student', [
                'user' => $user->viewData(),
                'navGroups' => Navigation::forRole($user->role, 'panel'),
                'dashboard' => $user->student ? StudentDashboard::for($user->student) : null,
            ]);
        }

        // `?tipo=en-riesgo,con-alerta`: los tipos se combinan (todos a la vez). Lo desconocido se descarta y «todos» no suma nada.
        $kinds = collect(explode(',', Input::text($request, 'tipo')))->map(fn ($k) => trim($k))
            ->filter(fn ($k) => $k !== 'todos' && in_array($k, ProgramMetrics::mapKinds(), true))->unique()->values();

        return view('panel.index', [
            'cohortFilter' => $kinds->isEmpty() ? 'todos' : $kinds->implode(','),
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'panel'),
            'cutoff' => $metrics->cutoff(),
            'termLabel' => Labels::termLabel($metrics->term()),
            ...$metrics->panel($user),
        ]);
    }
}
