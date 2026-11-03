<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Guardas del sistema de diseño «El hilo»: los tokens existen en claro y en oscuro, los pares de color cumplen
 * contraste (WCAG: 4,5:1 para texto, 3:1 para gráficos) y las vistas solo hablan en tokens, nunca en hex.
 */
class DesignTokensTest extends TestCase
{
    private function css(): string
    {
        return file_get_contents(resource_path('css/app.css'));
    }

    /** @return array<string, string> */
    private function tokens(string $block): array
    {
        preg_match_all('/--(trz-[a-z0-9-]+):\s*(#[0-9A-Fa-f]{6})/', $block, $m);

        return array_combine($m[1], array_map('strtoupper', $m[2]));
    }

    /** @return array{light: array<string, string>, dark: array<string, string>, darkMedia: array<string, string>} */
    private function themes(): array
    {
        $css = $this->css();
        preg_match('/:root\s*\{(.*?)\n\}/s', $css, $root);
        preg_match('/:root\[data-theme="dark"\]\s*\{(.*?)\n\}/s', $css, $forced);
        preg_match('/@media \(prefers-color-scheme: dark\)\s*\{\s*:root:not\(\[data-theme="light"\]\)\s*\{(.*?)\n  \}\n\}/s', $css, $auto);

        $light = $this->tokens($root[1] ?? '');
        $dark = $this->tokens($forced[1] ?? '') + $light;
        $darkMedia = $this->tokens($auto[1] ?? '') + $light;

        return ['light' => $light, 'dark' => $dark, 'darkMedia' => $darkMedia];
    }

