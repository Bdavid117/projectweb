<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AnimatedIconsTest extends TestCase
{
    public function test_every_configured_animated_icon_is_registered_in_javascript(): void
    {
        $config = require __DIR__.'/../../config/animated-icons.php';
        $js = file_get_contents(__DIR__.'/../../resources/js/icons.js');

        foreach ($config['names'] as $name) {
            $this->assertStringContainsString("@animateicons/vanilla/lucide/{$name}'", $js, $name);
        }
        foreach ($config['aliases'] as $target) {
            $this->assertContains($target, $config['names'], "alias → {$target}");
        }
    }
}
