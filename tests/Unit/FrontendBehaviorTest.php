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

    public function test_long_unbroken_words_wrap_instead_of_widening_the_page(): void
    {
        $css = $this->css();

        // URL, correos, nombres de archivo con guiones bajos y códigos no tienen espacios: sin esto empujan el ancho de la pantalla
        // y en el móvil se puede arrastrar la página de lado. `anywhere` (y no `break-word`) también reduce el ancho mínimo de las cajas.
        $this->assertMatchesRegularExpression('/body\s*\{[^}]*overflow-wrap:\s*anywhere/s', $css);
        // En las tablas se conserva el ancho mínimo por palabras: allí un desborde se desplaza dentro de su contenedor, no en la página.
        $this->assertMatchesRegularExpression('/table[^{]*\{[^}]*overflow-wrap:\s*break-word/s', $css);
        // Red de seguridad: lo que aun así se salga se recorta en lugar de dejar un hueco gris al arrastrar.
        $this->assertMatchesRegularExpression('/html\s*,\s*body\s*\{[^}]*overflow-x:\s*clip/s', $css);
    }

    public function test_every_grid_declares_its_columns_from_phone_width(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            foreach (file($file->getPathname()) as $number => $line) {
                preg_match_all('/class="([^"]*)"/', $line, $classes);
                foreach ($classes[1] as $class) {
                    $tokens = preg_split('/\s+/', trim($class));
                    $declares = collect($tokens)->contains(fn ($t) => str_starts_with($t, 'grid-cols-') || str_starts_with($t, 'grid-flow') || str_starts_with($t, 'grid-rows'));
                    if (in_array('grid', $tokens, true) && ! $declares) {
                        $offenders[] = str_replace(resource_path('views').'/', '', $file->getPathname()).':'.($number + 1);
                    }
                }
            }
        }

        // Una rejilla sin `grid-cols-1` tiene una columna automática que crece hasta el ancho mínimo de su contenido: un nombre
        // de archivo con `truncate` o una URL la ensanchaba hasta 1000 px en un teléfono de 390.
        $this->assertSame([], $offenders, "Falta grid-cols-1 (columna minmax(0,1fr)) en:\n".implode("\n", $offenders));
    }

    public function test_scrollable_containers_clip_their_absolutely_positioned_children(): void
    {
        // Un `sr-only` (absolute) dentro de una tabla desplazable cuyo contenedor no está posicionado queda colocado en el borde
        // derecho de la tabla y alarga toda la página (así pasaba con «Acciones» en la tabla de solicitudes de la ficha).
        $this->assertMatchesRegularExpression('/\.overflow-x-auto\s*\{[^}]*position:\s*relative/s', $this->css());
    }

    public function test_the_cohort_map_can_be_zoomed_panned_filtered_and_read_with_a_card(): void
    {
        $js = $this->js();
        $start = strpos($js, "Alpine.data('cohortMap'");
        $this->assertNotFalse($start, 'Falta el componente del mapa interactivo.');
        $block = substr($js, $start, (strpos($js, 'Alpine.data(', $start + 10) ?: strlen($js)) - $start);

        // Zoom: rueda (solo con Ctrl/⌘, para no secuestrar el desplazamiento de la página), pellizco, doble clic y teclado.
        $this->assertStringContainsString('ctrlKey', $block);
        $this->assertStringContainsString('pointerdown', $block);
        $this->assertStringContainsString('pointermove', $block);
        $this->assertStringContainsString('dblclick', $block);
        $this->assertStringContainsString("'Escape'", $block);
        // Filtro, ajuste a lo visible y tarjeta.
        $this->assertStringContainsString('setTipos', $block);
        $this->assertStringContainsString('fit(', $block);
        $this->assertStringContainsString('replaceState', $block, 'El tipo elegido queda en la dirección para poder compartirla.');
        $this->assertStringContainsString('hover: none', $block, 'En pantallas táctiles el primer toque abre la tarjeta y el segundo entra a la ficha.');
        // El zoom nunca deja el mapa fuera de su marco.
        $this->assertStringContainsString('clamp', $block);
        // Al volver de una ficha el mapa conserva el zoom y la posición solo durante la visita (sessionStorage); las vistas guardadas, en cambio,
        // son una elección que debe durar (localStorage), cada una con su clave.
        $this->assertStringContainsString("sessionStorage.setItem('trz-mapa-vista'", $block);
        $this->assertStringContainsString("localStorage.setItem('trz-mapa-vistas'", $block);
    }

    public function test_the_map_combines_types_finds_students_lists_them_exports_safely_and_groups_dense_dots(): void
    {
        $js = $this->js();
        $start = strpos($js, "Alpine.data('cohortMap'");
        $block = substr($js, $start, (strpos($js, 'Alpine.data(', $start + 10) ?: strlen($js)) - $start);

        $this->assertStringContainsString('toggleTipo', $block);
        $this->assertStringContainsString("normalize('NFD')", $block, 'La búsqueda ignora tildes.');
        $this->assertStringContainsString('locate(', $block);
        $this->assertStringContainsString('sortBy', $block);
        $this->assertStringContainsString('selectRow', $block);
        $this->assertStringContainsString('shiftKey', $block, 'Mayús + arrastrar dibuja el recuadro.');
        $this->assertStringContainsString('cluster', $block);
        // CSV para Excel en español: BOM, «;» y las celdas que empiezan con = + - @ se neutralizan (inyección de fórmulas).
        $this->assertStringContainsString('\\uFEFF', $block);
        $this->assertStringContainsString('neutralize', $block);
        $this->assertMatchesRegularExpression('/\[=\+\\\\-@/', $block);
        // El filtro se anuncia a la lista «Piden atención», que lo sigue.
        $this->assertStringContainsString("'cohort-filter'", $block);
        $this->assertStringContainsString("Alpine.data('attentionList'", $js);
    }

    public function test_the_map_styles_cover_expected_progress_trails_selection_and_clusters(): void
    {
        $css = $this->css();

        foreach (['.cohort-expected', '.cohort-trail', '.cohort-cluster', '.cohort-dot[data-selected]', '.cohort-dot[data-found]', '.cohort-dot[data-clustered]'] as $selector) {
            $this->assertStringContainsString($selector, $css, "Falta el estilo {$selector}.");
        }
        $this->assertMatchesRegularExpression('/\.cohort-map\[data-trails\]\s+\.cohort-trail\s*\{[^}]*display:\s*block/s', $css);

        // «Sin tutoría reciente» es un punto hueco, no un anillo aparte: un segundo elemento más grande que su caja se desalinea (quedaba 4 px a la derecha)
        // y con cientos de estudiantes los anillos se pisan. El hueco ocupa lo mismo que un punto lleno.
        $this->assertMatchesRegularExpression('/\.cohort-dot\[data-quiet\]::before\s*\{[^}]*background:\s*var\(--trz-superficie\)/s', $css);
        $this->assertStringNotContainsString('.cohort-dot[data-quiet]::after', $css);
        $this->assertStringContainsString('prefers-reduced-motion', $css);
    }

    public function test_dimmed_dots_and_the_card_have_their_styles(): void
    {
        $css = $this->css();

        $this->assertMatchesRegularExpression('/\.cohort-dot\[data-dim\]\s*\{[^}]*pointer-events:\s*none/s', $css);
        $this->assertStringContainsString('--k', $css, 'El zoom se aplica con variables CSS (sin tocar cada punto).');
    }
}