    private function luminance(string $hex): float
    {
        $channels = array_map(function (string $c) {
            $v = hexdec($c) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private function contrast(string $a, string $b): float
    {
        [$hi, $lo] = [max($this->luminance($a), $this->luminance($b)), min($this->luminance($a), $this->luminance($b))];

        return ($hi + 0.05) / ($lo + 0.05);
    }

    public function test_app_css_imports_tailwind_the_local_fonts_and_defines_the_core_tokens(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('@import "tailwindcss";', $css);
        $this->assertStringContainsString('@fontsource-variable/bricolage-grotesque', $css);
        $this->assertStringContainsString('@fontsource-variable/ibm-plex-sans', $css);
        $this->assertStringNotContainsString('fonts.googleapis.com', $css);
        $this->assertStringContainsString('--trz-hilo:           #2456D6;', $css);
        $this->assertStringContainsString('--trz-papel:', $css);
        $this->assertStringContainsString('--control-height:    44px;', $css);
        $this->assertStringContainsString('--font-display:', $css);
    }

    public function test_the_focus_ring_lives_in_the_base_layer_so_utilities_can_override_it(): void
    {
        $css = $this->css();
        $rule = ':where(a, button, input, select, textarea, summary, [tabindex]):focus-visible';
        $at = strpos($css, $rule);
        $this->assertNotFalse($at, 'Falta la regla global de foco visible.');

        // Fuera de toda capa, la regla le gana a `outline-none` (@layer utilities) y los campos muestran un segundo aro.
        $start = strpos($css, '@layer base {');
        $this->assertNotFalse($start);
        $depth = 0;
        $end = null;
        for ($i = strpos($css, '{', $start), $len = strlen($css); $i < $len; $i++) {
            $depth += match ($css[$i]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };
            if ($depth === 0) {
                $end = $i;
                break;
            }
        }
        $this->assertNotNull($end);
        $this->assertTrue($at > $start && $at < $end, 'La regla de foco debe estar dentro de @layer base.');

        // Tampoco debe fijar el radio: cambiaría la forma de lo redondeado (avatar, tarjetas enlace) al enfocarlo.
        $declaration = substr($css, $at, strpos($css, '}', $at) - $at);
        $this->assertStringNotContainsString('border-radius', $declaration);
    }

    public function test_the_dark_theme_is_defined_for_the_forced_and_the_automatic_modes_alike(): void
    {
        ['light' => $light, 'dark' => $dark, 'darkMedia' => $darkMedia] = $this->themes();

        $this->assertNotEmpty($light);
        $this->assertSame($dark, $darkMedia, 'El modo oscuro forzado y el automático deben definir los mismos valores.');
        foreach (['trz-tinta', 'trz-papel', 'trz-superficie', 'trz-hilo-texto', 'trz-alerta', 'trz-logro', 'trz-brote'] as $token) {
            $this->assertNotSame($light[$token], $dark[$token], "{$token} no cambia en el tema oscuro.");
        }
        $this->assertSame($light['trz-sobre-hilo'], $dark['trz-sobre-hilo'], 'El texto sobre el azul de marca no cambia de tema.');
        $this->assertSame($light['trz-hito'], $dark['trz-hito']);
    }

    public function test_every_color_pair_meets_contrast_in_both_themes(): void
    {
        $themes = $this->themes();

        // [primer plano, fondo, mínimo]: 4,5 para texto; 3 para bordes de controles y elementos gráficos.
        // `trz-hito` no está: es solo relleno y su forma siempre lleva el contorno `trz-hito-texto`, que sí se mide.
        $pairs = [
            ['trz-tinta', 'trz-papel', 4.5], ['trz-tinta', 'trz-superficie', 4.5],
            ['trz-texto-2', 'trz-papel', 4.5], ['trz-texto-2', 'trz-superficie', 4.5],
            ['trz-texto-3', 'trz-papel', 4.5], ['trz-texto-3', 'trz-superficie', 4.5],
            ['trz-hilo-texto', 'trz-papel', 4.5], ['trz-hilo-texto', 'trz-superficie', 4.5], ['trz-hilo-texto', 'trz-hilo-fondo', 4.5],
            ['trz-sobre-hilo', 'trz-hilo', 4.5], ['trz-sobre-hilo', 'trz-hilo-hover', 4.5],
            ['trz-hito-texto', 'trz-hito-fondo', 4.5], ['trz-hito-texto', 'trz-superficie', 4.5],
            ['trz-logro', 'trz-logro-fondo', 4.5], ['trz-logro', 'trz-superficie', 4.5],
            ['trz-alerta', 'trz-alerta-fondo', 4.5], ['trz-alerta', 'trz-superficie', 4.5], ['trz-alerta', 'trz-papel', 4.5],
            ['trz-brote', 'trz-brote-fondo', 4.5], ['trz-brote', 'trz-superficie', 4.5],
            ['trz-borde-control', 'trz-superficie', 3.0], ['trz-borde-control', 'trz-papel', 3.0],
            ['trz-hilo', 'trz-superficie', 3.0], ['trz-foco', 'trz-superficie', 3.0], ['trz-foco', 'trz-papel', 3.0],
        ];

        foreach (['light', 'dark'] as $theme) {
            foreach ($pairs as [$fg, $bg, $min]) {
                $ratio = $this->contrast($themes[$theme][$fg], $themes[$theme][$bg]);
                $this->assertGreaterThanOrEqual($min, $ratio, sprintf('%s: %s sobre %s da %.2f:1 y pide %.1f:1.', $theme, $fg, $bg, $ratio, $min));
            }
        }
    }

    public function test_views_speak_in_tokens_never_in_legacy_variables_or_raw_hex(): void
    {
        $offenders = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $relative = str_replace(resource_path('views').'/', '', $file->getPathname());

            if (preg_match('/var\(--(color|surface|text|border)-[a-z0-9-]+\)|var\(--sidebar/', $source, $m)) {
                $offenders[] = "{$relative}: variable antigua {$m[0]}";
            }
            if (preg_match('/(?<![&\w])#[0-9A-Fa-f]{6}\b/', $source, $m)) {
                $offenders[] = "{$relative}: color literal {$m[0]}";
            }
        }

        $this->assertSame([], $offenders);
    }
}
