<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\Internship;
use App\Models\RequestType;
use App\Models\Student;
use App\Models\Thesis;
use App\Models\TutorAssignment;
use App\Models\Tutorship;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProgramMetrics
{
    /** Máximo de puntos por fila en los gráficos de puntos: la unidad (estudiantes por punto) se ajusta a este tope. */
    private const MAX_DOTS = 24;

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

    public function panel(?User $viewer = null): array
    {
        $ids = $this->visibleStudentIds($viewer);
        $scoped = $ids !== null;
        $term = $this->term();
        $records = $this->records($term)->when($scoped, fn ($q) => $q->whereIn('academic_records.student_id', $ids));
        $avgPapa = (clone $records)->avg('papa');
        $avgAdvance = (clone $records)->avg('porcentaje_avance');
        $previous = $this->previousTerm($term);
        $prevPapa = $previous ? $this->records($previous)->when($scoped, fn ($q) => $q->whereIn('academic_records.student_id', $ids))->avg('papa') : null;
        $open = $this->alerts($ids)->where('atendida', false)->count();
        $active = Student::where('estado', 'activo')->when($scoped, fn ($q) => $q->whereIn('id', $ids))->count();

        return [
            // Cifras de la frase que abre el panel; la vista las redacta.
            'headline' => [
                'scoped' => $scoped,
                'active' => $active,
                'open' => $open,
                'papa' => $avgPapa !== null ? Labels::decimal($avgPapa) : null,
                'papaDelta' => $avgPapa !== null && $prevPapa !== null ? round((float) $avgPapa - (float) $prevPapa, 1) : null,
                'advance' => $avgAdvance !== null ? (int) round((float) $avgAdvance) : null,
                'term' => Labels::termLabel($term),
            ],
            'cohortMap' => $this->cohortMap($records, $ids),
            'attentionRows' => $this->attentionRows($ids),
            'recentActivity' => $this->recentActivity($ids),
        ];
    }

    public function indicators(): array
    {
        $term = $this->term();
        $active = Student::where('estado', 'activo')->count();

        $internships = Internship::whereDate('fecha_inicio', '<=', today())
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->count();
        $theses = Thesis::where('estado', 'en_desarrollo')->count();

        $periodRequests = CommitteeRequest::query()->where('term_id', $term?->id ?? 0);
        $filed = (clone $periodRequests)->count();
        $pending = (clone $periodRequests)->where('estado', '!=', 'con_decision')->count();

        return [
            // Cifras de la frase que abre Indicadores.
            'headline' => ['active' => $active, 'internships' => $internships, 'theses' => $theses, 'filed' => $filed, 'pending' => $pending],
            'papaHistogram' => $histogram = $this->papaHistogram($term),
            'papaUnit' => $this->dotUnit(array_column($histogram, 'count')),
            'participation' => $participation = $this->participation($active),
            'participationUnit' => $this->dotUnit(array_column($participation, 'count')),
            'requestsByType' => $this->requestsByType($term),
        ];
    }

    /** Estudiantes que representa cada punto para que la fila más larga no pase de MAX_DOTS. */
    private function dotUnit(array $counts): int
    {
        return max(1, (int) ceil(max([1, ...$counts]) / self::MAX_DOTS));
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

    /** Estudiantes cuya información individual puede ver: todos (null), los vinculados al profesor o ninguno. */
    private function visibleStudentIds(?User $viewer): ?array
    {
        return match ($viewer?->role) {
            null, Role::Coordinacion => null,
            Role::Profesor => TutorAssignment::studentIdsOf($viewer),
            default => [],
        };
    }

    private function alerts(?array $ids): Builder
    {
        return Alert::query()->when($ids !== null, fn ($q) => $q->whereIn('student_id', $ids));
    }

    private function attentionRows(?array $ids): array
    {
        return $this->alerts($ids)->with('student.user')->latest('fecha_deteccion')->latest('id')->limit(6)->get()->map(fn (Alert $alert) => [
            'name' => $alert->student->user?->name ?? $alert->student->codigo,
            'issue' => $alert->detalle ?? $alert->tipo,
            'date' => Labels::date($alert->fecha_deteccion),
            'status' => $alert->atendida ? 'Atendida' : 'Sin atender',
            'tone' => $alert->atendida ? 'success' : 'danger',
            'url' => route('students.show', $alert->student_id),
        ])->all();
    }

    /**
     * Un punto por estudiante, en la fila de su cohorte y a la altura de su avance del plan (0 a 100 %).
     * Los que tienen una alerta abierta llevan un anillo. Respeta el alcance del usuario (el profesor solo ve a sus tutorados).
     *
     * @return array{rows: list<array{label: string, dots: list<array{pct: int, alert: bool, label: string}>}>, reading: ?string}
     */
    private function cohortMap(Builder $records, ?array $ids): array
    {
        $withAlert = $this->alerts($ids)->where('atendida', false)->pluck('student_id')->flip();

        $students = (clone $records)
            ->join('students', 'students.id', '=', 'academic_records.student_id')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->select('students.id as student_id', 'students.cohorte', 'students.codigo', 'users.name', 'academic_records.porcentaje_avance')
            ->orderBy('students.cohorte')
            ->orderBy('users.name')
            ->get();

        // Una fila por año de ingreso: la cohorte «2021-2» y la «2021-1» comparten fila.
        $students = $students->each(fn ($row) => $row->year = substr((string) $row->cohorte, 0, 4));

        $rows = $students->groupBy('year')->map(fn ($group, $year) => [
            'label' => (string) $year,
            'dots' => $group->map(function ($row) use ($withAlert) {
                $pct = (int) max(0, min(100, round((float) $row->porcentaje_avance)));
                $alert = $withAlert->has($row->student_id);

                return [
                    'pct' => $pct,
                    'alert' => $alert,
                    'label' => ($row->name ?? $row->codigo).' · '.$pct.' % del plan'.($alert ? ', con una alerta abierta' : ''),
                ];
            })->values()->all(),
        ])->values()->all();

        $byCohort = $students->filter(fn ($row) => $withAlert->has($row->student_id))->countBy('year')->sortDesc();
        $total = $byCohort->sum();
        $top = $byCohort->take(2);

        $reading = match (true) {
            $students->isEmpty() => null,
            $total === 0 => 'Ninguna alerta abierta en este periodo.',
            $byCohort->count() === 1 => 'Las '.$total.' alertas abiertas están en la cohorte '.$top->keys()->first().'.',
            default => 'Las cohortes '.$top->keys()->join(' y ').' concentran '.$top->sum().' de las '.$total.' alertas abiertas.',
        };

        return ['rows' => $rows, 'reading' => $reading];
    }

    private function recentActivity(?array $ids): array
    {
        $items = collect();

        $tutorships = Tutorship::with(['student.user', 'tutor'])
            ->when($ids !== null, fn ($q) => $q->whereIn('student_id', $ids))
            ->latest()->limit(5)->get();
        foreach ($tutorships as $t) {
            $items->push(['at' => $t->created_at, 'icon' => 'message-square', 'description' => 'Tutoría registrada a '.($t->student->user?->name ?? $t->student->codigo), 'meta' => $t->created_at->diffForHumans().' · '.$t->tutor->name]);
        }
        foreach (CommitteeRequest::with(['student.user', 'type'])->when($ids !== null, fn ($q) => $q->whereIn('student_id', $ids))->latest('fecha_radicacion')->limit(5)->get() as $r) {
            $items->push(['at' => $r->fecha_radicacion, 'icon' => 'file-text', 'description' => 'Solicitud de '.mb_strtolower($r->type->nombre).' radicada', 'meta' => $r->fecha_radicacion->diffForHumans().' · '.($r->student->user?->name ?? $r->student->codigo)]);
        }
        foreach (Experience::with('validador')->when($ids !== null, fn ($q) => $q->whereIn('student_id', $ids))->where('validada', true)->latest('updated_at')->limit(5)->get() as $e) {
            $items->push(['at' => $e->updated_at, 'icon' => 'sparkles', 'description' => 'Experiencia validada: '.$e->nombre, 'meta' => $e->updated_at->diffForHumans().' · '.($e->validador?->name ?? 'Coordinación')]);
        }

        return $items->sortByDesc('at')->take(5)->map(fn (array $i) => collect($i)->except('at')->all())->values()->all();
    }

    private function papaHistogram(?AcademicTerm $term): array
    {
        $buckets = [['Menos de 3,0', 3.0], ['3,0 a 3,3', 3.3], ['3,3 a 3,6', 3.6], ['3,6 a 4,0', 4.0], ['4,0 a 4,3', 4.3], ['Más de 4,3', INF]];
        $counts = array_fill(0, count($buckets), 0);

        foreach ($this->records($term)->pluck('papa') as $papa) {
            foreach ($buckets as $i => [, $upper]) {
                if ((float) $papa < $upper) {
                    $counts[$i]++;
                    break;
                }
            }
        }

        return array_map(fn (array $bucket, int $count) => [
            'range' => $bucket[0],
            'value' => (string) $count,
            'count' => $count,
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

                return ['label' => $row->categoria, 'value' => $row->total.' · '.$percent.'%', 'percent' => $percent, 'count' => (int) $row->total];
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
