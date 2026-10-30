<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Alert;
use App\Models\CommitteeRequest;
use App\Models\Experience;
use App\Models\TutorAssignment;
use App\Models\TutorRequest;
use App\Models\User;

class Notifications
{
    private const LIMIT = 5;

    /** @return list<array{icon: string, text: string, meta: string, url: string}> */
    public static function for(User $user): array
    {
        return match ($user->role) {
            Role::Coordinacion => self::forCoordination(),
            Role::Profesor => self::forTutor($user),
            Role::Estudiante => self::forStudent($user),
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
        return Alert::with('student.user')->whereIn('student_id', TutorAssignment::studentIdsOf($user))->where('atendida', false)
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

        $tutorRequests = TutorRequest::with('tutor')->where('student_id', $studentId)->where('estado', TutorRequest::PENDING)
            ->latest()->limit(self::LIMIT)->get()
            ->map(fn (TutorRequest $t) => [
                'at' => $t->created_at,
                'icon' => 'user-plus',
                'text' => $t->tutor->name.' quiere acompañarle como tutor',
                'meta' => 'Responda la solicitud · '.$t->created_at->diffForHumans(),
                'url' => route('my-tutor.show'),
            ]);

        $requests = CommitteeRequest::with('type')->where('student_id', $studentId)
            ->latest('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (CommitteeRequest $r) => [
                'at' => $r->updated_at,
                'icon' => 'file-text',
                'text' => $r->type->nombre.': '.Labels::requestStatus($r)[0],
                'meta' => $r->updated_at->diffForHumans(),
                'url' => route('requests.show', $r),
            ]);

        // Lo que espera una respuesta del estudiante va primero.
        return $tutorRequests->concat($requests)->take(self::LIMIT)
            ->map(fn (array $item) => collect($item)->except('at')->all())->values()->all();
    }
}
