<?php
// tests/Unit/DesignTokensTest.php

namespace Tests\Unit;

use Tests\TestCase;

class DesignTokensTest extends TestCase
{
    public function test_app_css_imports_tailwind_and_defines_core_tokens(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@import "tailwindcss";', $css);
        $this->assertStringContainsString('--color-primary-600: #1E5290;', $css);
        $this->assertStringContainsString('--surface-page:', $css);
        $this->assertStringContainsString('--control-height:    40px;', $css);
        $this->assertStringContainsString('.font-sans', $css);
    }
}
