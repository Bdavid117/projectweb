<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use App\Support\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->label(),
            'active' => $user->active ? 'Activo' : 'Inactivo',
        ];

        return view('profile.show', [
            'user' => $user->viewData(),
            'navGroups' => Navigation::forRole($user->role, 'perfil'),
            'profile' => $profileData,
            'student' => $user->student ? StudentProfile::header($user->student) : null,
        ]);
    }
}