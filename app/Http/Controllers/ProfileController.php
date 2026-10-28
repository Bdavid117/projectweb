<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Support\Audit;
use App\Support\Navigation;
use App\Support\StudentProfile;
use Illuminate\Http\RedirectResponse;
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
            'contact' => $user->student,
        ]);
    }

    /** RF-06: el estudiante actualiza sus datos personales y de contacto; cada cambio queda en la bitácora. */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        Audit::update($request, $user, ['name' => $data['name'], 'email' => $data['email']], 'perfil.cuenta_actualizada');
        Audit::update($request, $user->student, [
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
        ], 'perfil.contacto_actualizado');

        return redirect()->route('profile.show')->with('status', 'Datos actualizados correctamente.');
    }
}
