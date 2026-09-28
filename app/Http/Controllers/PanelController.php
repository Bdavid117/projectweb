<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(): View
    {
        return view('panel.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('panel'),
            'indicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => '312', 'detail' => 'Matriculados en 2026-2S'],
                ['label' => 'PAPA PROMEDIO', 'value' => '3.9', 'detail' => '+0.1 frente al periodo anterior'],
                ['label' => 'AVANCE MEDIO', 'value' => '58%', 'detail' => 'Créditos cursados del plan'],
                ['label' => 'ALERTAS ABIERTAS', 'value' => '7', 'detail' => 'Requieren atención temprana', 'valueClass' => 'text-[var(--color-danger)]'],
            ],
            'attentionRows' => [
                ['name' => 'Pérez Rojas, Juan', 'issue' => 'Caída del PAPA frente al periodo anterior', 'date' => '12/09/2026', 'status' => 'Sin atender', 'tone' => 'danger'],
                ['name' => 'Gómez Ruiz, Laura', 'issue' => 'Tercera solicitud del mismo tipo en el periodo', 'date' => '10/09/2026', 'status' => 'En revisión', 'tone' => 'warning'],
                ['name' => 'Ruiz Salazar, Andrés', 'issue' => 'Matrícula sin avance en créditos', 'date' => '05/09/2026', 'status' => 'En revisión', 'tone' => 'warning'],
                ['name' => 'Martínez Cruz, Diana', 'issue' => 'Sin registro de tutoría en dos periodos', 'date' => '01/09/2026', 'status' => 'Atendida', 'tone' => 'success'],
            ],
            'cohortChart' => [
                ['label' => '2019', 'value' => '92%', 'height' => 152],
                ['label' => '2020', 'value' => '78%', 'height' => 129],
                ['label' => '2021', 'value' => '61%', 'height' => 101],
                ['label' => '2022', 'value' => '44%', 'height' => 73],
                ['label' => '2023', 'value' => '29%', 'height' => 48],
                ['label' => '2024', 'value' => '16%', 'height' => 26],
                ['label' => '2025', 'value' => '8%', 'height' => 13],
                ['label' => '2026', 'value' => '3%', 'height' => 5],
            ],
            'recentActivity' => [
                ['icon' => 'upload', 'description' => 'Carga de 42 registros Saber Pro', 'meta' => 'Hace 2 h · Ana Restrepo'],
                ['icon' => 'message-square', 'description' => 'Tutoría registrada a Gómez Ruiz, L.', 'meta' => 'Hace 5 h · Prof. Carlos P.'],
                ['icon' => 'file-text', 'description' => 'Solicitud de reingreso radicada', 'meta' => 'Ayer · Ruiz Salazar, A.'],
                ['icon' => 'circle-check', 'description' => 'Práctica aprobada en Softlogic S.A.S.', 'meta' => 'Ayer · Coordinación'],
                ['icon' => 'sparkles', 'description' => 'Experiencia validada: Semillero SIGMA', 'meta' => '18/09 · Coordinación'],
            ],
            'quickLinks' => [
                ['icon' => 'upload', 'label' => 'Cargar archivo de Excel'],
                ['icon' => 'file-plus', 'label' => 'Registrar decisión del comité'],
                ['icon' => 'user-plus', 'label' => 'Crear usuario y asignar rol'],
            ],
        ]);
    }
}
