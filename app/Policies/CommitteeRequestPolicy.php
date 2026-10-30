<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\Student;
use App\Models\User;

class CommitteeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Coordinación ve todas; el estudiante las suyas; el profesor las de quienes aceptaron su solicitud de tutoría. */
    public function view(User $user, CommitteeRequest $request): bool
    {
        return Student::query()->visibleTo($user)->whereKey($request->student_id)->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Estudiante, Role::Coordinacion], true);
    }

    public function decide(User $user, CommitteeRequest $request): bool
    {
        return $user->role === Role::Coordinacion;
    }
}
