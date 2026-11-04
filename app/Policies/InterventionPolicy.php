<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Intervention;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\User;

/**
 * RF-36: registran y cierran intervenciones Coordinación y el profesor con vínculo vigente
 * (misma regla de consentimiento del PR #52 que protege la ficha del estudiante).
 */
class InterventionPolicy
{
    /** Sin estudiante solo valida el rol; con estudiante, el profesor debe tener el vínculo vigente. */
    public function create(User $user, ?Student $student = null): bool
    {
        if ($user->role === Role::Coordinacion) {
            return true;
        }

        if ($user->role !== Role::Profesor) {
            return false;
        }

        return $student === null || TutorAssignment::isLinked($user, $student);
    }

    /** Cerrar o corregir: Coordinación, o el profesor que sigue con vínculo vigente con el estudiante. */
    public function update(User $user, Intervention $intervention): bool
    {
        return $this->create($user, $intervention->student);
    }
}
