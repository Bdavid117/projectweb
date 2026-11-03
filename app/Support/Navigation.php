<?php

namespace App\Support;

use App\Enums\Role;

/**
 * Navegación por rol. Devuelve una lista de grupos; la barra superior los pinta así:
 *  - grupo sin `label`: sus ítems van sueltos en la barra;
 *  - grupo con `label`: un menú desplegable («Administración», «Tutoría», «Mi trayectoria»).
 * En móvil, los ítems con `tab` (y los grupos con `tab`, que apuntan a su primer ítem) forman las pestañas
 * inferiores; todo lo demás queda en la hoja «Más». Nunca se enlaza a una ruta que el rol no pueda abrir.
 */
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
            self::group(null, null, self::coordinatorItems(), $active),
            self::group('Administración', 'shield', self::adminItems(), $active),
        ];
    }

    public static function studentPortal(string $active): array
    {
        return [
            self::group(null, null, [
                ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel', 'tab' => true],
            ], $active),
            self::group('Mi trayectoria', 'trending-up', [
                ['key' => 'perfil', 'icon' => 'user', 'label' => 'Mi perfil', 'href' => '/perfil'],
                ['key' => 'historia', 'icon' => 'book-open', 'label' => 'Mi historia académica', 'href' => '/historia-academica'],
                ['key' => 'trabajo-grado', 'icon' => 'graduation-cap', 'label' => 'Mi trabajo de grado', 'href' => '/trabajo-de-grado'],
                ['key' => 'practica', 'icon' => 'briefcase', 'label' => 'Mi práctica', 'href' => '/practica'],
            ], $active),
            self::group(null, null, [
                ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes', 'tab' => true],
                ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Experiencias', 'href' => '/experiencias', 'tab' => true],
                ['key' => 'mi-tutor', 'icon' => 'users', 'label' => 'Mi tutor', 'href' => '/mi-tutor'],
            ], $active),
        ];
    }

    /** El profesor es también tutor: el menú «Tutoría» reúne lo que antes tenía el rol aparte. */
    private static function professor(string $active): array
    {
        $items = array_values(array_filter(
            self::coordinatorItems(),
            static fn (array $item) => $item['key'] !== 'indicadores'
        ));

        return [
            self::group(null, null, $items, $active),
            self::group('Tutoría', 'users-round', [
                ['key' => 'mis-asesorias', 'icon' => 'users', 'label' => 'Mis asesorías', 'href' => '/mis-asesorias'],
                ['key' => 'solicitar-tutoria', 'icon' => 'user-plus', 'label' => 'Solicitar tutoría', 'href' => '/tutoria/solicitudes'],
            ], $active, tab: true),
        ];
    }

    private static function coordinatorItems(): array
    {
        return [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel', 'tab' => true],
            ['key' => 'estudiantes', 'icon' => 'users', 'label' => 'Estudiantes', 'href' => '/estudiantes', 'tab' => true],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes', 'tab' => true],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Experiencias', 'href' => '/experiencias'],
            ['key' => 'indicadores', 'icon' => 'bar-chart-3', 'label' => 'Indicadores', 'href' => '/indicadores'],
        ];
    }

    private static function adminItems(): array
    {
        return [
            ['key' => 'usuarios', 'icon' => 'users', 'label' => 'Usuarios y roles', 'href' => '/admin/usuarios'],
            ['key' => 'cargas', 'icon' => 'file-spreadsheet', 'label' => 'Cargas desde Excel', 'href' => '/admin/cargas'],
            ['key' => 'catalogos', 'icon' => 'book-marked', 'label' => 'Catálogos', 'href' => '/admin/catalogos'],
            ['key' => 'bitacora', 'icon' => 'scroll-text', 'label' => 'Bitácora', 'href' => '/admin/bitacora'],
        ];
    }

    /** @param  list<array<string, mixed>>  $items */
    private static function group(?string $label, ?string $icon, array $items, string $active, bool $tab = false): array
    {
        $marked = array_map(
            static fn (array $item) => $item + ['tab' => false, 'active' => $item['key'] === $active],
            $items
        );

        return [
            'label' => $label,
            'icon' => $icon,
            'tab' => $tab,
            'active' => (bool) array_filter($marked, static fn (array $item) => $item['active']),
            'items' => $marked,
        ];
    }
}
