<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Alert;
use App\Models\TutorAssignment;
use App\Models\User;

class AlertPolicy
{
    /** Atiende una alerta Coordinación o el tutor vigente del estudiante. */
    public function attend(User $user, Alert $alert): bool
    {
        return $user->role === Role::Coordinacion
            || ($user->role === Role::Profesor && TutorAssignment::isLinked($user, $alert->student));
    }
}
