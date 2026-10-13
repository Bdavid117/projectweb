<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Tutorship;
use App\Models\User;

class TutorshipPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [Role::Tutor, Role::Coordinacion], true);
    }

    public function update(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    public function delete(User $user, Tutorship $tutorship): bool
    {
        return $this->isAuthorInsideWindow($user, $tutorship);
    }

    private function isAuthorInsideWindow(User $user, Tutorship $tutorship): bool
    {
        return $user->id === $tutorship->tutor_id
            && $tutorship->created_at->gt(now()->subHours(config('program.tutorship_edit_hours')));
    }
}
