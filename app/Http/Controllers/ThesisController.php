<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThesisController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        $thesis = $student ? Thesis::where('student_id', $student->id)->with(['director', 'codirector', 'term'])->latest('id')->first() : null;

        return view('thesis.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'trabajo-grado'),
            'thesis' => $thesis,
            'status' => $thesis ? match ($thesis->estado) {
                'en_desarrollo' => ['En desarrollo', 'warning'],
                'finalizado' => ['Finalizado', 'success'],
                default => [ucfirst(str_replace('_', ' ', $thesis->estado)), 'info'],
            } : null,
        ]);
    }
}