<?php

namespace Tests\Feature;

use App\Support\Navigation;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SharedComponentsTest extends TestCase
{
    public function test_button_renders_variant_classes_and_label(): void
    {
        $html = Blade::render('<x-button variant="primary">Ingresar</x-button>');

        $this->assertStringContainsString('bg-[var(--color-primary-600)]', $html);
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

        $this->assertStringContainsString('bg-[var(--color-danger-bg)]', $html);
        $this->assertStringContainsString('text-[var(--color-danger)]', $html);
        $this->assertStringContainsString('En riesgo', $html);
    }

    public function test_indicator_card_renders_label_value_detail(): void
    {
        $html = Blade::render(
            '<x-indicator-card label="ALERTAS ABIERTAS" value="7" detail="Requieren atención temprana" value-class="text-[var(--color-danger)]" />'
        );

        $this->assertStringContainsString('ALERTAS ABIERTAS', $html);
        $this->assertStringContainsString('>7<', $html);
        $this->assertStringContainsString('text-[var(--color-danger)]', $html);
    }

    public function test_form_field_select_renders_slot_options(): void
    {
        $html = Blade::render(
            '<x-form-field label="Tipo" name="tipo" type="select"><option>Cancelación</option></x-form-field>'
        );

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('Cancelación', $html);
    }

    public function test_nav_item_marks_active_state(): void
    {
        $html = Blade::render('<x-nav-item icon="users" label="Estudiantes" href="/estudiantes" :active="true" />');

        $this->assertStringContainsString('bg-[var(--color-primary-50)]', $html);
    }

    public function test_navigation_coordinator_marks_current_item_active(): void
    {
        $groups = Navigation::coordinator('estudiantes');

        $general = collect($groups[0]['items']);
        $this->assertTrue($general->firstWhere('key', 'estudiantes')['active']);
        $this->assertFalse($general->firstWhere('key', 'panel')['active']);
    }
}
