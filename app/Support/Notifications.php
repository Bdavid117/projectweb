<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\TutorAssignment;
use App\Models\User;

class Notifications
{
    private const LIMIT = 5;

    /** @return list<array{icon: string, text: string, meta: string, url: string}> */
    public static function for(User $user): array
    {
        return match ($user->role) {
            Role::Coordinacion => self::forCoordination(),
            Role::Tutor => self::forTutor($user),
            Role::Estudiante => self::forStudent($user),
            default => [],
        };
    }

    private static function forCoordination(): array
    {
        $requests = CommitteeRequest::with(['student.user', 'type'])->where('estado', '!=', 'con_decision')
            ->latest('fecha_radicacion')->latest('id')->limit(self::LIMIT)->get()
            ->map(fn (CommitteeRequest $r) => [
                'at' => $r->fecha_radicacion,
                'icon' => 'file-text',
                'text' => 'Solicitud de '.mb_strtolower($r->type->nombre).' sin decisión',
                'meta' => ($r->student->user?->name ?? $r->student->codigo).' · '.$r->fecha_radicacion->diffForHumans(),
                'url' => route('requests.show', $r),
            ]);

        $experiences = Experience::with('student.user')->where('validada', false)->whereNull('motivo_rechazo')
            ->latest('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (Experience $e) => [
                'at' => $e->updated_at,
                'icon' => 'sparkles',
                'text' => 'Experiencia por validar: '.$e->nombre,
                'meta' => ($e->student->user?->name ?? $e->student->codigo).' · '.$e->updated_at->diffForHumans(),
                'url' => route('experiencias.index', ['estado' => 'pendiente']),
            ]);

        return $requests->concat($experiences)->sortByDesc('at')->take(self::LIMIT)
            ->map(fn (array $item) => collect($item)->except('at')->all())->values()->all();
    }

    private static function forTutor(User $user): array
    {
        $studentIds = TutorAssignment::where('tutor_id', $user->id)
            ->where('term_id', AcademicTerm::current()?->id ?? 0)->pluck('student_id');

        return Alert::with('student.user')->whereIn('student_id', $studentIds)->where('atendida', false)
            ->latest('fecha_deteccion')->latest('id')->limit(self::LIMIT)->get()
            ->map(fn (Alert $a) => [
                'icon' => 'triangle-alert',
                'text' => $a->detalle ?? $a->tipo,
                'meta' => ($a->student->user?->name ?? $a->student->codigo).' · '.$a->fecha_deteccion->diffForHumans(),
                'url' => route('students.show', $a->student_id),
            ])->all();
    }

    private static function forStudent(User $user): array
    {
        $studentId = $user->student?->id;

        if ($studentId === null) {
            return [];
        }

        return CommitteeRequest::with('type')->where('student_id', $studentId)
            ->latest('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (CommitteeRequest $r) => [
                'icon' => 'file-text',
                'text' => $r->type->nombre.': '.Labels::requestStatus($r)[0],
                'meta' => $r->updated_at->diffForHumans(),
                'url' => route('requests.show', $r),
            ])->all();
    }
}
