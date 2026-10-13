<?php

namespace App\Support;

use App\Models\AcademicRecord;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

class StudentProfile
{
    private const PAPA_BAR_HEIGHT = 150;

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
            'papa' => $latest ? number_format((float) $latest->papa, 1) : '—',
        ];
    }

    public static function build(Student $student, ?User $viewer = null): array
    {
        $records = self::records($student);
        $latest = $records->last();
        $previous = $records->count() > 1 ? $records[$records->count() - 2] : null;

        return [
            'student' => self::header($student),
            'tabs' => ['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'],
            'papaChart' => $records->map(fn (AcademicRecord $r) => [
                'period' => $r->term->codigo,
                'value' => number_format((float) $r->papa, 1),
                'height' => (int) round((float) $r->papa / 5 * self::PAPA_BAR_HEIGHT),
            ])->all(),
            'timeline' => self::timeline($student),
            'processes' => self::processes($student),
            'progress' => [
                'percent' => $latest ? (int) round((float) $latest->porcentaje_avance) : 0,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => $latest ? $latest->creditos_cursados.' de '.config('program.total_credits') : '—'],
                    ['label' => 'Cupo del periodo', 'value' => $latest?->cupo_creditos ? $latest->cupo_creditos.' créditos' : '—'],
                    ['label' => 'PAPA actual', 'value' => $latest ? number_format((float) $latest->papa, 1) : '—'],
                    ['label' => 'Promedio anterior', 'value' => $previous ? number_format((float) $previous->papa, 1) : '—'],
                ],
            ],
            'tutorials' => $student->tutorships()->latest('fecha')->limit(3)->get()->map(fn ($t) => [
                'date' => Labels::date($t->fecha),
                'reason' => $t->motivo,
                'note' => (string) $t->observaciones,
                'editUrl' => $viewer && $viewer->can('update', $t) ? route('tutorships.edit', $t) : null,
            ])->all(),
        ];
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
                'tag' => $e->validada ? $e->type->categoria : 'Pendiente de verificación',
                'tone' => $e->validada ? $tone : 'warning',
            ];
        })->all();
    }

    private static function processes(Student $student): array
    {
        $processes = [];

        foreach ($student->theses()->with(['director', 'term'])->get() as $thesis) {
            [$status, $tone] = match ($thesis->estado) {
                'en_desarrollo' => ['En desarrollo', 'warning'],
                'finalizado' => ['Finalizado', 'success'],
                default => [ucfirst(str_replace('_', ' ', $thesis->estado)), 'info'],
            };
            $processes[] = [
                'title' => 'Trabajo de grado · modalidad '.$thesis->modalidad,
                'meta' => 'Director: '.($thesis->director ? 'Prof. '.$thesis->director->name : 'sin asignar').' · '.$thesis->term->codigo.'S',
                'status' => $status,
                'tone' => $tone,
            ];
        }

        foreach ($student->internships()->with(['company', 'asesor'])->get() as $internship) {
            $finished = $internship->fecha_fin && $internship->fecha_fin->isPast();
            $processes[] = [
                'title' => 'Práctica empresarial · '.$internship->company->nombre,
                'meta' => 'Asesor: '.($internship->asesor ? 'Prof. '.$internship->asesor->name : 'sin asignar')
                    .' · '.$internship->fecha_inicio->isoFormat('MMM YYYY').' – '
                    .($internship->fecha_fin ? $internship->fecha_fin->isoFormat('MMM YYYY') : 'en curso'),
                'status' => $finished ? 'Finalizada' : 'En curso',
                'tone' => $finished ? 'success' : 'info',
            ];
        }

        return $processes;
    }
}
