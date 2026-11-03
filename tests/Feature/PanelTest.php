<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Student;
use App\Models\TutorAssignment;
use App\Support\ProgramMetrics;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_renders_real_indicators_and_attention_table(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Periodo 2026-2, con datos al');
        $response->assertSee('6 estudiantes activos, 3 con situaciones por atender.');
        $response->assertSee('El PAPA medio es 3,7');
        $response->assertSee('3 décimas menos que el periodo anterior');
        $response->assertSee('el avance medio del plan es 44 %');
        $response->assertSee('Dónde está cada cohorte');
        $response->assertSee('Piden atención');
        $response->assertSee('Pérez Rojas, Juan Sebastián');
        $response->assertSee('Sin atender');
        $response->assertSee('Atendida');
        $response->assertDontSeeText('312');
        $response->assertDontSeeText('20/09/2026');
    }

    public function test_every_map_dot_is_a_link_to_the_student_file_with_the_data_of_its_card(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);
        $ruiz = Student::where('codigo', '2019087')->firstOrFail();

        $html = $this->get('/panel')->getContent();

        $this->assertStringContainsString('href="'.route('students.show', $ruiz).'"', $html);
        $this->assertStringContainsString('data-name="Ruiz Salazar, Andrés"', $html);
        $this->assertStringContainsString('data-pct="88"', $html);
        $this->assertStringContainsString('data-papa="3,2"', $html);
        $this->assertStringContainsString('data-cohort="2019-2"', $html);
        $this->assertSame(8, substr_count($html, 'data-dot'), 'Un punto por estudiante.');
    }

    public function test_the_map_has_type_filters_zoom_controls_and_a_live_count(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $html = $this->get('/panel')->getContent();

        // Varios tipos se combinan (Y): son botones que se pulsan o sueltan, no una opción única.
        $this->assertStringContainsString('role="group" aria-label="Tipo de estudiante"', $html);
        $this->assertStringNotContainsString('role="radiogroup" aria-label="Tipo de estudiante"', $html);
        foreach (['Todos', 'Activos', 'En riesgo', 'Con alerta', 'En práctica', 'Trabajo de grado', 'Rezagados', 'Sin tutoría reciente', 'Intercambio'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        foreach (['Acercar', 'Alejar', 'Restablecer la vista', 'Ajustar a los estudiantes visibles'] as $control) {
            $this->assertStringContainsString('aria-label="'.$control.'"', $html);
        }
        $this->assertStringContainsString('aria-live="polite"', $html);
        // El estado «pulsado» que oye un lector de pantalla debe seguir a los botones elegidos, no quedarse con el del servidor.
        $this->assertStringContainsString(':aria-pressed="isOn(', $html);
        $this->assertStringContainsString('Ctrl', $html, 'El mapa explica cómo acercar con la rueda.');
    }

    public function test_the_filter_can_come_from_the_address_and_ignores_unknown_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/panel?tipo=en-riesgo')->assertSee('data-filter="en-riesgo"', false);
        $this->get('/panel?tipo=inventado')->assertSee('data-filter="todos"', false);
        $this->get('/panel?tipo[]=en-riesgo')->assertOk()->assertSee('data-filter="todos"', false);
        $this->get('/panel')->assertSee('data-filter="todos"', false);
    }

    public function test_a_professor_map_only_links_to_their_own_students(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tutor = $this->signInSeededTutor();
        $mine = TutorAssignment::studentIdsOf($tutor);
        $others = Student::whereNotIn('id', $mine)->get();

        $html = $this->get('/panel')->getContent();

        $this->assertNotEmpty($others);
        foreach ($mine as $id) {
            $this->assertStringContainsString('href="'.route('students.show', $id).'"', $html);
        }
        foreach ($others as $student) {
            $this->assertStringNotContainsString('data-code="'.$student->codigo.'"', $html);
        }
    }

    public function test_the_map_offers_search_table_view_export_saved_views_and_box_selection(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $html = $this->get('/panel')->getContent();

        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString('aria-label="Ubicar a un estudiante"', $html);
        $this->assertStringContainsString('role="group" aria-label="Vista del mapa"', $html);
        foreach (['Mapa', 'Tabla', 'Guardar vista', 'Seleccionar con recuadro', 'Ver el cambio desde el periodo anterior'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString('aria-label="Exportar lo que se ve a CSV"', $html);
        $this->assertStringContainsString(':aria-sort=', $html, 'La tabla anuncia su orden.');
        $this->assertStringContainsString('se guardan en este navegador', $html, 'Las vistas guardadas dicen dónde viven.');
    }

    public function test_dots_expose_the_expected_progress_the_previous_period_and_the_last_tutorship(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $html = $this->get('/panel')->getContent();

        $this->assertStringContainsString('data-expected="100"', $html);
        $this->assertStringContainsString('data-prev="63"', $html);
        $this->assertStringContainsString('data-last="12/09/2026"', $html);
        $this->assertStringContainsString('data-behind', $html);
        $this->assertSame(5, substr_count($html, 'class="cohort-expected"'), 'Una marca de avance esperado por fila de cohorte.');
        $this->assertSame(1, substr_count($html, 'class="cohort-trail"'), 'Solo Juan tiene un periodo anterior con el cual compararse.');
    }

    public function test_several_types_can_come_from_the_address_and_unknown_ones_are_dropped(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $this->get('/panel?tipo=activo,con-alerta')->assertSee('data-filter="activo,con-alerta"', false);
        $this->get('/panel?tipo=en-riesgo,inventado')->assertSee('data-filter="en-riesgo"', false);
        $this->get('/panel?tipo=inventado,otro')->assertSee('data-filter="todos"', false);
        $this->get('/panel?tipo=todos,en-riesgo')->assertSee('data-filter="en-riesgo"', false);

        // Activos Y con alerta: los tres de la demostración; los otros cinco salen atenuados desde el servidor.
        $html = $this->get('/panel?tipo=activo,con-alerta')->getContent();
        $this->assertSame(5, preg_match_all('/<a [^>]*data-dot[^>]*data-dim/', $html));
        $this->assertStringContainsString('Mostrando <span x-text="shown">3</span> de 8', $html);
    }

    public function test_the_attention_list_starts_filtered_like_the_map_and_says_so_when_nothing_matches(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Coordinacion);

        $all = $this->get('/panel')->getContent();
        $this->assertSame(4, substr_count($all, 'data-attention'));
        $this->assertSame(0, preg_match_all('/<li [^>]*data-attention[^>]*\shidden/', $all));

        $none = $this->get('/panel?tipo=en-riesgo')->getContent();
        $this->assertSame(4, preg_match_all('/<li [^>]*data-attention[^>]*\shidden/', $none), 'Ninguna alerta sembrada es de un estudiante en riesgo.');
        $this->assertStringContainsString('Ninguna de las situaciones recientes', $none);
    }

    public function test_staff_roles_can_open_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([Role::Profesor, Role::Coordinacion] as $role) {
            $this->signInSeeded($role);
            $this->get('/panel')->assertOk();
        }

        $this->signInSeededTutor();
        $this->get('/panel')->assertOk();
    }

    public function test_the_student_gets_their_own_panel_not_the_program_one(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $this->get('/panel')->assertOk()->assertSee('Mi panel')->assertDontSee('estudiantes activos');
    }

    public function test_coordination_sees_every_alert_but_a_professor_only_those_of_their_tutored_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeeded(Role::Coordinacion);
        $this->get('/panel')->assertSee('Ruiz Salazar, Andrés')->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');

        $this->signInSeededTutor();
        $tutor = $this->get('/panel');
        $tutor->assertSee('Gómez Ruiz, Laura')->assertSee('Pérez Rojas, Juan Sebastián');
        $tutor->assertDontSee('Ruiz Salazar, Andrés');
        $tutor->assertSee('Tutoría registrada a');
    }

    public function test_a_professor_without_tutored_students_sees_no_student_in_alerts_nor_in_recent_activity(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Profesor);

        $response = $this->get('/panel');

        $response->assertOk();
        $response->assertSee('Aún no tiene estudiantes bajo su tutoría.');
        $response->assertSee('Piden atención')->assertSee('Sin situaciones por atender.');
        foreach (['Ruiz Salazar, Andrés', 'Gómez Ruiz, Laura', 'Pérez Rojas, Juan Sebastián'] as $name) {
            $response->assertDontSee($name);
        }
        $response->assertDontSee('Tutoría registrada a');
        $response->assertDontSee('Experiencia validada:');
        $response->assertDontSee('radicada');
    }

    public function test_alert_counter_follows_the_same_scope(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->signInSeededTutor();
        $this->assertSame(2, (new ProgramMetrics)->panel(auth()->user())['headline']['open']);

        $this->signInSeeded(Role::Coordinacion);
        $this->assertSame(3, (new ProgramMetrics)->panel(auth()->user())['headline']['open']);
    }
}
