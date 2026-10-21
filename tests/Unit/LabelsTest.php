<?php

namespace Tests\Unit;

use App\Models\CommitteeRequest;
use App\Support\Labels;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class LabelsTest extends TestCase
{
    public function test_student_status_maps_to_text_and_tone(): void
    {
        $this->assertSame(['Activo', 'success'], Labels::studentStatus('activo'));
        $this->assertSame(['En riesgo', 'danger'], Labels::studentStatus('en_riesgo'));
        $this->assertSame(['Intercambio', 'info'], Labels::studentStatus('intercambio'));
        $this->assertSame(['Algo raro', 'info'], Labels::studentStatus('algo_raro'));
    }

    public function test_request_status_uses_the_decision_once_decided(): void
    {
        $this->assertSame(['Radicada', 'info'], Labels::requestStatus(new CommitteeRequest(['estado' => 'radicada'])));
        $this->assertSame(['En revisión', 'warning'], Labels::requestStatus(new CommitteeRequest(['estado' => 'en_revision'])));
        $this->assertSame(['Aprobada', 'success'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'aprobada'])));
        $this->assertSame(['Rechazada', 'danger'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'rechazada'])));
        $this->assertSame(['Aplazada', 'primary'], Labels::requestStatus(new CommitteeRequest(['estado' => 'con_decision', 'decision' => 'aplazada'])));
    }

    public function test_period_date_and_bytes_formatting(): void
    {
        $this->assertSame('2026-1', Labels::period(Carbon::parse('2026-06-30')));
        $this->assertSame('2026-2', Labels::period(Carbon::parse('2026-07-01')));
        $this->assertSame('12/09/2026', Labels::date(Carbon::parse('2026-09-12')));
        $this->assertSame('—', Labels::date(null));
        $this->assertSame('340 KB', Labels::bytes(348160));
        $this->assertSame('1,2 MB', Labels::bytes(1258291));
        $this->assertSame('Radicada', Labels::stateLabel('radicada'));
        $this->assertSame('En revisión', Labels::stateLabel('en_revision'));
        $this->assertSame('Con decisión', Labels::stateLabel('con_decision'));
    }
}
