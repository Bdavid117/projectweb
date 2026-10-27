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

        return [
            'indicators' => [
                ['label' => 'PAPA ACTUAL', 'value' => $header['papa'], 'detail' => $header['enrollment']],
                ['label' => 'AVANCE DEL PLAN', 'value' => $latest ? round((float) $latest->porcentaje_avance).'%' : '—', 'detail' => $latest ? $latest->creditos_cursados.' créditos cursados' : 'Sin registros académicos'],
                ['label' => 'SOLICITUDES ABIERTAS', 'value' => (string) $student->requests()->whereIn('estado', ['radicada', 'en_revision'])->count(), 'detail' => 'Radicadas o en revisión'],
                ['label' => 'EXPERIENCIAS', 'value' => (string) $student->experiences()->where('validada', true)->count(), 'detail' => Experience::where('student_id', $student->id)->where('validada', false)->whereNull('motivo_rechazo')->count().' pendientes de validar'],
            ],
            'header' => $header,
            'requests' => $requests,
            'thesis' => $thesis ? ['title' => $thesis->titulo ?? 'Sin título', 'status' => ucfirst(str_replace('_', ' ', $thesis->estado))] : null,
            'internship' => $internship ? ['period' => Labels::date($internship->fecha_inicio).' – '.($internship->fecha_fin ? Labels::date($internship->fecha_fin) : 'en curso')] : null,
        ];
    }
}
