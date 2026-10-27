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

    public function test_student_updates_contact_data_and_the_change_is_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);

        $this->get('/perfil')->assertOk()->assertSee('Guardar cambios');
        $this->put('/perfil', ['name' => $user->name, 'email' => $user->email, 'telefono' => '300 123 4567', 'ciudad' => 'Manizales', 'direccion' => 'Calle 1 # 2-3'])
            ->assertRedirect('/perfil');

        $this->assertSame('300 123 4567', $user->student->fresh()->telefono);
        $log = \App\Models\AuditLog::where('accion', 'perfil.contacto_actualizado')->firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Manizales', $log->valores_nuevos['ciudad']);

        $this->put('/perfil', ['name' => $user->name, 'email' => $user->email, 'telefono' => '300 123 4567', 'ciudad' => 'Manizales', 'direccion' => 'Calle 1 # 2-3']);
        $this->assertSame(1, \App\Models\AuditLog::count());
    }

    public function test_profile_update_validates_in_spanish_and_is_students_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->from('/perfil')->put('/perfil', ['name' => '', 'email' => 'no-es-correo', 'telefono' => 'abc'])
            ->assertRedirect('/perfil')
            ->assertSessionHasErrors(['name' => 'Indique su nombre completo.', 'email' => 'El correo electrónico no es válido.', 'telefono' => 'El teléfono solo puede tener números, espacios y los símbolos + ( ) -.']);

        $this->signInSeeded(Role::Tutor);
        $this->put('/perfil', ['name' => 'X', 'email' => 'x@unal.edu.co'])->assertForbidden();
    }
}
