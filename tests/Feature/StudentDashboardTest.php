<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_panel_shows_their_own_data_not_the_program_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);

        $this->get('/panel')->assertOk()
            ->assertSee('Mi panel')
            ->assertSee($user->student->codigo)
            ->assertSee('PAPA ACTUAL')
            ->assertSee('AVANCE DEL PLAN')
            ->assertDontSee('ESTUDIANTES ACTIVOS');
    }

    public function test_staff_still_see_the_program_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/panel')->assertOk()->assertSee('ESTUDIANTES ACTIVOS')->assertDontSee('Mi panel');
    }
}
