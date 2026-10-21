<?php

namespace Tests\Feature;

use App\Support\ProgramMetrics;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_panel_indicators_are_computed_from_the_current_term(): void
    {
        $this->seed(DatabaseSeeder::class);

        $panel = (new ProgramMetrics)->panel();
        $cards = collect($panel['indicators'])->keyBy('label');

        $this->assertSame('6', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('Matriculados en 2026-2S', $cards['ESTUDIANTES ACTIVOS']['detail']);
        $this->assertSame('3.7', $cards['PAPA PROMEDIO']['value']);
        $this->assertSame('-0.3 frente al periodo anterior', $cards['PAPA PROMEDIO']['detail']);
        $this->assertSame('44%', $cards['AVANCE MEDIO']['value']);
        $this->assertSame('3', $cards['ALERTAS ABIERTAS']['value']);
        $this->assertSame('text-[var(--color-danger)]', $cards['ALERTAS ABIERTAS']['valueClass']);
    }

    public function test_attention_rows_list_alerts_newest_first_with_status_and_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $rows = (new ProgramMetrics)->panel()['attentionRows'];

        $this->assertCount(4, $rows);
        $this->assertSame('Pérez Rojas, Juan Sebastián', $rows[0]['name']);
        $this->assertSame('Sin atender', $rows[0]['status']);
        $this->assertSame('danger', $rows[0]['tone']);
        $this->assertSame('Martínez Cruz, Diana', $rows[3]['name']);
        $this->assertSame('Atendida', $rows[3]['status']);
        $this->assertStringContainsString('/estudiantes/', $rows[0]['url']);
    }

    public function test_cohort_chart_averages_the_progress_by_entry_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $chart = collect((new ProgramMetrics)->panel()['cohortChart']);

        $this->assertSame(['2019', '2020', '2021', '2022', '2023'], $chart->pluck('label')->all());
        $this->assertSame(['88%', '72%', '48%', '29%', '17%'], $chart->pluck('value')->all());
        $this->assertLessThanOrEqual(152, $chart->max('height'));
        $this->assertGreaterThanOrEqual(3, $chart->min('height'));
    }

    public function test_recent_activity_mixes_tutorships_requests_and_validated_experiences(): void
    {
        $this->seed(DatabaseSeeder::class);

        $activity = (new ProgramMetrics)->panel()['recentActivity'];

        $this->assertCount(5, $activity);
        foreach ($activity as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('meta', $item);
        }
    }

    public function test_indicator_screen_metrics(): void
    {
        $this->seed(DatabaseSeeder::class);

        $data = (new ProgramMetrics)->indicators();
        $cards = collect($data['keyIndicators'])->keyBy('label');

        $this->assertSame('6', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('1', $cards['PRÁCTICAS EN CURSO']['value']);
        $this->assertSame('17% de los activos', $cards['PRÁCTICAS EN CURSO']['detail']);
        $this->assertSame('1', $cards['TRABAJOS DE GRADO']['value']);
        $this->assertSame('2', $cards['SOLICITUDES DEL PERIODO']['value']);
        $this->assertSame('2 aún sin decisión', $cards['SOLICITUDES DEL PERIODO']['detail']);
        $this->assertSame('text-[var(--color-warning)]', $cards['SOLICITUDES DEL PERIODO']['valueClass']);

        $histogram = collect($data['papaHistogram']);
        $this->assertSame(['< 3.0', '3.0–3.3', '3.3–3.6', '3.6–4.0', '4.0–4.3', '> 4.3'], $histogram->pluck('range')->all());
        $this->assertSame(['1', '1', '1', '2', '2', '1'], $histogram->pluck('value')->all());
        $this->assertSame([71, 71, 71, 141, 141, 71], $histogram->pluck('height')->all());

        $participation = collect($data['participation']);
        $this->assertCount(4, $participation);
        $this->assertSame('1 · 17%', $participation->first()['value']);

        $byType = collect($data['requestsByType'])->keyBy('type');
        $this->assertSame(['filed' => '1', 'review' => '0', 'decided' => '0', 'avgTime' => '—'], collect($byType['Cancelación de asignatura'])->only(['filed', 'review', 'decided', 'avgTime'])->all());
        $this->assertSame('1', $byType['Reingreso al programa']['review']);
        $this->assertSame('0', $byType['Otras']['filed']);
    }

    public function test_an_empty_database_yields_zeroes_and_dashes_without_errors(): void
    {
        $metrics = new ProgramMetrics;

        $panel = $metrics->panel();
        $cards = collect($panel['indicators'])->keyBy('label');
        $this->assertSame('0', $cards['ESTUDIANTES ACTIVOS']['value']);
        $this->assertSame('—', $cards['PAPA PROMEDIO']['value']);
        $this->assertSame('—', $cards['AVANCE MEDIO']['value']);
        $this->assertSame('0', $cards['ALERTAS ABIERTAS']['value']);
        $this->assertSame([], $panel['attentionRows']);
        $this->assertSame([], $panel['cohortChart']);
        $this->assertSame([], $panel['recentActivity']);

        $data = $metrics->indicators();
        $this->assertCount(6, $data['papaHistogram']);
        $this->assertSame(['0', '0', '0', '0', '0', '0'], collect($data['papaHistogram'])->pluck('value')->all());
        $this->assertSame([], $data['participation']);
        $this->assertSame([], $data['requestsByType']);
    }
}
