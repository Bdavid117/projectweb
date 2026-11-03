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

    /** Pantalla de inicio tras entrar: todos los roles aterrizan en su panel («Mi panel» para el estudiante). */
    public function homeRoute(): string
    {
        return 'panel.index';
    }
}
