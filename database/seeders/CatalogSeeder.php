<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\ExperienceType;
use App\Models\RequestType;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['2019-1', '2019-2', '2020-1', '2021-1', '2021-2', '2022-1', '2022-2', '2023-1', '2023-2', '2024-1', '2024-2', '2025-1', '2025-2', '2026-1', '2026-2S'] as $code) {
            AcademicTerm::firstOrCreate(['code' => $code], ['name' => "Periodo {$code}"]);
        }

        foreach ([
            ['nombre' => 'Cancelación de asignatura', 'descripcion' => 'Retiro de una o más asignaturas del periodo'],
            ['nombre' => 'Reingreso al programa', 'descripcion' => 'Reincorporación tras interrupción de estudios'],
            ['nombre' => 'Homologación de créditos', 'descripcion' => 'Reconocimiento de créditos externos'],
            ['nombre' => 'Ampliación de cupo de créditos', 'descripcion' => 'Aumento del cupo máximo del periodo'],
            ['nombre' => 'Otras', 'descripcion' => 'Otras solicitudes al comité asesor'],
        ] as $row) {
            RequestType::firstOrCreate(['nombre' => $row['nombre']], $row);
        }

        foreach ([
            ['nombre' => 'Pasantía', 'categoria' => 'movilidad'],
            ['nombre' => 'Semillero de investigación', 'categoria' => 'investigacion'],
            ['nombre' => 'Monitoría', 'categoria' => 'vinculacion'],
            ['nombre' => 'Ponencia en evento', 'categoria' => 'evento'],
            ['nombre' => 'Práctica empresarial', 'categoria' => 'practica'],
        ] as $row) {
            ExperienceType::firstOrCreate(['nombre' => $row['nombre']], $row);
        }
    }
}
