<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Support\Navigation;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThesisController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        $thesis = $student ? Thesis::where('student_id', $student->id)->first() : null;

        return view('thesis.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'trabajo-grado'),
            'thesis' => $thesis,
        ]);
    }
}