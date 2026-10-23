<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AnimatedIconsTest extends TestCase
{
    private function pascal(string $name): string
    {
        return implode('', array_map('ucfirst', explode('-', $name)));
    }

    public function test_every_configured_animated_icon_is_registered_in_javascript(): void
    {
        $config = require __DIR__.'/../../config/animated-icons.php';
        $js = file_get_contents(__DIR__.'/../../resources/js/icons.js');

        foreach ($config['names'] as $name) {
            $this->assertStringContainsString("@animated-color-icons/lucide-wc/{$this->pascal($name)}.js'", $js, $name);
        }
    }

    public function test_every_literal_icon_used_by_the_app_has_an_animated_version(): void
    {
        $names = (require __DIR__.'/../../config/animated-icons.php')['names'];
        $files = array_merge(
            glob(__DIR__.'/../../resources/views/{*,*/*}/*.blade.php', GLOB_BRACE) ?: [],
            glob(__DIR__.'/../../app/Support/*.php') ?: [],
            glob(__DIR__.'/../../app/Http/Controllers/*.php') ?: [],
        );

        $used = [];
        foreach ($files as $file) {
            $source = file_get_contents($file);
            preg_match_all('/<x-ui-icon name="([a-z0-9-]+)"/', $source, $a);
            preg_match_all("/'icon' => '([a-z0-9-]+)'/", $source, $b);
            preg_match_all('/\bicon="([a-z0-9-]+)"/', $source, $c);
            $used = array_merge($used, $a[1], $b[1], $c[1]);
        }

        $this->assertSame([], array_values(array_diff(array_unique($used), $names)), 'Íconos sin versión animada: agréguelos a config/animated-icons.php y resources/js/icons.js');
    }
}
