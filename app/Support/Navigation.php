<?php

namespace App\Support;

class Navigation
{
    public static function coordinator(string $active): array
    {
        return [
            ['label' => 'GENERAL', 'items' => self::mark(self::coordinatorGeneralItems(), $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
        ];
    }

    public static function coordinatorWithTutorExtras(string $active): array
    {
        $groups = self::coordinator($active);
        $groups[] = [
            'label' => null,
            'items' => self::mark([
                ['key' => 'registrar-tutoria', 'icon' => 'message-square', 'label' => 'Registrar tutoría', 'href' => '#'],
                ['key' => 'mis-asesorias', 'icon' => 'clipboard-list', 'label' => 'Mis asesorías', 'href' => '#'],
            ], $active),
        ];

        return $groups;
    }

    public static function studentPortal(string $active): array
    {
        $items = [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'perfil', 'icon' => 'user', 'label' => 'Mi perfil', 'href' => '#'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Mis solicitudes', 'href' => '/solicitudes/nueva'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Mis experiencias', 'href' => '#'],
            ['key' => 'trabajo-grado', 'icon' => 'book-open', 'label' => 'Mi trabajo de grado', 'href' => '#'],
            ['key' => 'historia', 'icon' => 'trending-up', 'label' => 'Mi historia académica', 'href' => '#'],
        ];

        return [
            ['label' => 'GENERAL', 'items' => self::mark($items, $active)],
            ['label' => 'ADMINISTRACIÓN', 'items' => self::mark(self::adminItems(), $active)],
            ['label' => null, 'items' => self::mark([
                ['key' => 'practica', 'icon' => 'briefcase', 'label' => 'Mi práctica', 'href' => '#'],
            ], $active)],
        ];
    }

    private static function coordinatorGeneralItems(): array
    {
        return [
            ['key' => 'panel', 'icon' => 'layout-dashboard', 'label' => 'Panel', 'href' => '/panel'],
            ['key' => 'estudiantes', 'icon' => 'users', 'label' => 'Estudiantes', 'href' => '/estudiantes'],
            ['key' => 'solicitudes', 'icon' => 'file-text', 'label' => 'Solicitudes', 'href' => '/solicitudes/nueva'],
            ['key' => 'experiencias', 'icon' => 'sparkles', 'label' => 'Experiencias', 'href' => '#'],
            ['key' => 'tutorias', 'icon' => 'message-square', 'label' => 'Tutorías', 'href' => '#'],
            ['key' => 'indicadores', 'icon' => 'bar-chart-3', 'label' => 'Indicadores', 'href' => '/indicadores'],
        ];
    }

    private static function adminItems(): array
    {
        return [
            ['key' => 'usuarios', 'icon' => 'shield', 'label' => 'Usuarios y roles', 'href' => '#'],
            ['key' => 'cargas', 'icon' => 'upload', 'label' => 'Cargas desde Excel', 'href' => '#'],
            ['key' => 'catalogos', 'icon' => 'list', 'label' => 'Catálogos', 'href' => '#'],
            ['key' => 'bitacora', 'icon' => 'history', 'label' => 'Bitácora', 'href' => '#'],
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
