<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Mock roster shown in the listing. Row order matches the design's table exactly.
     */
    private const STUDENTS = [
        ['code' => '2019087', 'name' => 'Ruiz Salazar, Andrés', 'cohort' => '2019-2', 'papa' => '3.2', 'advance' => 88, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2020123', 'name' => 'Pérez Rojas, Juan', 'cohort' => '2020-1', 'papa' => '4.1', 'advance' => 72, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2021045', 'name' => 'Gómez Ruiz, Laura', 'cohort' => '2021-1', 'papa' => '3.7', 'advance' => 55, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2021098', 'name' => 'Cardona Vélez, Mateo', 'cohort' => '2021-2', 'papa' => '2.9', 'advance' => 41, 'status' => 'En riesgo', 'tone' => 'danger'],
        ['code' => '2022011', 'name' => 'Martínez Cruz, Diana', 'cohort' => '2022-1', 'papa' => '4.4', 'advance' => 31, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2022076', 'name' => 'Ospina Lara, Sofía', 'cohort' => '2022-2', 'papa' => '3.5', 'advance' => 27, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2023019', 'name' => 'Herrera Niño, Camilo', 'cohort' => '2023-1', 'papa' => '3.8', 'advance' => 19, 'status' => 'Activo', 'tone' => 'success'],
        ['code' => '2023044', 'name' => 'Valencia Toro, Ana', 'cohort' => '2023-2', 'papa' => '4.0', 'advance' => 15, 'status' => 'Intercambio', 'tone' => 'info'],
    ];

    public function index(): View
    {
        return view('students.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('estudiantes'),
            'students' => self::STUDENTS,
        ]);
    }

    public function show(string $id): View
    {
        return view('students.show', [
            'user' => ['initials' => 'CP', 'name' => 'Carlos Pineda', 'role' => 'Tutor'],
            'navGroups' => Navigation::coordinatorWithTutorExtras('estudiantes'),
            'student' => [
                'initials' => 'JP',
                'name' => 'Pérez Rojas, Juan Sebastián',
                'code' => 'Código 2020123',
                'cohort' => 'Cohorte 2020-1',
                'enrollment' => 'Séptima matrícula',
                'tutor' => 'Tutor asignado: Carlos Pineda',
            ],
            'tabs' => ['General', 'Académica', 'Experiencias', 'Trabajo de grado', 'Práctica', 'Solicitudes', 'Tutorías'],
            'papaChart' => [
                ['period' => '2020-1', 'value' => '3.4', 'height' => 76],
                ['period' => '2021-1', 'value' => '3.6', 'height' => 94],
                ['period' => '2021-2', 'value' => '3.5', 'height' => 85],
                ['period' => '2022-1', 'value' => '3.9', 'height' => 119],
                ['period' => '2022-2', 'value' => '4.2', 'height' => 145],
                ['period' => '2023-1', 'value' => '4.0', 'height' => 128],
                ['period' => '2026-2', 'value' => '4.1', 'height' => 136],
            ],
            'timeline' => [
                ['icon' => 'sparkles', 'description' => 'Semillero de investigación SIGMA', 'period' => '2022-1', 'tag' => 'Investigación', 'tone' => 'primary'],
                ['icon' => 'book-open', 'description' => 'Monitoría de Bases de Datos', 'period' => '2023-2', 'tag' => 'Vinculación', 'tone' => 'accent'],
                ['icon' => 'plane', 'description' => 'Pasantía Sígueme · Univ. de Antioquia', 'period' => '2024-1', 'tag' => 'Pasantía', 'tone' => 'restricted'],
                ['icon' => 'mic', 'description' => 'Ponencia en Congreso Colombiano de Computación', 'period' => '2025-2', 'tag' => 'Evento', 'tone' => 'warning'],
                ['icon' => 'briefcase', 'description' => 'Práctica empresarial · Softlogic S.A.S.', 'period' => '2026-2', 'tag' => 'Práctica', 'tone' => 'success'],
            ],
            'processes' => [
                ['title' => 'Trabajo de grado · modalidad pasantía', 'meta' => 'Director: Prof. María Londoño · 2026-2S', 'status' => 'En desarrollo', 'tone' => 'warning'],
                ['title' => 'Práctica empresarial · Softlogic S.A.S.', 'meta' => 'Asesor: Prof. Carlos Pineda · jul–dic 2026', 'status' => 'En curso', 'tone' => 'info'],
            ],
            'progress' => [
                'percent' => 72,
                'details' => [
                    ['label' => 'Créditos cursados', 'value' => '116 de 160'],
                    ['label' => 'Cupo del periodo', 'value' => '21 créditos'],
                    ['label' => 'PAPA actual', 'value' => '4.1'],
                    ['label' => 'Promedio anterior', 'value' => '4.0'],
                ],
            ],
            'tutorials' => [
                ['date' => '12/09/2026', 'reason' => 'Seguimiento a carga académica', 'note' => 'Acordó reducir a 15 créditos el próximo periodo.'],
                ['date' => '03/05/2026', 'reason' => 'Orientación sobre modalidad de grado', 'note' => 'Interesado en pasantía; se remitió a Coordinación.'],
                ['date' => '18/11/2025', 'reason' => 'Revisión de avance', 'note' => 'Sin novedades; continúa según plan.'],
            ],
        ]);
    }
}
