<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Escala tipográfica del móvil. Los titulares grandes del escritorio (42, 52 px) ocupaban media pantalla de un teléfono y dejaban
 * pequeños a los datos y a la foto; en móvil el texto acompaña a las cifras, no las tapa. Se protege leyendo las vistas.
 */
class MobileTypeScaleTest extends TestCase
{
    private function views(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[str_replace(resource_path('views').'/', '', $file->getPathname())] = file_get_contents($file->getPathname());
            }
        }

        return $files;
    }

    /** Titulares: 28 px como máximo en móvil. Las cifras de datos (el % de avance) pueden llegar a 34 px; nada pasa de ahí. */
    public function test_no_text_is_bigger_than_34px_on_a_phone_unless_it_is_decorative(): void
    {
        $offenders = [];
        foreach ($this->views() as $name => $source) {
            // El código del error (404, 500…) es una forma decorativa, no un titular.
            if ($name === 'components/layouts/error.blade.php') {
                continue;
            }
            preg_match_all('/class="([^"]*)"/', $source, $classes);
            foreach ($classes[1] as $class) {
                foreach (preg_split('/\s+/', trim($class)) as $token) {
                    // Sin prefijo de pantalla = vale también para el teléfono.
                    if (preg_match('/^text-\[(\d+)px\]$/', $token, $m) && (int) $m[1] > 34) {
                        $offenders[] = "$name: $token";
                    }
                    if (in_array($token, ['text-3xl', 'text-4xl', 'text-5xl', 'text-6xl'], true)) {
                        $offenders[] = "$name: $token";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($offenders)), "Titulares que no se reducen en el móvil:\n".implode("\n", array_unique($offenders)));
    }

    public function test_the_lead_sentence_and_the_headings_step_down_on_phones(): void
    {
        $views = $this->views();

        $this->assertStringContainsString('text-[22px]', $views['components/frase.blade.php'], 'La frase de cabecera: 22 px en móvil.');
        $this->assertStringContainsString('text-[28px]', $views['components/page-header.blade.php'], 'El título de pantalla: 28 px en móvil.');
        $this->assertStringContainsString('text-[22px]', $views['components/section.blade.php'], 'El título de sección: 22 px en móvil.');
    }

    public function test_the_student_file_gives_the_avatar_room_and_keeps_the_name_in_check(): void
    {
        $source = $this->views()['students/show.blade.php'];

        // Un avatar de 56 px junto a un nombre de 30 px parecía un adorno; ahora el avatar manda en el teléfono.
        $this->assertStringContainsString('h-[72px] w-[72px]', $source);
        $this->assertStringContainsString('lg:h-[88px] lg:w-[88px]', $source, 'Escritorio: 88 px, como en el lienzo.');
        $this->assertStringContainsString('text-2xl', $source);
        $this->assertStringContainsString('text-[34px]', $source, 'La cifra de avance baja a 34 px en móvil.');
    }

    public function test_the_history_charts_fit_a_phone_instead_of_hiding_their_last_bars(): void
    {
        $source = $this->views()['academic-history/index.blade.php'];

        // Con `min-w-[44px]` y huecos de 20 px, siete periodos pedían 440 px de una pantalla de 342 y la última barra se cortaba.
        $this->assertStringNotContainsString('min-w-[44px]', $source);
        $this->assertStringContainsString('gap-2 sm:gap-5', $source);
        $this->assertStringContainsString('whitespace-nowrap', $source, 'Los periodos («2026-2») no se parten en el guion.');
    }

    public function test_the_history_table_shows_all_its_useful_columns_on_a_phone(): void
    {
        $source = $this->views()['academic-history/index.blade.php'];

        // La columna «Avance del plan» quedaba tras un desplazamiento interno. En el teléfono la tabla cabe: sin ancho mínimo,
        // sin la columna redundante «Promedio periodo» y con la barra de avance más corta.
        $this->assertStringNotContainsString('w-full min-w-[560px]', $source);
        $this->assertStringContainsString('sm:min-w-[560px]', $source);
        $this->assertMatchesRegularExpression('/class="hidden [^"]*sm:table-cell"/', $source);
        $this->assertStringContainsString('w-14 sm:w-[140px]', $source);
    }
}
