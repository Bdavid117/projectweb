<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_panel_headline_is_computed_from_the_current_term(): void
    {
        $this->seed(DatabaseSeeder::class);

        $headline = (new ProgramMetrics)->panel()['headline'];

        $this->assertFalse($headline['scoped']);
        $this->assertSame(6, $headline['active']);
        $this->assertSame('2026-2', $headline['term']);
        $this->assertSame('3,7', $headline['papa']);
        $this->assertSame(-0.3, $headline['papaDelta']);
        $this->assertSame(44, $headline['advance']);
        $this->assertSame(3, $headline['open']);
    }

    public function test_the_headline_of_a_professor_only_counts_their_tutored_students(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tutor = User::where('email', 'profesor2@unal.edu.co')->first() ?? User::where('role', 'profesor')->first();

        $headline = (new ProgramMetrics)->panel($tutor)['headline'];

        $this->assertTrue($headline['scoped']);
        $this->assertLessThanOrEqual(6, $headline['active']);
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

    public function test_cohort_map_places_one_dot_per_student_in_the_row_of_their_cohort(): void
    {
        $this->seed(DatabaseSeeder::class);

        $map = (new ProgramMetrics)->panel()['cohortMap'];
        $rows = collect($map['rows']);

        $this->assertSame(['2019', '2020', '2021', '2022', '2023'], $rows->pluck('label')->all());
        $this->assertSame(8, $rows->sum(fn ($row) => count($row['dots'])), 'Un punto por cada estudiante con registro en el periodo, también en riesgo o en intercambio (la leyenda no dice «activo»).');

        foreach ($rows->pluck('dots')->flatten(1) as $dot) {
            $this->assertGreaterThanOrEqual(0, $dot['pct']);
            $this->assertLessThanOrEqual(100, $dot['pct']);
            $this->assertStringContainsString('% del plan', $dot['label']);
        }
        $this->assertSame(88, $rows->firstWhere('label', '2019')['dots'][0]['pct']);
        $this->assertNotNull($map['reading']);
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

        $this->assertSame(['active' => 6, 'internships' => 1, 'theses' => 1, 'filed' => 2, 'pending' => 2], $data['headline']);

        $histogram = collect($data['papaHistogram']);
        $this->assertSame(['Menos de 3,0', '3,0 a 3,3', '3,3 a 3,6', '3,6 a 4,0', '4,0 a 4,3', 'Más de 4,3'], $histogram->pluck('range')->all());
        $this->assertSame([1, 1, 1, 2, 2, 1], $histogram->pluck('count')->all());
        $this->assertSame(1, $data['papaUnit']);

        $participation = collect($data['participation']);
        $this->assertCount(4, $participation);
        $this->assertSame('1 · 17%', $participation->first()['value']);
        $this->assertSame(1, $participation->first()['count']);

        $byType = collect($data['requestsByType'])->keyBy('type');
        $this->assertSame(['filed' => '1', 'review' => '0', 'decided' => '0', 'avgTime' => '—'], collect($byType['Cancelación de asignatura'])->only(['filed', 'review', 'decided', 'avgTime'])->all());
        $this->assertSame('1', $byType['Reingreso al programa']['review']);
        $this->assertSame('0', $byType['Otras']['filed']);
    }

    public function test_dots_are_scaled_so_the_longest_row_never_exceeds_the_cap(): void
    {
        $unit = new \ReflectionMethod(ProgramMetrics::class, 'dotUnit');
        $unit->setAccessible(true);
        $metrics = new ProgramMetrics;

        $this->assertSame(1, $unit->invoke($metrics, [0, 5, 24]));
        $this->assertSame(2, $unit->invoke($metrics, [25]));
        $this->assertSame(5, $unit->invoke($metrics, [120]));
        $this->assertSame(1, $unit->invoke($metrics, []));
    }

    public function test_an_empty_database_yields_zeroes_and_dashes_without_errors(): void
    {
        $metrics = new ProgramMetrics;

        $panel = $metrics->panel();
        $this->assertSame(0, $panel['headline']['active']);
        $this->assertNull($panel['headline']['papa']);
        $this->assertNull($panel['headline']['advance']);
        $this->assertNull($panel['headline']['papaDelta']);
        $this->assertSame(0, $panel['headline']['open']);
        $this->assertSame([], $panel['attentionRows']);
        $this->assertSame(['rows' => [], 'reading' => null], $panel['cohortMap']);
        $this->assertSame([], $panel['recentActivity']);

        $data = $metrics->indicators();
        $this->assertCount(6, $data['papaHistogram']);
        $this->assertSame([0, 0, 0, 0, 0, 0], collect($data['papaHistogram'])->pluck('count')->all());
        $this->assertSame(1, $data['papaUnit']);
        $this->assertSame([], $data['participation']);
        $this->assertSame([], $data['requestsByType']);
    }
}
