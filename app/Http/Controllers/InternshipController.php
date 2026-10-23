<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InternshipController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        $internship = $student ? Internship::where('student_id', $student->id)
            ->with(['company', 'asesor'])->latest('id')
            ->first() : null;

        return view('internship.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'practica'),
            'internship' => $internship,
            'finished' => (bool) $internship?->fecha_fin?->isPast(),
        ]);
    }
}