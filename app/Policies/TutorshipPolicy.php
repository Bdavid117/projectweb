<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;

class TutorshipPolicy
{
    /** Sin estudiante solo valida el rol; con estudiante, el tutor debe tenerlo asignado en el periodo vigente. */
    public function create(User $user, ?Student $student = null): bool
    {
        if ($user->role === Role::Coordinacion) {
            return true;
        }

        if ($user->role !== Role::Tutor) {
            return false;
        }

        return $student === null || $this->isAssigned($user, $student);
    }

    public function update(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    public function delete(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    private function isAssigned(User $user, Student $student): bool
    {
        $term = AcademicTerm::current();

        return $term !== null && TutorAssignment::where('tutor_id', $user->id)
            ->where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->exists();
    }

    private function isAuthorInsideWindow(User $user, Tutorship $tutorship): bool
    {
        return $user->id === $tutorship->tutor_id
            && $tutorship->created_at->gt(now()->subHours(config('program.tutorship_edit_hours')));
    }
}
