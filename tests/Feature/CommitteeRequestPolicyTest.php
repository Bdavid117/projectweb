<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CommitteeRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CommitteeRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function requestOf(string $codigo): CommitteeRequest
    {
        return CommitteeRequest::whereHas('student', fn ($q) => $q->where('codigo', $codigo))->firstOrFail();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function allows(User $user, string $ability, mixed $argument): bool
    {
        return Gate::forUser($user)->allows($ability, $argument);
    }

    private function withRole(Role $role): User
    {
        return User::factory()->make(['id' => 1, 'role' => $role]);
    }

    public function test_a_student_views_only_their_own_requests(): void
    {
        $juan = $this->requestOf('2020123');

        $this->assertTrue($this->allows($this->user('estudiante@unal.edu.co'), 'view', $juan));
        $this->assertFalse($this->allows($this->user('est2021045@unal.edu.co'), 'view', $juan));
    }

    public function test_coordination_views_any_request_and_a_professor_only_those_of_their_tutored_students(): void
    {
        foreach (['2020123', '2021045'] as $codigo) {
            $request = $this->requestOf($codigo);

            $this->assertTrue($this->allows($this->user('coordinacion@unal.edu.co'), 'view', $request));
            // Juan y Laura son tutorados de Carlos Pineda; profesor@ no tiene vínculo con ninguno.
            $this->assertTrue($this->allows($this->user('profesor2@unal.edu.co'), 'view', $request));
            $this->assertFalse($this->allows($this->user('profesor@unal.edu.co'), 'view', $request));
        }
    }

    public function test_everyone_can_list_but_only_student_and_coordination_create(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertTrue($this->allows($this->withRole($role), 'viewAny', CommitteeRequest::class));
        }
        $this->assertTrue($this->allows($this->withRole(Role::Estudiante), 'create', CommitteeRequest::class));
        $this->assertTrue($this->allows($this->withRole(Role::Coordinacion), 'create', CommitteeRequest::class));
        $this->assertFalse($this->allows($this->withRole(Role::Profesor), 'create', CommitteeRequest::class));
    }

    public function test_only_coordination_decides(): void
    {
        $request = $this->requestOf('2020123');

        $this->assertTrue($this->allows($this->user('coordinacion@unal.edu.co'), 'decide', $request));
        foreach (['estudiante@unal.edu.co', 'profesor@unal.edu.co', 'profesor2@unal.edu.co'] as $email) {
            $this->assertFalse($this->allows($this->user($email), 'decide', $request), $email);
        }
    }
}
