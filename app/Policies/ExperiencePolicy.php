<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Experience;
use App\Models\User;

class ExperiencePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Estudiante, Role::Coordinacion], true);
    }

    /** Solo el estudiante registra sus experiencias; Coordinación las valida. */
    public function create(User $user): bool
    {
        return $user->role === Role::Estudiante && $user->student !== null;
    }

    public function update(User $user, Experience $experience): bool
    {
        return $user->role === Role::Coordinacion || $this->isPendingOwner($user, $experience);
    }

    public function delete(User $user, Experience $experience): bool
    {
        return $this->update($user, $experience);
    }

    public function validate(User $user, Experience $experience): bool
    {
        return $user->role === Role::Coordinacion && ! $experience->validada;
    }

    private function isPendingOwner(User $user, Experience $experience): bool
    {
        return ! $experience->validada && $user->student?->id === $experience->student_id;
    }
}
