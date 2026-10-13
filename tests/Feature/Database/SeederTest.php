<?php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_one_demo_user_per_role_with_a_known_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (Role::cases() as $role) {
            $this->assertTrue(User::where('role', $role->value)->exists(), "Sin usuario {$role->value}");
        }
        $this->assertTrue(Hash::check('password', User::where('email', 'coordinacion@unal.edu.co')->value('password')));
    }

    public function test_seeders_create_the_roster_and_juan_perez_history(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, Student::count());
        $juan = Student::where('codigo', '2020123')->firstOrFail();
        $this->assertSame(7, $juan->academicRecords()->count());
        $this->assertSame('4.10', $juan->academicRecords()->whereHas('term', fn ($q) => $q->where('codigo', '2026-2'))->first()->papa);
        $this->assertSame(3, $juan->tutorships()->count());
        $this->assertGreaterThanOrEqual(4, $juan->experiences()->count());
        $this->assertTrue(AcademicTerm::where('codigo', '2019-1')->exists());
    }

    public function test_seeding_twice_does_not_duplicate_or_fail(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Student::count(), AcademicTerm::count(), Student::where('codigo', '2020123')->firstOrFail()->tutorships()->count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Student::count(), AcademicTerm::count(), Student::where('codigo', '2020123')->firstOrFail()->tutorships()->count()]);
    }
}
