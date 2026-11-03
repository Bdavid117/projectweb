<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Support\Labels;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        $records = $student ? AcademicRecord::where('student_id', $student->id)
            ->with('term')
            ->get()
            ->sortByDesc(fn (AcademicRecord $record) => $record->term->fecha_inicio)
            ->values() : collect();

        // Series en orden cronológico (de más antiguo a más reciente) para graficar la evolución · RF-16.
        $chronological = $records->reverse()->values();
        $maxHeight = 140;

        return view('academic-history.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'historia'),
            'records' => $records,
            'papaSeries' => $chronological->map(fn (AcademicRecord $r) => [
                'period' => $r->term->codigo,
                'label' => Labels::decimal($r->papa),
                'height' => (int) round((float) $r->papa / 5 * $maxHeight),
            ])->all(),
            'creditSeries' => $chronological->map(fn (AcademicRecord $r) => [
                'period' => $r->term->codigo,
                'label' => (int) round((float) $r->porcentaje_avance).'%',
                'credits' => $r->creditos_cursados,
                'height' => (int) round((float) $r->porcentaje_avance / 100 * $maxHeight),
            ])->all(),
        ]);
    }
}
