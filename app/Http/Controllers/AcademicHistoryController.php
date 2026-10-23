<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
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

        return view('academic-history.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'historia'),
            'records' => $records,
        ]);
    }
}