<?php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_persisted_and_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => Role::Profesor]);

        $this->assertSame(Role::Profesor, $user->fresh()->role);
        $this->assertTrue($user->fresh()->active);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'profesor']);
    }

    public function test_the_merge_migration_turns_old_tutors_into_profesores_and_leaves_the_rest(): void
    {
        $tutorId = DB::table('users')->insertGetId(['name' => 'Tutor antiguo', 'email' => 'viejo@unal.edu.co', 'password' => 'x', 'role' => 'tutor', 'created_at' => now(), 'updated_at' => now()]);
        $student = User::factory()->create(['role' => Role::Estudiante]);

        (require database_path('migrations/2026_10_30_000001_merge_tutor_role_into_profesor.php'))->up();

        $this->assertSame(Role::Profesor, User::findOrFail($tutorId)->role);
        $this->assertSame(Role::Estudiante, $student->fresh()->role);
    }

    public function test_email_is_stored_trimmed_and_lowercased_so_login_can_find_it(): void
    {
        $user = User::factory()->create(['email' => '  Ana.Gomez@UNAL.edu.co ']);

        $this->assertSame('ana.gomez@unal.edu.co', $user->fresh()->email);
    }

    public function test_password_is_stored_as_a_hash(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->assertNotSame('secreto123', $user->getRawOriginal('password'));
        $this->assertStringStartsWith('$2y$', $user->getRawOriginal('password'));
    }

    public function test_initials_and_view_data(): void
    {
        $this->assertSame('AR', User::factory()->make(['name' => 'Ana Restrepo'])->initials());
        $this->assertSame('JP', User::factory()->make(['name' => 'Pérez Rojas, Juan Sebastián'])->initials());

        $this->assertSame(
            ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            User::factory()->make(['name' => 'Ana Restrepo', 'role' => Role::Coordinacion])->viewData()
        );
    }
}
