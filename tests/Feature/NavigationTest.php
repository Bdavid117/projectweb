<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_internal_screen_links_to_the_other_implemented_screens(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $this->get('/panel')->assertSee('href="/estudiantes"', false);

        $students = $this->get('/estudiantes');
        $students->assertSee('href="'.route('students.show', $juan).'"', false);
        $students->assertSee('href="/indicadores"', false);

        $this->get('/solicitudes/nueva')->assertSee('href="/panel"', false);
    }

    public function test_all_screens_return_200(): void
    {
        $this->seed(DatabaseSeeder::class);
        $juan = Student::where('codigo', '2020123')->firstOrFail();

        $this->get('/login')->assertOk();

        $this->signInSeeded(Role::Coordinacion);
        foreach (['/panel', '/estudiantes', '/estudiantes/'.$juan->id, '/solicitudes/nueva', '/indicadores'] as $route) {
            $this->get($route)->assertOk();
        }
    }
}
