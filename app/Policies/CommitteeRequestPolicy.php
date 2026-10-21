<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\User;

class CommitteeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CommitteeRequest $request): bool
    {
        return $user->role !== Role::Estudiante || $request->student?->user_id === $user->id;
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
