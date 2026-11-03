<?php

namespace App\Support;

use App\Models\Experience;
use App\Models\Internship;
use App\Models\Student;
use App\Models\Thesis;

/** Datos del panel propio del estudiante: solo lee lo que pertenece a la ficha recibida. */
class StudentDashboard
{
    public static function for(Student $student): array
    {
        $header = StudentProfile::header($student);
        $latest = $student->academicRecords()->with('term')->get()->sortBy(fn ($r) => $r->term->fecha_inicio)->last();

        $requests = $student->requests()->with('type')->latest('fecha_radicacion')->limit(5)->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type->nombre,
                'when' => $r->fecha_radicacion->diffForHumans(),
                'status' => Labels::requestStatus($r),
            ])->all();

        $thesis = Thesis::where('student_id', $student->id)->latest('id')->first();
        $internship = Internship::where('student_id', $student->id)->latest('id')->first();

        $progress = $latest ? (int) round((float) $latest->porcentaje_avance) : null;
        $experiences = [
            'validated' => $student->experiences()->where('validada', true)->count(),
            'pending' => Experience::where('student_id', $student->id)->where('validada', false)->whereNull('motivo_rechazo')->count(),
        ];

        return [
            'summary' => [
                'progress' => $progress,
                'papa' => $header['papa'],
                'enrollment' => $latest?->numero_matricula ? $latest->numero_matricula.'.ª matrícula' : null,
                'open' => $student->requests()->whereIn('estado', ['radicada', 'en_revision'])->count(),
                'experiences' => $experiences,
            ],
            'journey' => self::journey($student, $header, $progress, $internship, $thesis),
            'header' => $header,
            'requests' => $requests,
            'thesis' => $thesis ? ['title' => $thesis->titulo ?? 'Sin título', 'status' => ucfirst(str_replace('_', ' ', $thesis->estado))] : null,
            'internship' => $internship ? ['period' => Labels::date($internship->fecha_inicio).' – '.($internship->fecha_fin ? Labels::date($internship->fecha_fin) : 'en curso')] : null,
        ];
    }

    /**
     * Recorrido del estudiante: ingreso, práctica, trabajo de grado y grado. `state` es done, current o pending.
     * La práctica y el trabajo no tienen un punto fijo en el plan: la vista los ubica hacia el final del recorrido.
     */
    private static function journey(Student $student, array $header, ?int $progress, ?Internship $internship, ?Thesis $thesis): array
    {
        $internshipState = match (true) {
            $internship === null => 'pending',
            $internship->fecha_fin === null || $internship->fecha_fin->isFuture() => 'current',
            default => 'done',
        };
        $thesisState = match (true) {
            $thesis === null => 'pending',
            $thesis->estado === 'en_desarrollo' => 'current',
            default => 'done',
        };

        return [
            'progress' => $progress,
            'steps' => [
                ['key' => 'ingreso', 'title' => 'Ingreso', 'detail' => $header['cohort'], 'state' => 'done'],
                ['key' => 'practica', 'title' => 'Práctica', 'state' => $internshipState, 'detail' => match ($internshipState) {
                    'pending' => 'Sin registrar',
                    'current' => 'En curso desde el '.Labels::date($internship->fecha_inicio),
                    default => 'Terminó el '.Labels::date($internship->fecha_fin),
                }],
                ['key' => 'trabajo', 'title' => 'Trabajo de grado', 'state' => $thesisState, 'detail' => $thesis
                    ? ucfirst(Labels::thesisModality($thesis->modalidad)).', '.($thesis->estado === 'en_desarrollo' ? 'desde '.Labels::termLabel($thesis->term) : Labels::stateLabel($thesis->estado))
                    : 'Sin registrar'],
                ['key' => 'grado', 'title' => 'Grado', 'state' => 'pending', 'detail' => 'Meta del plan'],
            ],
        ];
    }
}
