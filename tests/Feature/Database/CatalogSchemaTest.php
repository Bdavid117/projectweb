<?php

namespace Tests\Feature\Database;

use App\Models\AcademicTerm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'academic_terms' => ['codigo', 'fecha_inicio', 'fecha_fin'],
            'schools' => ['nombre', 'caracter', 'municipio'],
            'companies' => ['nombre', 'nit', 'sector', 'tamano', 'ciudad'],
            'request_types' => ['nombre', 'descripcion'],
            'experience_types' => ['nombre', 'categoria'],
            'committee_minutes' => ['numero_acta', 'fecha', 'instancia', 'url'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_term_codigo_is_unique(): void
    {
        $attrs = ['codigo' => '2026-2', 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-12-12'];
        AcademicTerm::create($attrs);

        $this->expectException(QueryException::class);
        AcademicTerm::create($attrs);
    }
}
