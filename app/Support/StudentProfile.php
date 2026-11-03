<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\CommitteeRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentProfile
{
    /** @var array<string, array{0: string, 1: string}> */
    private const CATEGORY_STYLE = [
        'Investigación' => ['sparkles', 'primary'],
        'Vinculación' => ['book-open', 'accent'],
        'Pasantía' => ['plane', 'restricted'],
        'Eventos y publicaciones' => ['mic', 'warning'],
        'Deporte y cultura' => ['trophy', 'success'],
        'Programa' => ['star', 'info'],
    ];

    public static function header(Student $student): array
    {
        $student->loadMissing('user');
        $latest = self::records($student)->last();
        $term = AcademicTerm::current();
        $tutor = $term
            ? $student->tutorAssignments()->where('term_id', $term->id)->with('tutor')->first()?->tutor
            : null;

        return [
            'id' => $student->id,
            'initials' => $student->user?->initials() ?? strtoupper(substr($student->codigo, 0, 2)),
            'name' => $student->user?->name ?? $student->codigo,
            'code' => 'Código '.$student->codigo,
            'cohort' => 'Cohorte '.$student->cohorte,
            'enrollment' => $latest?->numero_matricula ? $latest->numero_matricula.'.ª matrícula' : 'Sin matrícula registrada',
            'tutor' => $tutor ? 'Tutor asignado: '.$tutor->name : 'Sin tutor asignado',
            'papa' => $latest ? Labels::decimal($latest->papa) : '—',
        ];
    }

    public static function build(Student $student, ?User $viewer = null): array
    {
        $records = self::records($student);
        $latest = $records->last();
        $seesTutorships = in_array($viewer?->role, [Role::Profesor, Role::Coordinacion], true);
        $previous = $records->count() > 1 ? $records[$records->count() - 2] : null;

        // Orden de la issue: General, Académica, Experiencias, Trabajo de grado, Práctica, [Solicitudes], [Tutorías].
        $tabs = [
            'general' => 'General',
            'academica' => 'Académica',
            'experiencias' => 'Experiencias',
            'trabajo-grado' => 'Trabajo de grado',
            'practica' => 'Práctica',
            'solicitudes' => 'Solicitudes',
        ];
        if ($seesTutorships) {
            $tabs['tutorias'] = 'Tutorías';
        }

        return [
            'student' => self::header($student),
            'tabs' => $tabs,
            'notebook' => self::notebook($student, $records, $seesTutorships),
            'academicRows' => $records->reverse()->values()->map(fn (AcademicRecord $r) => [
                'period' => $r->term->codigo,
                'papa' => Labels::decimal($r->papa),
                'average' => $r->promedio_periodo !== null ? Labels::decimal($r->promedio_periodo) : '—',
                'credits' => $r->creditos_cursados !== null ? $r->creditos_cursados.' de '.config('program.total_credits') : '—',
                'advance' => (int) round((float) $r->porcentaje_avance),
            ])->all(),
            'timeline' => self::timeline($student),
            'theses' => self::theses($student),
            'internships' => self::internships($student),
            'requests' => self::requests($student),
            'progress' => [
                'percent' => $latest ? (int) round((float) $latest->porcentaje_avance) : 0,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => $latest ? $latest->creditos_cursados.' de '.config('program.total_credits') : '—'],
                    ['label' => 'Cupo del periodo', 'value' => $latest?->cupo_creditos ? $latest->cupo_creditos.' créditos' : '—'],
                    ['label' => 'PAPA actual', 'value' => $latest ? Labels::decimal($latest->papa) : '—'],
                    ['label' => 'Promedio anterior', 'value' => $previous ? Labels::decimal($previous->papa) : '—'],
                ],
            ],
            'showTutorials' => $seesTutorships,
            'tutorials' => ! $seesTutorships ? [] : $student->tutorships()->latest('fecha')->latest('id')->limit(20)->get()->map(fn ($t) => [
                'date' => Labels::date($t->fecha),
                'reason' => $t->motivo,
                'note' => (string) $t->observaciones,
                'editUrl' => $viewer && $viewer->can('update', $t) ? route('tutorships.edit', $t) : null,
            ])->all(),
        ];
    }

    /**
     * Cuaderno de ruta: el PAPA de cada periodo, las experiencias y las tutorías en un solo eje, del periodo más reciente
     * al más antiguo. Cada entrada lleva la corriente a la que pertenece (académico, experiencia o acompañamiento).
     *
     * @return list<array{period: string, items: list<array{kind: string, text: string}>}>
     */
    private static function notebook(Student $student, Collection $records, bool $seesTutorships): array
    {
        $terms = AcademicTerm::orderBy('fecha_inicio')->get();
        $periodOf = fn ($date) => $terms->first(fn (AcademicTerm $t) => $date->between($t->fecha_inicio, $t->fecha_fin))?->codigo ?? (string) $date->year;

        $entries = $records->map(fn (AcademicRecord $r) => [
            'period' => $r->term->codigo,
            'at' => $r->term->fecha_fin ?? $r->term->fecha_inicio,
            'kind' => 'academico',
            'text' => 'PAPA '.Labels::decimal($r->papa).($r->promedio_periodo !== null ? ' · promedio del periodo '.Labels::decimal($r->promedio_periodo) : ''),
        ]);

        foreach ($student->experiences()->get() as $experience) {
            $entries->push([
                'period' => $periodOf($experience->fecha_inicio),
                'at' => $experience->fecha_inicio,
                'kind' => 'experiencia',
                'text' => $experience->nombre.match ($experience->estado()) {
                    'validada' => '',
                    'rechazada' => ' (rechazada)',
                    default => ' (por verificar)',
                },
            ]);
        }

        if ($seesTutorships) {
            foreach ($student->tutorships()->get() as $tutorship) {
                $entries->push([
                    'period' => $periodOf($tutorship->fecha),
                    'at' => $tutorship->fecha,
                    'kind' => 'acompanamiento',
                    'text' => 'Tutoría del '.$tutorship->fecha->isoFormat('D [de] MMMM').($tutorship->motivo ? ': '.$tutorship->motivo : ''),
                ]);
            }
        }

        return $entries->groupBy('period')
            ->map(fn (Collection $items, string $period) => [
                'period' => $period,
                'latest' => $items->max('at'),
                'items' => $items->sortByDesc('at')->map(fn (array $item) => ['kind' => $item['kind'], 'text' => $item['text']])->values()->all(),
            ])
            ->sortByDesc('latest')
            ->map(fn (array $group) => ['period' => $group['period'], 'items' => $group['items']])
            ->values()
            ->all();
    }

    private static function records(Student $student): Collection
    {
        return $student->academicRecords()->with('term')->get()
            ->sortBy(fn (AcademicRecord $r) => $r->term->fecha_inicio)
            ->values();
    }

    private static function timeline(Student $student): array
    {
        return $student->experiences()->with('type')->orderBy('fecha_inicio')->get()->map(function ($e) {
            [$icon, $tone] = self::CATEGORY_STYLE[$e->type->categoria] ?? ['sparkles', 'primary'];

            return [
                'icon' => $icon,
                'description' => $e->nombre,
                'period' => Labels::period($e->fecha_inicio),
                'tag' => match ($e->estado()) {
                    'validada' => $e->type->categoria,
                    'rechazada' => 'Rechazada',
                    default => 'Pendiente de verificación',
                },
                'tone' => match ($e->estado()) {
                    'validada' => $tone,
                    'rechazada' => 'danger',
                    default => 'warning',
                },
            ];
        })->all();
    }

    /** Solicitudes del estudiante ante el comité, para la pestaña Solicitudes (RF-08). */
    private static function requests(Student $student): array
    {
        return $student->requests()->with(['type', 'term'])->latest('fecha_radicacion')->latest('id')->get()
            ->map(function (CommitteeRequest $request) {
                [$status, $tone] = Labels::requestStatus($request);

                return [
                    'date' => Labels::date($request->fecha_radicacion),
                    'type' => $request->type->nombre,
                    'period' => $request->term->codigo,
                    'status' => $status,
                    'tone' => $tone,
                    'url' => route('requests.show', $request),
                ];
            })->all();
    }

    private static function theses(Student $student): array
    {
        return $student->theses()->with(['director', 'term'])->get()->map(function ($thesis) {
            [$status, $tone] = match ($thesis->estado) {
                'en_desarrollo' => ['En desarrollo', 'warning'],
                'finalizado' => ['Finalizado', 'success'],
                default => [ucfirst(str_replace('_', ' ', $thesis->estado)), 'info'],
            };

            return [
                'title' => 'Trabajo de grado · modalidad '.Labels::thesisModality($thesis->modalidad),
                'meta' => 'Director: '.($thesis->director ? 'Prof. '.$thesis->director->name : 'sin asignar').' · '.$thesis->term->codigo.'S',
                'status' => $status,
                'tone' => $tone,
            ];
        })->all();
    }

    private static function internships(Student $student): array
    {
        return $student->internships()->with(['company', 'asesor'])->get()->map(function ($internship) {
            $finished = $internship->fecha_fin && $internship->fecha_fin->isPast();

            return [
                'title' => 'Práctica empresarial · '.$internship->company->nombre,
                'meta' => 'Asesor: '.($internship->asesor ? 'Prof. '.$internship->asesor->name : 'sin asignar')
                    .' · '.$internship->fecha_inicio->isoFormat('MMM YYYY').' – '
                    .($internship->fecha_fin ? $internship->fecha_fin->isoFormat('MMM YYYY') : 'en curso'),
                'status' => $finished ? 'Finalizada' : 'En curso',
                'tone' => $finished ? 'success' : 'info',
            ];
        })->all();
    }
}
