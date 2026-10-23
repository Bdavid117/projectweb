<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Support\Navigation;
use App\Enums\Role;
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
            ->orderByDesc('created_at')
            ->get() : collect();

        return view('academic-history.index', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'historia'),
            'records' => $records,
        ]);
    }
}