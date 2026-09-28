<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\View\View;

class IndicatorController extends Controller
{
    public function index(): View
    {
        return view('indicators.index', [
            'user' => ['initials' => 'AR', 'name' => 'Ana Restrepo', 'role' => 'Coordinación'],
            'navGroups' => Navigation::coordinator('indicadores'),
            'keyIndicators' => [
                ['label' => 'ESTUDIANTES ACTIVOS', 'value' => '312', 'detail' => 'Matriculados en 2026-2S'],
                ['label' => 'PRÁCTICAS EN CURSO', 'value' => '28', 'detail' => '9% de los activos'],
                ['label' => 'TRABAJOS DE GRADO', 'value' => '41', 'detail' => '13% de los activos'],
                ['label' => 'SOLICITUDES DEL PERIODO', 'value' => '96', 'detail' => '31 aún sin decisión', 'valueClass' => 'text-[var(--color-warning)]'],
            ],
            'papaHistogram' => [
                ['range' => '< 3.0', 'value' => '22', 'height' => 33],
                ['range' => '3.0–3.3', 'value' => '41', 'height' => 62],
                ['range' => '3.3–3.6', 'value' => '68', 'height' => 102],
                ['range' => '3.6–4.0', 'value' => '94', 'height' => 141],
                ['range' => '4.0–4.3', 'value' => '59', 'height' => 89],
                ['range' => '> 4.3', 'value' => '28', 'height' => 42],
            ],
            'participation' => [
                ['label' => 'Semilleros y grupos de investigación', 'value' => '54 · 17%', 'percent' => 17],
                ['label' => 'Monitorías y vinculaciones', 'value' => '38 · 12%', 'percent' => 12],
                ['label' => 'Deporte y cultura', 'value' => '47 · 15%', 'percent' => 15],
                ['label' => 'Pasantías e intercambios', 'value' => '11 · 4%', 'percent' => 4],
                ['label' => 'Eventos y publicaciones', 'value' => '23 · 7%', 'percent' => 7],
            ],
            'requestsByType' => [
                ['type' => 'Cancelación de asignatura', 'filed' => '38', 'review' => '9', 'decided' => '29', 'avgTime' => '6 días'],
                ['type' => 'Reingreso al programa', 'filed' => '21', 'review' => '7', 'decided' => '14', 'avgTime' => '11 días'],
                ['type' => 'Homologación de créditos', 'filed' => '17', 'review' => '8', 'decided' => '9', 'avgTime' => '14 días'],
                ['type' => 'Ampliación de cupo de créditos', 'filed' => '12', 'review' => '4', 'decided' => '8', 'avgTime' => '5 días'],
                ['type' => 'Otras', 'filed' => '8', 'review' => '3', 'decided' => '5', 'avgTime' => '9 días'],
            ],
        ]);
    }
}
