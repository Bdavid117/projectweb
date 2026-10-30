<?php

namespace App\Support;

use App\Enums\Role;

class Navigation
{
    public static function forRole(Role $role, string $active): array
    {
        return match ($role) {
            Role::Estudiante => self::studentPortal($active),
            Role::Profesor => self::professor($active),
            Role::Coordinacion => self::coordinator($active),
        };
    }

    public static function coordinator(string $active): array
    {
        return [
            ['label' => 'GENERAL', 'items' => self::mark(self::coordinatorGeneralItems(), $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
        ];
    }

    public static function studentPortal(string $active): array
    {
        $items = [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'perfil', 'icon' => 'user', 'label' => 'Mi perfil', 'href' => '/perfil'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Mis solicitudes', 'href' => '/solicitudes'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Mis experiencias', 'href' => '/experiencias'],
            ['key' => 'trabajo-grado', 'icon' => 'book-open', 'label' => 'Mi trabajo de grado', 'href' => '/trabajo-de-grado'],
            ['key' => 'historia', 'icon' => 'trending-up', 'label' => 'Mi historia académica', 'href' => '/historia-academica'],
            ['key' => 'mi-tutor', 'icon' => 'users', 'label' => 'Mi tutor', 'href' => '/mi-tutor'],
        ];

        return [
            ['label' => 'GENERAL', 'items' => self::mark($items, $active)],
            ['label' => null, 'items' => self::mark([
                ['key' => 'practica', 'icon' => 'briefcase', 'label' => 'Mi práctica', 'href' => '/practica'],
            ], $active)],
        ];
    }

    /** El profesor es también tutor: el grupo TUTORÍA reúne lo que antes tenía el rol aparte. */
    private static function professor(string $active): array
    {
        $items = array_values(array_filter(
            self::coordinatorGeneralItems(),
            static fn (array $item) => $item['key'] !== 'indicadores'
        ));

        return [
            ['label' => 'GENERAL', 'items' => self::mark($items, $active)],
            ['label' => 'TUTORÍA', 'items' => self::mark([
                ['key' => 'mis-asesorias', 'icon' => 'clipboard-list', 'label' => 'Mis asesorías', 'href' => '/mis-asesorias'],
                ['key' => 'solicitar-tutoria', 'icon' => 'user-plus', 'label' => 'Solicitar tutoría', 'href' => '/tutoria/solicitudes'],
            ], $active)],
        ];
    }

    private static function coordinatorGeneralItems(): array
    {
        return [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'estudiantes', 'icon' => 'users', 'label' => 'Estudiantes', 'href' => '/estudiantes'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Experiencias', 'href' => '/experiencias'],
            ['key' => 'indicadores', 'icon' => 'bar-chart-3', 'label' => 'Indicadores', 'href' => '/indicadores'],
        ];
    }

    private static function adminItems(): array
    {
        return [
            ['key' => 'usuarios', 'icon' => 'shield', 'label' => 'Usuarios y roles', 'href' => '/admin/usuarios'],
            ['key' => 'cargas', 'icon' => 'upload', 'label' => 'Cargas desde Excel', 'href' => '/admin/cargas'],
            ['key' => 'catalogos', 'icon' => 'list', 'label' => 'Catálogos', 'href' => '/admin/catalogos'],
            ['key' => 'bitacora', 'icon' => 'history', 'label' => 'Bitácora', 'href' => '/admin/bitacora'],
        ];
    }

    private static function mark(array $items, string $active): array
    {
        return array_map(
            static fn (array $item) => $item + ['active' => $item['key'] === $active],
            $items
        );
    }
}
