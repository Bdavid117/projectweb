<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TutorshipPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $carlos;

    private User $maria;

    private Student $juan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        $this->seed(DatabaseSeeder::class);

        $this->carlos = User::where('email', 'profesor2@unal.edu.co')->firstOrFail();
        $this->maria = User::where('email', 'profesor@unal.edu.co')->firstOrFail();
        $this->juan = Student::where('codigo', '2020123')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tutorship(User $author, string $createdAt): Tutorship
    {
        $tutorship = new Tutorship(['student_id' => $this->juan->id, 'tutor_id' => $author->id, 'fecha' => '2026-10-01', 'motivo' => 'Revisión de avance', 'observaciones' => 'Observación de prueba.']);
        $tutorship->forceFill(['created_at' => Carbon::parse($createdAt)])->save();

        return $tutorship;
    }

    private function allows(User $user, string $ability, mixed $argument): bool
    {
        return Gate::forUser($user)->allows($ability, $argument);
    }

    public function test_only_professors_and_coordination_create(): void
    {
        $this->assertTrue($this->allows($this->carlos, 'create', Tutorship::class));
        $this->assertTrue($this->allows(User::factory()->make(['id' => 7, 'role' => Role::Coordinacion]), 'create', Tutorship::class));
        $this->assertFalse($this->allows(User::factory()->make(['id' => 7, 'role' => Role::Estudiante]), 'create', Tutorship::class));
    }

    public function test_a_professor_creates_only_for_a_student_who_accepted_them(): void
    {
        $this->assertTrue($this->allows($this->carlos, 'create', [Tutorship::class, $this->juan]));
        $this->assertFalse($this->allows($this->maria, 'create', [Tutorship::class, $this->juan]));
        $this->assertTrue($this->allows(User::factory()->make(['id' => 7, 'role' => Role::Coordinacion]), 'create', [Tutorship::class, $this->juan]));
    }

    public function test_the_author_edits_and_deletes_only_inside_the_24_hour_window(): void
    {
        $this->assertTrue($this->allows($this->carlos, 'update', $this->tutorship($this->carlos, '2026-10-11 12:01:00')));
        $this->assertTrue($this->allows($this->carlos, 'delete', $this->tutorship($this->carlos, '2026-10-11 12:01:00')));
        $this->assertFalse($this->allows($this->carlos, 'update', $this->tutorship($this->carlos, '2026-10-11 12:00:00')));
        $this->assertFalse($this->allows($this->carlos, 'update', $this->tutorship($this->carlos, '2026-10-11 11:59:00')));
        $this->assertFalse($this->allows($this->carlos, 'delete', $this->tutorship($this->carlos, '2026-10-11 11:59:00')));
    }

    public function test_nobody_else_edits_even_inside_the_window(): void
    {
        $tutorship = $this->tutorship($this->carlos, '2026-10-12 11:00:00');

        foreach ([$this->maria, User::where('email', 'coordinacion@unal.edu.co')->firstOrFail(), User::where('email', 'estudiante@unal.edu.co')->firstOrFail()] as $user) {
            $this->assertFalse($this->allows($user, 'update', $tutorship), $user->email);
            $this->assertFalse($this->allows($user, 'delete', $tutorship), $user->email);
        }
    }

    public function test_the_author_loses_access_to_their_own_tutorship_once_the_student_stops_sharing(): void
    {
        $tutorship = $this->tutorship($this->carlos, '2026-10-12 11:00:00');
        $this->assertTrue($this->allows($this->carlos, 'update', $tutorship));

        TutorAssignment::where('tutor_id', $this->carlos->id)->where('student_id', $this->juan->id)->delete();

        $this->assertFalse($this->allows($this->carlos, 'update', $tutorship->fresh()));
        $this->assertFalse($this->allows($this->carlos, 'delete', $tutorship->fresh()));
    }
}
