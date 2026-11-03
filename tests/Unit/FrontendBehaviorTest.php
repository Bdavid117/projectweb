<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Comportamientos de la interfaz que viven en resources/js y resources/css y no tienen un navegador en las pruebas:
 * se protegen leyendo el código fuente, para que una refactorización no los pierda en silencio.
 */
class FrontendBehaviorTest extends TestCase
{
    private function js(): string
    {
        return file_get_contents(resource_path('js/app.js'));
    }

    private function css(): string
    {
        return file_get_contents(resource_path('css/app.css'));
    }

    public function test_every_post_form_shows_a_busy_state_and_gets_it_back_from_the_page_cache(): void
    {
        $js = $this->js();

        $this->assertStringContainsString("addEventListener('submit'", $js);
        $this->assertStringContainsString('aria-busy', $js);
        $this->assertStringContainsString('data-busy-label', file_get_contents(resource_path('views/auth/login.blade.php')));
        $this->assertStringContainsString('busyLabel', $js);
        $this->assertStringContainsString('defaultPrevented', $js, 'Un envío cancelado (p. ej. por una confirmación) no debe quedar bloqueado.');
        // Al volver con «Atrás» el navegador restaura la página tal cual: sin esto los botones quedarían deshabilitados para siempre.
        $this->assertStringContainsString("addEventListener('pageshow'", $js);
        $this->assertStringContainsString('persisted', $js);
        $this->assertStringContainsString('[data-busy]', $this->css());
    }

    public function test_login_and_new_request_rely_on_the_shared_busy_state(): void
    {
        foreach (['auth/login', 'requests/create'] as $view) {
            $this->assertStringNotContainsString('sending', file_get_contents(resource_path("views/{$view}.blade.php")), "{$view} tenía su propio estado de envío.");
        }
    }

    public function test_the_first_invalid_field_receives_the_focus_after_a_failed_submit(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('[aria-invalid="true"]', $js);
        $this->assertStringContainsString('.focus(', $js);
    }

    public function test_the_bell_does_not_fetch_when_the_page_loads(): void
    {
        $js = $this->js();
        $start = strpos($js, "Alpine.data('notifications'");
        $this->assertNotFalse($start);
        $block = substr($js, $start, strpos($js, 'Alpine.data(', $start + 10) - $start);

        $this->assertStringNotContainsString('init()', $block, 'La campana ya llega llena desde el servidor.');
        $this->assertStringContainsString('async refresh()', $block);
        $this->assertStringContainsString('this.refresh()', $block, 'Se refresca al abrirla.');
    }

    public function test_the_page_behind_a_modal_does_not_scroll_and_does_not_jump(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/html:has\(dialog\[open\]\)\s*\{[^}]*overflow:\s*hidden/', $css);
        $this->assertMatchesRegularExpression('/html\s*\{[^}]*scrollbar-gutter:\s*stable/s', $css, 'Sin el canal reservado, la página se corre al ocultar la barra.');
    }
}
