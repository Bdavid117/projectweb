<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Tutorship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TutorshipPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tutorship(int $tutorId, string $createdAt): Tutorship
    {
        $tutorship = new Tutorship;
        $tutorship->tutor_id = $tutorId;
        $tutorship->created_at = Carbon::parse($createdAt);

        return $tutorship;
    }

    private function allows(Role $role, string $ability, mixed $argument, int $id = 7): bool
    {
        return Gate::forUser(User::factory()->make(['id' => $id, 'role' => $role]))->allows($ability, $argument);
    }

    public function test_only_tutors_and_coordination_create(): void
    {
        $this->assertTrue($this->allows(Role::Tutor, 'create', Tutorship::class));
        $this->assertTrue($this->allows(Role::Coordinacion, 'create', Tutorship::class));
        $this->assertFalse($this->allows(Role::Profesor, 'create', Tutorship::class));
        $this->assertFalse($this->allows(Role::Estudiante, 'create', Tutorship::class));
    }

    public function test_the_author_edits_and_deletes_only_inside_the_24_hour_window(): void
    {
        $this->assertTrue($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 12:01:00')));
        $this->assertTrue($this->allows(Role::Tutor, 'delete', $this->tutorship(7, '2026-10-11 12:01:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 12:00:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'update', $this->tutorship(7, '2026-10-11 11:59:00')));
        $this->assertFalse($this->allows(Role::Tutor, 'delete', $this->tutorship(7, '2026-10-11 11:59:00')));
    }

    public function test_nobody_else_edits_even_inside_the_window(): void
    {
        foreach ([Role::Tutor, Role::Coordinacion, Role::Profesor, Role::Estudiante] as $role) {
            $this->assertFalse($this->allows($role, 'update', $this->tutorship(7, '2026-10-12 11:00:00'), 99));
        }
    }
}
