<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /** Misma regla que Student::visibleTo(): Coordinación, el propio estudiante o el profesor con vínculo vigente. */
    public function view(User $user, Student $student): bool
    {
        return Student::query()->visibleTo($user)->whereKey($student->id)->exists();
    }
}
