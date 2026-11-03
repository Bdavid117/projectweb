<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SharedComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_renders_variant_classes_and_label(): void
    {
        $html = Blade::render('<x-button variant="primary">Ingresar</x-button>');

        $this->assertStringContainsString('bg-[var(--trz-hilo)]', $html);
        $this->assertStringContainsString('Ingresar', $html);
    }

    public function test_button_with_icon_and_href_renders_anchor(): void
    {
        $html = Blade::render('<x-button variant="subtle" icon="download" href="/x">Exportar</x-button>');

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('href="/x"', $html);
        $this->assertStringContainsString('Exportar', $html);
    }

    public function test_status_badge_maps_tone_to_literal_classes(): void
    {
        $html = Blade::render('<x-status-badge text="En riesgo" tone="danger" />');

        $this->assertStringContainsString('bg-[var(--trz-alerta-fondo)]', $html);
        $this->assertStringContainsString('text-[var(--trz-alerta)]', $html);
        $this->assertStringContainsString('En riesgo', $html);
    }

    public function test_form_field_select_renders_slot_options(): void
    {
        $html = Blade::render(
            '<x-form-field label="Tipo" name="tipo" type="select"><option>Cancelación</option></x-form-field>'
        );

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('Cancelación', $html);
    }

    public function test_the_nav_list_marks_only_the_current_item_with_aria_current(): void
    {
        $html = Blade::render('<x-nav.list :groups="$groups" />', ['groups' => Navigation::coordinator('estudiantes')]);

        $this->assertSame(1, substr_count(explode('Administración', $html)[0], 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/<a href="\/estudiantes"[^>]*aria-current="page"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<a href="\/panel"[^>]*aria-current/', $html);
    }

    public function test_the_nav_list_can_skip_what_the_tab_bar_already_shows(): void
    {
        $html = Blade::render('<x-nav.list :groups="$groups" skip-tabs />', ['groups' => Navigation::coordinator('panel')]);

        $this->assertStringNotContainsString('href="/panel"', $html);
        $this->assertStringNotContainsString('href="/estudiantes"', $html);
        $this->assertStringContainsString('href="/indicadores"', $html);
        $this->assertStringContainsString('Usuarios y roles', $html);
    }

    public function test_dots_draw_one_shape_per_unit_and_are_hidden_from_assistive_tech(): void
    {
        $circles = Blade::render('<x-dots :count="3" />');
        $diamonds = Blade::render('<x-dots :count="2" shape="diamond" />');
        $alert = Blade::render('<x-dots :count="1" tone="alerta" />');

        $this->assertSame(3, substr_count($circles, 'rounded-full bg-[var(--trz-hilo)]'));
        $this->assertSame(2, substr_count($diamonds, 'rotate-45'));
        $this->assertStringContainsString('bg-[var(--trz-alerta)]', $alert);
        $this->assertStringContainsString('aria-hidden="true"', $circles);
    }

    public function test_stream_marks_differ_by_shape_not_only_by_color(): void
    {
        $this->assertStringContainsString('rounded-full', Blade::render('<x-stream-mark kind="academico" />'));
        $this->assertStringContainsString('rotate-45', Blade::render('<x-stream-mark kind="experiencia" />'));
        $this->assertStringContainsString('rounded-[3px]', Blade::render('<x-stream-mark kind="acompanamiento" />'));
        $this->assertStringContainsString('border-[3px]', Blade::render('<x-stream-mark kind="alerta" />'));
    }

    public function test_the_thread_marks_pending_steps_for_screen_readers(): void
    {
        $html = Blade::render('<x-thread :steps="$steps" />', ['steps' => [
            ['title' => 'Radicada', 'detail' => 'Hoy', 'done' => true],
            ['title' => 'Con decisión', 'detail' => 'Pendiente', 'done' => false],
        ]]);

        $this->assertStringContainsString('Radicada', $html);
        $this->assertStringContainsString('aria-label="Con decisión (pendiente)"', $html);
        $this->assertStringNotContainsString('aria-label="Radicada (pendiente)"', $html);
    }

    public function test_the_journey_places_today_at_the_plan_progress(): void
    {
        $steps = [
            ['key' => 'ingreso', 'title' => 'Ingreso', 'detail' => 'Cohorte 2020-1', 'state' => 'done'],
            ['key' => 'practica', 'title' => 'Práctica', 'detail' => 'Sin registrar', 'state' => 'pending'],
            ['key' => 'trabajo', 'title' => 'Trabajo de grado', 'detail' => 'Sin registrar', 'state' => 'current'],
            ['key' => 'grado', 'title' => 'Grado', 'detail' => 'Meta del plan', 'state' => 'pending'],
        ];

        $html = Blade::render('<x-journey :steps="$steps" :progress="$progress" />', ['steps' => $steps, 'progress' => 72]);

        $this->assertStringContainsString('width: 72%', $html);
        $this->assertStringContainsString('Hoy', $html);
        foreach (['Ingreso', 'Práctica', 'Trabajo de grado', 'Grado', 'Cohorte 2020-1'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('width: 0%', Blade::render('<x-journey :steps="$steps" :progress="null" />', ['steps' => $steps]));
    }

    public function test_the_cohort_map_labels_every_dot_and_prints_the_reading(): void
    {
        $html = Blade::render('<x-cohort-map :rows="$rows" reading="Las cohortes 2019 y 2020 concentran 2 de las 3 alertas abiertas." />', ['rows' => [
            ['label' => '2019', 'dots' => [['pct' => 88, 'alert' => false, 'label' => 'Ana · 88 % del plan']]],
            ['label' => '2020', 'dots' => [['pct' => 40, 'alert' => true, 'label' => 'Luis · 40 % del plan, con una alerta abierta']]],
        ]]);

        $this->assertStringContainsString('2019', $html);
        $this->assertStringContainsString('Ana · 88 % del plan', $html);
        $this->assertStringContainsString('con una alerta abierta', $html);
        $this->assertStringContainsString('Las cohortes 2019 y 2020 concentran 2 de las 3 alertas abiertas.', $html);
        $this->assertStringContainsString('Con alerta abierta', $html, 'La leyenda explica el anillo.');
        $this->assertStringNotContainsString('Estudiante activo', $html, 'El mapa incluye a todos los que tienen registro en el periodo, no solo a los activos.');
    }

    public function test_confirm_uses_a_native_dialog_with_the_real_action_and_cancel_first(): void
    {
        $html = Blade::render('<x-confirm title="¿Retirar el acceso?" text="Perderá el acceso." confirm="Sí, retirar" action="/mi-tutor/7" />');

        $this->assertStringContainsString('<dialog', $html);
        $this->assertStringContainsString('action="/mi-tutor/7"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringContainsString('¿Retirar el acceso?', $html);
        $this->assertStringContainsString('Sí, retirar', $html);
        $this->assertLessThan(strpos($html, 'Sí, retirar'), strpos($html, 'Cancelar'), 'Cancelar va primero y recibe el foco inicial.');
        $this->assertStringContainsString('autofocus', $html);
        $this->assertStringNotContainsString('confirm(', $html, 'Ya no se usa el confirm() del navegador.');
    }

    public function test_the_theme_toggle_offers_auto_light_and_dark_as_a_radio_group(): void
    {
        $html = Blade::render('<x-theme-toggle />');

        $this->assertStringContainsString('role="radiogroup"', $html);
        $this->assertSame(3, substr_count($html, 'role="radio"'));
        foreach (['Automático', 'Claro', 'Oscuro'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_the_theme_toggle_stacks_icon_over_label_with_one_weight_so_it_fits_in_a_third(): void
    {
        $html = Blade::render('<x-theme-toggle />');

        // «Automático» en semibold y con el icono al lado pasaba de 100 px dentro de una celda de ~80 px del menú de usuario.
        $this->assertStringContainsString('flex-col', $html);
        $this->assertStringNotContainsString('font-semibold', $html, 'Otro grosor en la opción activa cambia su ancho.');
        $this->assertStringContainsString('ring-inset', $html, 'La opción activa se distingue por forma, no solo por color.');
    }

    public function test_back_and_arrow_links_are_44px_touch_targets_on_phones_without_moving_the_layout(): void
    {
        $back = Blade::render('<x-back-link href="/solicitudes">Solicitudes</x-back-link>');
        $arrow = Blade::render('<x-link-arrow href="/estudiantes">Ver todos</x-link-arrow>');

        foreach ([$back, $arrow] as $html) {
            $this->assertStringContainsString('max-md:min-h-11', $html);
            $this->assertStringContainsString('max-md:-my-2.5', $html, 'El margen negativo compensa el alto añadido.');
        }
        $this->assertStringContainsString('href="/solicitudes"', $back);
        $this->assertStringContainsString('Solicitudes', $back);
    }

    public function test_the_sheets_that_cover_the_page_stop_it_from_scrolling_behind_them(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $html = $this->get('/panel')->getContent();

        // Una vez la hoja «Más» (móvil) y otra el cajón de navegación (tablet).
        $this->assertSame(2, substr_count($html, "classList.toggle('overflow-hidden'"));
    }

    public function test_empty_state_says_what_is_missing_and_offers_a_way_to_start(): void
    {
        $html = Blade::render('<x-empty-state kind="tesis" title="No hay trabajo de grado" text="Aún no se ha registrado."><x-slot:body><a href="/x">Empezar</a></x-slot:body></x-empty-state>');

        $this->assertStringContainsString('No hay trabajo de grado', $html);
        $this->assertStringContainsString('Aún no se ha registrado.', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html, 'La ilustración es decorativa.');
    }

    public function test_page_header_renders_one_h1_with_optional_back_link(): void
    {
        $html = Blade::render('<x-page-header title="Solicitud" subtitle="Detalle" back="/solicitudes" back-label="Volver" />');

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('href="/solicitudes"', $html);
        $this->assertStringContainsString('Volver', $html);
        $this->assertStringContainsString('Detalle', $html);
    }

    public function test_every_screen_has_a_skip_link_a_main_landmark_and_the_tab_bar(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->signInSeeded(Role::Estudiante);

        $html = $this->get('/panel')->getContent();

        $this->assertStringContainsString('<a href="#contenido"', $html);
        $this->assertStringContainsString('<main id="contenido"', $html);
        $this->assertStringContainsString('aria-label="Navegación principal"', $html);
        $this->assertStringContainsString('role="radiogroup"', $html, 'El selector de tema está en el menú de usuario.');
        $this->assertStringContainsString("localStorage.getItem('tema')", $html, 'El tema guardado se aplica antes de pintar.');
        $this->assertStringContainsString('<html lang="es"', $html);
    }

    public function test_navigation_coordinator_marks_current_item_active(): void
    {
        $groups = Navigation::coordinator('estudiantes');

        $general = collect($groups[0]['items']);
        $this->assertTrue($general->firstWhere('key', 'estudiantes')['active']);
        $this->assertFalse($general->firstWhere('key', 'panel')['active']);
    }
}
