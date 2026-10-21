<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Company;
use App\Models\ExperienceType;
use App\Models\RequestType;
use App\Models\School;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(2019, 2026) as $year) {
            AcademicTerm::updateOrCreate(['codigo' => "$year-1"], ['fecha_inicio' => "$year-02-01", 'fecha_fin' => "$year-06-15"]);
            AcademicTerm::updateOrCreate(['codigo' => "$year-2"], ['fecha_inicio' => "$year-08-01", 'fecha_fin' => "$year-12-15"]);
        }

        foreach ([
            'Cancelación de asignatura', 'Reingreso al programa', 'Homologación de créditos',
            'Ampliación de cupo de créditos', 'Otras',
        ] as $nombre) {
            RequestType::updateOrCreate(['nombre' => $nombre]);
        }

        foreach ([
            ['Pasantía', 'Pasantía'],
            ['Semillero o grupo de investigación', 'Investigación'],
            ['Proyecto de investigación o extensión', 'Investigación'],
            ['Grupo deportivo o cultural', 'Deporte y cultura'],
            ['Asistencia o ponencia en evento', 'Eventos y publicaciones'],
            ['Publicación o certificación', 'Eventos y publicaciones'],
            ['Vinculación (auxiliar, becario o monitor)', 'Vinculación'],
            ['Programa SUMA', 'Programa'],
        ] as [$nombre, $categoria]) {
            ExperienceType::updateOrCreate(['nombre' => $nombre], ['categoria' => $categoria]);
        }

        School::updateOrCreate(['nombre' => 'Colegio Nacional de Manizales'], ['caracter' => 'publico', 'municipio' => 'Manizales']);
        School::updateOrCreate(['nombre' => 'Colegio Los Ángeles'], ['caracter' => 'privado', 'municipio' => 'Manizales']);

        Company::updateOrCreate(['nombre' => 'Softlogic S.A.S.'], ['nit' => '900123456-1', 'sector' => 'Tecnología', 'tamano' => 'Mediana', 'ciudad' => 'Manizales']);
    }
}
