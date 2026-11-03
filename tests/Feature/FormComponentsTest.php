<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FormComponentsTest extends TestCase
{
    private function withErrors(array $messages): void
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
    }

    public function test_form_field_shows_its_error_and_danger_border(): void
    {
        $this->withErrors(['email' => ['Credenciales inválidas.']]);

        $html = Blade::render('<x-form-field label="Correo" name="email" />');

        $this->assertStringContainsString('Credenciales inválidas.', $html);
        $this->assertStringContainsString('border-[var(--trz-alerta)]', $html);
    }

    public function test_form_controls_use_16px_text_on_phones_and_fill_their_box(): void
    {
        $this->withErrors([]);

        foreach (['text', 'password'] as $type) {
            $html = Blade::render('<x-form-field label="Campo" name="campo" type="'.$type.'" />');
            // 15 px haría que iOS acercara la pantalla al enfocar; el control llena el recuadro (sin bordes muertos al tocar).
            $this->assertStringContainsString('text-base', $html);
            $this->assertStringContainsString('md:text-[15px]', $html);
            $this->assertStringContainsString('h-full', $html);
        }

        $select = Blade::render('<x-form-field label="Tipo" name="tipo" type="select"><option>Uno</option></x-form-field>');
        $this->assertStringContainsString('text-base', $select);
        $this->assertStringContainsString('h-full appearance-none', $select);
        $this->assertStringContainsString('pointer-events-none absolute', $select, 'El chevron no debe interceptar el toque.');

        $textarea = Blade::render('<x-form-field label="Texto" name="texto" type="textarea" />');
        $this->assertStringContainsString('text-base', $textarea);
        $this->assertStringContainsString('p-3', $textarea, 'El relleno va en el textarea, no en el recuadro.');
    }

    public function test_the_focus_ring_belongs_to_the_box_and_only_follows_the_control(): void
    {
        $this->withErrors([]);

        $text = Blade::render('<x-form-field label="Campo" name="campo" icon="mail" />');
        $this->assertStringContainsString('has-[input:focus-visible]:outline-2', $text);
        $this->assertStringContainsString('outline-none', $text, 'El control no dibuja su propio aro.');
        $this->assertStringContainsString('pointer-events-none absolute right-3.5', $text, 'El icono no recibe toques.');

        // Con el botón del ojo enfocado el aro del recuadro no debe aparecer: ese botón trae el suyo.
        $password = Blade::render('<x-form-field label="Clave" name="clave" type="password" />');
        $this->assertStringContainsString('has-[input:focus-visible]:outline-2', $password);
        $this->assertStringNotContainsString('has-[:focus-visible]', $password);
    }

    public function test_date_fields_keep_the_browser_picker_but_hide_its_duplicate_icon(): void
    {
        $this->withErrors([]);

        $date = Blade::render('<x-form-field label="Fecha" name="fecha" type="date" icon="calendar" />');
        // Blade escapa «&» como «&amp;» en el atributo; el navegador lo decodifica y Tailwind lo lee del código fuente.
        $this->assertStringContainsString('[&amp;::-webkit-calendar-picker-indicator]:opacity-0', $date);
        $this->assertStringContainsString('[&amp;::-webkit-calendar-picker-indicator]:w-11', $date, 'Tocar el icono sigue abriendo el selector.');

        $text = Blade::render('<x-form-field label="Campo" name="campo" icon="mail" />');
        $this->assertStringNotContainsString('calendar-picker-indicator', $text);
    }

    public function test_an_invalid_field_is_marked_and_linked_to_its_message_so_the_focus_lands_on_something_readable(): void
    {
        $this->withErrors(['email' => ['Credenciales inválidas.']]);

        $html = Blade::render('<x-form-field label="Correo" name="email" help="Use su correo." />');

        // El script lleva el foco al primer [aria-invalid="true"]; el lector de pantalla lee el mensaje por aria-describedby.
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="email-error email-help"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
    }

    public function test_form_field_without_errors_keeps_the_default_border(): void
    {
        $this->withErrors([]);

        $html = Blade::render('<x-form-field label="Correo" name="email" />');

        $this->assertStringNotContainsString('border-[var(--trz-alerta)]', $html);
    }

    public function test_form_field_repopulates_old_input_but_never_passwords(): void
    {
        $this->withErrors([]);
        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app['session.store']->flashInput(['email' => 'ana@unal.edu.co', 'password' => 'secreto']);

        $email = Blade::render('<x-form-field label="Correo" name="email" />');
        $password = Blade::render('<x-form-field label="Clave" name="password" type="password" />');

        $this->assertStringContainsString('value="ana@unal.edu.co"', $email);
        $this->assertStringNotContainsString('secreto', $password);
    }

    public function test_form_field_can_be_readonly_and_textarea_shows_old_text(): void
    {
        $this->withErrors([]);

        $this->assertStringContainsString('readonly', Blade::render('<x-form-field label="Periodo" name="periodo" value="2026-2" :readonly="true" />'));
        $this->assertStringContainsString('Texto previo', Blade::render('<x-form-field label="Descripción" name="d" type="textarea" value="Texto previo" />'));
    }

    public function test_form_field_passes_autocomplete_to_the_input_only_when_given(): void
    {
        $with = Blade::render('<x-form-field label="Contraseña" name="password" type="password" autocomplete="current-password" />');
        $text = Blade::render('<x-form-field label="Correo" name="email" type="email" autocomplete="username" />');
        $without = Blade::render('<x-form-field label="Nombre" name="name" />');

        $this->assertStringContainsString('autocomplete="current-password"', $with);
        $this->assertStringContainsString('autocomplete="username"', $text);
        $this->assertStringNotContainsString('autocomplete', $without);
    }

    public function test_filter_select_marks_the_selected_option(): void
    {
        $html = Blade::render('<x-filter-select name="estado" all="Estado: todos" :options="$options" selected="en_riesgo" />', [
            'options' => ['activo' => 'Activo', 'en_riesgo' => 'En riesgo'],
        ]);

        $this->assertStringContainsString('Estado: todos', $html);
        $this->assertMatchesRegularExpression('/<option value="en_riesgo"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="activo"\s+selected/', $html);
    }

    public function test_pagination_shows_range_and_keeps_the_query_string(): void
    {
        $paginator = (new LengthAwarePaginator(range(1, 8), 20, 8, 2, ['path' => '/estudiantes']))->appends(['q' => 'ana']);

        $html = Blade::render('<x-pagination :paginator="$p" noun="estudiantes" />', ['p' => $paginator]);

        $this->assertStringContainsString('Mostrando 9–16 de 20 estudiantes', $html);
        $this->assertStringContainsString('href="/estudiantes?q=ana&amp;page=1"', $html);
        $this->assertStringContainsString('href="/estudiantes?q=ana&amp;page=3"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_pagination_with_no_results_says_so(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 8, 1, ['path' => '/estudiantes']);

        $html = Blade::render('<x-pagination :paginator="$p" noun="estudiantes" />', ['p' => $paginator]);

        $this->assertStringContainsString('Sin resultados', $html);
        $this->assertStringNotContainsString('Siguiente', $html);
    }

    public function test_layout_shows_the_flash_status_banner(): void
    {
        session()->flash('status', 'Solicitud radicada correctamente.');

        $html = Blade::render('<x-layouts.app :user="$u" :nav-groups="[]">x</x-layouts.app>', [
            'u' => ['initials' => 'AR', 'name' => 'Ana', 'role' => 'Coordinación'],
        ]);

        $this->assertStringContainsString('Solicitud radicada correctamente.', $html);
    }
}
