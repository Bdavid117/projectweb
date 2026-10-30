<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\TutorRequest;
use App\Models\User;

class TutorRequestPolicy
{
    /** Cualquier profesor puede pedir acompañar a un estudiante; el estudiante decide si acepta. */
    public function create(User $user): bool
    {
        return $user->role === Role::Profesor;
    }

    /** Solo el estudiante destinatario acepta o rechaza, y una sola vez. */
    public function respond(User $user, TutorRequest $request): bool
    {
        return $user->role === Role::Estudiante
            && $request->student?->user_id === $user->id
            && $request->isPending();
    }

    /** El profesor solo retira sus propias solicitudes que siguen pendientes. */
    public function cancel(User $user, TutorRequest $request): bool
    {
        return $user->role === Role::Profesor
            && $request->tutor_id === $user->id
            && $request->isPending();
    }
}
