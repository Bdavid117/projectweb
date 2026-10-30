<?php

namespace App\Enums;

enum Role: string
{
    case Estudiante = 'estudiante';
    case Profesor = 'profesor';
    case Coordinacion = 'coordinacion';

    public function label(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Profesor => 'Profesor',
            self::Coordinacion => 'Coordinación',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Estudiante => 'requests.create',
            default => 'panel.index',
        };
    }
}
