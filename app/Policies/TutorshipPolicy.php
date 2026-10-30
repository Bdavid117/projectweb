<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;

class TutorshipPolicy
{
    /** Sin estudiante solo valida el rol; con estudiante, el profesor debe tener el vínculo vigente (el estudiante aceptó). */
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

    public function update(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship) && $this->keepsAccess($user, $tutorship);
    }

    public function delete(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship) && $this->keepsAccess($user, $tutorship);
    }

    /** Si el estudiante dejó de compartir, el profesor ya no puede abrir ni modificar sus tutorías, ni siquiera las suyas. */
    private function keepsAccess(User $user, Tutorship $tutorship): bool
    {
        return $user->role === Role::Coordinacion || TutorAssignment::isLinked($user, $tutorship->student);
    }

    private function isAuthorInsideWindow(User $user, Tutorship $tutorship): bool
    {
        return $user->id === $tutorship->tutor_id
            && $tutorship->created_at->gt(now()->subHours(config('program.tutorship_edit_hours')));
    }
}
