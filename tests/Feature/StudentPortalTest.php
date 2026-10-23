<?php

namespace Tests\Feature;

use App\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_portal_screens_render_with_seeded_data_and_mark_their_menu_item(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/perfil')->assertOk()->assertSee('Información académica')->assertSee('Cohorte');
        $this->get('/historia-academica')->assertOk()->assertSee('Mi historia académica')->assertDontSee('No hay registros académicos');
        $this->get('/trabajo-de-grado')->assertOk()->assertSee('Mi trabajo de grado');
        $this->get('/practica')->assertOk()->assertSee('Mi práctica');
    }

    public function test_menu_item_is_highlighted_on_each_portal_screen(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        foreach (['/perfil' => 'Mi perfil', '/historia-academica' => 'Mi historia académica', '/trabajo-de-grado' => 'Mi trabajo de grado', '/practica' => 'Mi práctica'] as $url => $label) {
            $html = $this->get($url)->getContent();
            $this->assertMatchesRegularExpression('/<a\s[^>]*class="[^"]*bg-\[var\(--color-primary-50\)\][^"]*"[^>]*>(?:(?!<\/a>).)*<span>'.preg_quote($label, '/').'<\/span>/su', $html, $url);
        }
    }

    public function test_portal_screens_are_students_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Tutor);

        foreach (['/perfil', '/historia-academica', '/trabajo-de-grado', '/practica'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
}
