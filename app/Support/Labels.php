<?php

namespace App\Support;

use App\Models\AcademicTerm;
use App\Models\CommitteeRequest;
use Carbon\CarbonInterface;

class Labels
{
    /** @return array{0: string, 1: string} */
    public static function studentStatus(string $estado): array
    {
        return match ($estado) {
            'activo' => ['Activo', 'success'],
            'en_riesgo' => ['En riesgo', 'danger'],
            'intercambio' => ['Intercambio', 'info'],
            'egresado' => ['Egresado', 'primary'],
            default => [ucfirst(str_replace('_', ' ', $estado)), 'info'],
        };
    }

    /** @return array{0: string, 1: string} */
    public static function requestStatus(CommitteeRequest $request): array
    {
        return match ($request->estado) {
            'radicada' => ['Radicada', 'info'],
            'en_revision' => ['En revisión', 'warning'],
            default => match ($request->decision) {
                'aprobada' => ['Aprobada', 'success'],
                'rechazada' => ['Rechazada', 'danger'],
                'aplazada' => ['Aplazada', 'primary'],
                default => ['Con decisión', 'success'],
            },
        };
    }

    public static function stateLabel(string $estado): string
    {
        return match ($estado) {
            'radicada' => 'Radicada',
            'en_revision' => 'En revisión',
            'con_decision' => 'Con decisión',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }

    public static function period(CarbonInterface $date): string
    {
        return $date->year.'-'.($date->month <= 6 ? 1 : 2);
    }

    public static function date(?CarbonInterface $date): string
    {
        return $date?->format('d/m/Y') ?? '—';
    }

    public static function termLabel(?AcademicTerm $term): string
    {
        return $term ? $term->codigo.'S' : 'sin periodo';
    }

    public static function bytes(int $bytes): string
    {
        return $bytes < 1048576
            ? round($bytes / 1024).' KB'
            : number_format($bytes / 1048576, 1, ',', '.').' MB';
    }
}
