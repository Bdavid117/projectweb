<?php

namespace App\Support;

/**
 * Campos que aplican a cada categoría de experiencia (RF-30, RF-31).
 * Cada categoría lista los campos opcionales que muestra el formulario; los marcados
 * como `true` son obligatorios para esa categoría.
 */
class ExperienceFields
{
    public const OPTIONAL = ['alcance', 'institucion', 'convenio', 'docente_id', 'rol'];

    private const BY_CATEGORY = [
        'Pasantía' => ['institucion' => true, 'convenio' => false, 'docente_id' => false, 'rol' => true],
        'Investigación' => ['alcance' => false, 'docente_id' => true, 'rol' => false],
        'Deporte y cultura' => ['institucion' => false, 'rol' => false],
        'Eventos y publicaciones' => ['alcance' => true, 'institucion' => false],
        'Vinculación' => ['institucion' => true, 'docente_id' => false, 'rol' => true],
        'Programa' => ['docente_id' => false, 'rol' => false],
    ];

    /** @return array<string, bool> campo => obligatorio */
    public static function for(?string $categoria): array
    {
        return self::BY_CATEGORY[$categoria] ?? [];
    }

    /** @return array<string, array<string, bool>> categoría => campos, para el formulario */
    public static function all(): array
    {
        return self::BY_CATEGORY;
    }
}
