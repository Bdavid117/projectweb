<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\Internship;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\Thesis;
use App\Models\Tutorship;
use Illuminate\Database\Eloquent\Builder;

class ProgramMetrics
{
    private const COHORT_BAR_HEIGHT = 152;

    private const HISTOGRAM_BAR_HEIGHT = 141;

    private ?AcademicTerm $term = null;

    private bool $termLoaded = false;

    public function term(): ?AcademicTerm
    {
        if (! $this->termLoaded) {
            $this->term = AcademicTerm::current();
            $this->termLoaded = true;
        }

        return $this->term;
    }

    public function cutoff(): string
    {
        return now()->format('d/m/Y');
    }

    public function panel(): array
    {
        $term = $this->term();
        $records = $this->records($term);
        $avgPapa = (clone $records)->avg('papa');
        $avgAdvance = (clone $records)->avg('porcentaje_avance');
        $previous = $this->previousTerm($term);
        $prevPapa = $previous ? $this->records($previous)->avg('papa') : null;
        $open = Alert::where('atendida', false)->count();

        $delta = $avgPapa !== null && $prevPapa !== null
            ? ((float) $avgPapa - (float) $prevPapa >= 0 ? '+' : '').number_format((float) $avgPapa - (float) $prevPapa, 1).' frente al periodo anterior'
            : 'Sin periodo anterior para comparar';

        $alerts = ['label' => 'ALERTAS ABIERTAS', 'value' => (string) $open, 'detail' => 'Requieren atención temprana'];
        if ($open > 0) {
            $alerts['valueClass'] = 'text-[var(--color-danger)]';
        }

        return [
            'indicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => (string) Student::where('estado', 'activo')->count(), 'detail' => 'Matriculados en '.Labels::termLabel($term)],
                ['label' => 'PAPA PROMEDIO', 'value' => $avgPapa !== null ? number_format((float) $avgPapa, 1) : '—', 'detail' => $delta],
                ['label' => 'AVANCE MEDIO', 'value' => $avgAdvance !== null ? round((float) $avgAdvance).'%' : '—', 'detail' => 'Créditos cursados del plan'],
                $alerts,
            ],
            'attentionRows' => $this->attentionRows(),
            'cohortChart' => $this->cohortChart($records),
            'recentActivity' => $this->recentActivity(),
        ];
    }

    public function indicators(): array
    {
        $term = $this->term();
        $active = Student::where('estado', 'activo')->count();
        $share = fn (int $n) => $active > 0 ? round($n / $active * 100).'% de los activos' : 'Sin estudiantes activos';

        $internships = Internship::whereDate('fecha_inicio', '<=', today())
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->count();
        $theses = Thesis::where('estado', 'en_desarrollo')->count();

        $periodRequests = CommitteeRequest::query()->where('term_id', $term?->id ?? 0);
        $filed = (clone $periodRequests)->count();
        $pending = (clone $periodRequests)->where('estado', '!=', 'con_decision')->count();

        return [
            'keyIndicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => (string) $active, 'detail' => 'Matriculados en '.Labels::termLabel($term)],
                ['label' => 'PRÁCTICAS EN CURSO', 'value' => (string) $internships, 'detail' => $share($internships)],
                ['label' => 'TRABAJOS DE GRADO', 'value' => (string) $theses, 'detail' => $share($theses)],
                ['label' => 'SOLICITUDES DEL PERIODO', 'value' => (string) $filed, 'detail' => $pending.' aún sin decisión', 'valueClass' => 'text-[var(--color-warning)]'],
            ],
            'papaHistogram' => $this->papaHistogram($term),
            'participation' => $this->participation($active),
            'requestsByType' => $this->requestsByType($term),
        ];
    }

    private function records(?AcademicTerm $term): Builder
    {
        return AcademicRecord::query()->where('academic_records.term_id', $term?->id ?? 0);
    }

    private function previousTerm(?AcademicTerm $term): ?AcademicTerm
    {
        return $term
            ? AcademicTerm::whereHas('academicRecords')->where('fecha_inicio', '<', $term->fecha_inicio)->orderByDesc('fecha_inicio')->first()
            : null;
    }

    private function attentionRows(): array
    {
        return Alert::with('student.user')->latest('fecha_deteccion')->latest('id')->limit(6)->get()->map(fn (Alert $alert) => [
            'name' => $alert->student->user?->name ?? $alert->student->codigo,
            'issue' => $alert->detalle ?? $alert->tipo,
            'date' => Labels::date($alert->fecha_deteccion),
            'status' => $alert->atendida ? 'Atendida' : 'Sin atender',
            'tone' => $alert->atendida ? 'success' : 'danger',
            'url' => route('students.show', $alert->student_id),
        ])->all();
    }

    private function cohortChart(Builder $records): array
    {
        return (clone $records)
            ->join('students', 'students.id', '=', 'academic_records.student_id')
            ->selectRaw('left(students.cohorte, 4) as anio, avg(academic_records.porcentaje_avance) as avance')
            ->groupBy('anio')
            ->orderBy('anio')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->anio,
                'value' => round((float) $row->avance).'%',
                'height' => max(3, (int) round((float) $row->avance / 100 * self::COHORT_BAR_HEIGHT)),
            ])->all();
    }

    private function recentActivity(): array
    {
        $items = collect();

        foreach (Tutorship::with(['student.user', 'tutor'])->latest()->limit(5)->get() as $t) {
            $items->push(['at' => $t->created_at, 'icon' => 'message-square', 'description' => 'Tutoría registrada a '.($t->student->user?->name ?? $t->student->codigo), 'meta' => $t->created_at->diffForHumans().' · '.$t->tutor->name]);
        }
        foreach (CommitteeRequest::with(['student.user', 'type'])->latest('fecha_radicacion')->limit(5)->get() as $r) {
            $items->push(['at' => $r->fecha_radicacion, 'icon' => 'file-text', 'description' => 'Solicitud de '.mb_strtolower($r->type->nombre).' radicada', 'meta' => $r->fecha_radicacion->diffForHumans().' · '.($r->student->user?->name ?? $r->student->codigo)]);
        }
        foreach (Experience::with('validador')->where('validada', true)->latest('updated_at')->limit(5)->get() as $e) {
            $items->push(['at' => $e->updated_at, 'icon' => 'sparkles', 'description' => 'Experiencia validada: '.$e->nombre, 'meta' => $e->updated_at->diffForHumans().' · '.($e->validador?->name ?? 'Coordinación')]);
        }

        return $items->sortByDesc('at')->take(5)->map(fn (array $i) => collect($i)->except('at')->all())->values()->all();
    }

    private function papaHistogram(?AcademicTerm $term): array
    {
        $buckets = [['< 3.0', 3.0], ['3.0–3.3', 3.3], ['3.3–3.6', 3.6], ['3.6–4.0', 4.0], ['4.0–4.3', 4.3], ['> 4.3', INF]];
        $counts = array_fill(0, count($buckets), 0);

        foreach ($this->records($term)->pluck('papa') as $papa) {
            foreach ($buckets as $i => [, $upper]) {
                if ((float) $papa < $upper) {
                    $counts[$i]++;
                    break;
                }
            }
        }

        $max = max($counts) ?: 1;

        return array_map(fn (array $bucket, int $count) => [
            'range' => $bucket[0],
            'value' => (string) $count,
            'height' => (int) round($count / $max * self::HISTOGRAM_BAR_HEIGHT),
        ], $buckets, $counts);
    }

    private function participation(int $active): array
    {
        return Experience::query()
            ->join('experience_types', 'experience_types.id', '=', 'experiences.experience_type_id')
            ->selectRaw('experience_types.categoria as categoria, count(distinct experiences.student_id) as total')
            ->groupBy('experience_types.categoria')
            ->orderByDesc('total')
            ->orderBy('categoria')
            ->get()
            ->map(function ($row) use ($active) {
                $percent = $active > 0 ? (int) min(100, round($row->total / $active * 100)) : 0;

                return ['label' => $row->categoria, 'value' => $row->total.' · '.$percent.'%', 'percent' => $percent];
            })->all();
    }

    private function requestsByType(?AcademicTerm $term): array
    {
        $requests = CommitteeRequest::with('statusHistory')->where('term_id', $term?->id ?? 0)->get()->groupBy('request_type_id');

        return RequestType::orderBy('nombre')->get()->map(function (RequestType $type) use ($requests) {
            $group = $requests->get($type->id, collect());
            $days = $group->where('estado', 'con_decision')->map(function (CommitteeRequest $r) {
                $decided = $r->statusHistory->firstWhere('estado_nuevo', 'con_decision');

                return $decided ? $r->fecha_radicacion->diffInDays($decided->fecha) : null;
            })->filter(fn ($d) => $d !== null);

            return [
                'type' => $type->nombre,
                'filed' => (string) $group->count(),
                'review' => (string) $group->where('estado', 'en_revision')->count(),
                'decided' => (string) $group->where('estado', 'con_decision')->count(),
                'avgTime' => $days->isEmpty() ? '—' : round($days->avg()).' días',
            ];
        })->all();
    }
}
