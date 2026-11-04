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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProgramMetrics
{
    /** Máximo de puntos por fila en los gráficos de puntos: la unidad (estudiantes por punto) se ajusta a este tope. */
    private const MAX_DOTS = 24;

    /** Tipos de estudiante del filtro del mapa del panel, en el orden de sus botones (clave => etiqueta). */
    private const MAP_KINDS = [
        'activo' => 'Activos',
        'en-riesgo' => 'En riesgo',
        'con-alerta' => 'Con alerta',
        'en-practica' => 'En práctica',
        'trabajo-de-grado' => 'Trabajo de grado',
        'rezagado' => 'Rezagados',
        'sin-tutoria' => 'Sin tutoría reciente',
        'intercambio' => 'Intercambio',
        'egresado' => 'Egresados',
    ];

    private ?AcademicTerm $term = null;

    private bool $termLoaded = false;

    /** Claves válidas del filtro del mapa (la dirección `?tipo=` se valida contra esta lista). */
    public static function mapKinds(): array
    {
        return ['todos', ...array_keys(self::MAP_KINDS)];
    }

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
            'cohortMap' => $cohortMap = $this->cohortMap($records, $ids),
            'attentionRows' => $this->attentionRows($ids, collect($cohortMap['rows'])->pluck('dots')->flatten(1)->pluck('tags', 'id')),
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

    /** @param Collection<int, list<string>> $tagsByStudent las etiquetas de cada punto del mapa, para que la lista siga al filtro. */
    private function attentionRows(?array $ids, $tagsByStudent): array
    {
        // Hasta 12: el filtro del mapa muestra de ahí las 6 que le correspondan, así que no puede quedarse sin candidatas por el tope.
        return $this->alerts($ids)->with('student.user')->latest('fecha_deteccion')->latest('id')->limit(12)->get()->map(fn (Alert $alert) => [
            'tags' => $tagsByStudent->get($alert->student_id, array_values(array_unique([str_replace('_', '-', (string) $alert->student->estado), 'con-alerta']))),
            'id' => $alert->id,
            'student_id' => $alert->student_id,
            'open' => ! $alert->atendida,
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
        $openAlerts = $this->alerts($ids)->where('atendida', false)->orderBy('fecha_deteccion')->get(['student_id', 'tipo', 'detalle'])->groupBy('student_id');
        $inPractice = Internship::whereDate('fecha_inicio', '<=', today())
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', today()))
            ->pluck('student_id')->flip();
        $inThesis = Thesis::where('estado', 'en_desarrollo')->pluck('student_id')->flip();
        $term = $this->term();
        $tutored = $term ? TutorAssignment::where('term_id', $term->id)->pluck('student_id')->flip() : collect();
        $lastTutorship = Tutorship::selectRaw('student_id, max(fecha) as ultima')->groupBy('student_id')->pluck('ultima', 'student_id');
        $staleDays = (int) config('program.tutorship_stale_days');

        $students = (clone $records)
            ->join('students', 'students.id', '=', 'academic_records.student_id')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->select('students.id as student_id', 'students.cohorte', 'students.codigo', 'students.estado', 'users.name', 'academic_records.porcentaje_avance', 'academic_records.papa')
            ->orderBy('students.cohorte')
            ->orderBy('users.name')
            ->get();

        // Una fila por año de ingreso: la cohorte «2021-2» y la «2021-1» comparten fila.
        $students = $students->each(fn ($row) => $row->year = substr((string) $row->cohorte, 0, 4));
        $previous = $term ? AcademicRecord::previousFor($students->pluck('student_id')->all(), $term) : collect();

        $rows = $students->groupBy('year')->map(fn ($group, $year) => [
            'label' => (string) $year,
            'expected' => $this->expectedProgress($term, $year.'-1.5'),
            'dots' => $group->map(function ($row) use ($openAlerts, $inPractice, $inThesis, $term, $tutored, $lastTutorship, $staleDays, $previous) {
                $pct = (int) max(0, min(100, round((float) $row->porcentaje_avance)));
                $alerts = $openAlerts->get($row->student_id, collect());
                $alert = $alerts->isNotEmpty();
                $name = $row->name ?? $row->codigo;
                $tags = [str_replace('_', '-', (string) $row->estado)];
                $alert && $tags[] = 'con-alerta';
                $inPractice->has($row->student_id) && $tags[] = 'en-practica';
                $inThesis->has($row->student_id) && $tags[] = 'trabajo-de-grado';

                // Los de intercambio o egresados no se miden contra el plan: no hay «atraso» ni «sin tutoría» que señalar.
                $measured = in_array($row->estado, ['activo', 'en_riesgo'], true);
                $expected = $this->expectedProgress($term, (string) $row->cohorte);
                $behind = $measured && $pct < $expected - (int) config('program.lag_margin');
                $last = $lastTutorship->get($row->student_id);
                $quiet = $measured && $tutored->has($row->student_id) && ($last === null || now()->diffInDays($last, false) < -$staleDays);
                $behind && $tags[] = 'rezagado';
                $quiet && $tags[] = 'sin-tutoria';

                return [
                    'id' => (int) $row->student_id,
                    'name' => $name,
                    'code' => $row->codigo,
                    'cohort' => $row->cohorte,
                    'pct' => $pct,
                    'papa' => $row->papa !== null ? Labels::decimal($row->papa) : '—',
                    'papaLow' => $row->papa !== null && (float) $row->papa < config('program.papa_alert'),
                    'state' => $row->estado,
                    'stateLabel' => Labels::studentStatus((string) $row->estado)[0],
                    'alert' => $alert,
                    'alerts' => $alerts->count(),
                    'alertNote' => $alerts->first()?->detalle,
                    'expected' => $expected,
                    'behind' => $behind,
                    'prevPct' => ($before = $previous->get($row->student_id)) ? (int) max(0, min(100, round((float) $before->porcentaje_avance))) : null,
                    'lastTutorship' => $last ? Labels::date(Carbon::parse($last)) : null,
                    'tags' => $tags,
                    'url' => route('students.show', $row->student_id),
                    'label' => $name.' · '.$pct.' % del plan'.($alert ? ', con una alerta abierta' : ''),
                ];
            })->values()->all(),
        ])->values()->all();

        $byCohort = $students->filter(fn ($row) => $openAlerts->has($row->student_id))->countBy('year')->sortDesc();
        $total = $byCohort->sum();
        $top = $byCohort->take(2);

        $reading = match (true) {
            $students->isEmpty() => null,
            $total === 0 => 'Ninguna alerta abierta en este periodo.',
            $byCohort->count() === 1 => 'Las '.$total.' alertas abiertas están en la cohorte '.$top->keys()->first().'.',
            default => 'Las cohortes '.$top->keys()->join(' y ').' concentran '.$top->sum().' de las '.$total.' alertas abiertas.',
        };

        // Un botón por tipo de estudiante que exista en el mapa, con cuántos son. Un mismo estudiante puede estar en varios tipos.
        $counts = collect($rows)->pluck('dots')->flatten(1)->pluck('tags')->flatten()->countBy();
        $filters = $students->isEmpty() ? [] : collect([['key' => 'todos', 'label' => 'Todos', 'count' => $students->count()]])
            ->merge(collect(self::MAP_KINDS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => (int) $counts->get($key, 0)])->filter(fn ($f) => $f['count'] > 0)->values())
            ->all();

        return ['rows' => $rows, 'reading' => $reading, 'filters' => $filters, 'total' => $students->count()];
    }

    /**
     * Avance del plan que se esperaría hoy de una cohorte: semestres transcurridos entre su ingreso y el periodo vigente, sobre los semestres del plan
     * (config `plan_semesters`, un supuesto). Se calcula con los códigos de periodo («2026-2»), no con la fecha del día. Acepta «2021-1.5» para la
     * fila de un año, que reúne a las cohortes -1 y -2.
     */
    private function expectedProgress(?AcademicTerm $term, string $cohort): int
    {
        if (! $term || ! preg_match('/^(\d{4})-(\d(?:\.\d)?)$/', $cohort, $in) || ! preg_match('/^(\d{4})-(\d)$/', (string) $term->codigo, $now)) {
            return 0;
        }

        $elapsed = ((int) $now[1] - (int) $in[1]) * 2 + ((int) $now[2] - (float) $in[2]);

        return (int) round(max(0, min(1, $elapsed / max(1, (int) config('program.plan_semesters')))) * 100);
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
