<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CommitteeRequestPolicyTest extends TestCase
{
    private function request(int $ownerUserId): CommitteeRequest
    {
        $student = new Student(['codigo' => 'X']);
        $student->user_id = $ownerUserId;
        $request = new CommitteeRequest;
        $request->setRelation('student', $student);

        return $request;
    }

    private function allows(Role $role, string $ability, mixed $argument, int $id = 1): bool
    {
        return Gate::forUser(User::factory()->make(['id' => $id, 'role' => $role]))->allows($ability, $argument);
    }

    public function test_a_student_views_only_their_own_requests(): void
    {
        $this->assertTrue($this->allows(Role::Estudiante, 'view', $this->request(5), 5));
        $this->assertFalse($this->allows(Role::Estudiante, 'view', $this->request(5), 6));
    }

    public function test_staff_roles_view_any_request(): void
    {
        foreach ([Role::Profesor, Role::Tutor, Role::Coordinacion] as $role) {
            $this->assertTrue($this->allows($role, 'view', $this->request(5)));
        }
    }

    public function test_everyone_can_list_but_only_student_and_coordination_create(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertTrue($this->allows($role, 'viewAny', CommitteeRequest::class));
        }
        $this->assertTrue($this->allows(Role::Estudiante, 'create', CommitteeRequest::class));
        $this->assertTrue($this->allows(Role::Coordinacion, 'create', CommitteeRequest::class));
        $this->assertFalse($this->allows(Role::Profesor, 'create', CommitteeRequest::class));
        $this->assertFalse($this->allows(Role::Tutor, 'create', CommitteeRequest::class));
    }

    public function test_only_coordination_decides(): void
    {
        $this->assertTrue($this->allows(Role::Coordinacion, 'decide', $this->request(5)));
        foreach ([Role::Estudiante, Role::Profesor, Role::Tutor] as $role) {
            $this->assertFalse($this->allows($role, 'decide', $this->request(5), 5));
        }
    }
}
