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
}
