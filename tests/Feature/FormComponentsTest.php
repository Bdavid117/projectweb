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
        $this->assertStringContainsString('border-[var(--color-danger)]', $html);
    }

    public function test_form_field_without_errors_keeps_the_default_border(): void
    {
        $this->withErrors([]);

        $html = Blade::render('<x-form-field label="Correo" name="email" />');

        $this->assertStringNotContainsString('border-[var(--color-danger)]', $html);
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
