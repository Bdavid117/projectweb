<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Experience;
use App\Models\User;

class ExperiencePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Experience $experience): bool
    {
        if ($user->role === Role::Coordinacion) {
            return true;
        }

        return $experience->student_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Estudiante, Role::Coordinacion], true);
    }

    public function update(User $user, Experience $experience): bool
    {
        return $this->isOwnerWithinWindow($user, $experience);
    }

    public function delete(User $user, Experience $experience): bool
    {
        return $this->isOwnerWithinWindow($user, $experience);
    }

    private function isOwnerWithinWindow(User $user, Experience $experience): bool
    {
        return $experience->student_id === $user->id
            && $experience->updated_at->gt(now()->subHours(config('program.tutorship_edit_hours')));
    }
}