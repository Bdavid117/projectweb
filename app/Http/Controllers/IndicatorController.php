<?php

namespace App\Http\Controllers;

use App\Support\Labels;
use App\Support\Navigation;
use App\Support\ProgramMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(Request $request, ProgramMetrics $metrics): View
    {
        $user = $request->user();

        return view('indicators.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'indicadores'),
            'cutoff' => $metrics->cutoff(),
            'termLabel' => Labels::termLabel($metrics->term()),
            ...$metrics->indicators(),
        ]);
    }
}
