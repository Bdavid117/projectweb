<?php

namespace Tests\Feature\Database;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Experience;
use App\Models\ExperienceType;
use App\Models\ImportBatch;
use App\Models\Intervention;
use App\Models\Tutorship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class SupportSchemaTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    public function test_tables_have_the_columns_from_the_analysis_document(): void
    {
        $expected = [
            'experiences' => ['student_id', 'experience_type_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'alcance', 'institucion', 'convenio', 'docente_id', 'rol', 'validada', 'validada_por'],
            'tutorships' => ['student_id', 'tutor_id', 'fecha', 'motivo', 'observaciones', 'compromisos', 'deleted_at'],
            'interventions' => ['student_id', 'origen', 'descripcion', 'responsable_id', 'fecha', 'resultado', 'estado', 'deleted_at'],
            'alerts' => ['student_id', 'tipo', 'detalle', 'fecha_deteccion', 'atendida'],
            'import_batches' => ['tipo', 'archivo', 'user_id', 'filas_ok', 'filas_error', 'log', 'created_at'],
            'audit_logs' => ['user_id', 'auditable_type', 'auditable_id', 'accion', 'valores_anteriores', 'valores_nuevos', 'ip', 'created_at'],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), "Faltan columnas en $table");
        }
    }

    public function test_experience_is_pending_validation_by_default(): void
    {
        $student = $this->makeStudent();
        $experience = Experience::create([
            'student_id' => $student->id,
            'experience_type_id' => ExperienceType::create(['nombre' => 'Semillero', 'categoria' => 'Investigación'])->id,
            'nombre' => 'Semillero SIGMA', 'fecha_inicio' => '2022-02-01',
        ]);

        $this->assertFalse($experience->fresh()->validada);
        $this->assertSame(1, $student->experiences()->count());
    }

    public function test_soft_deleted_tutorships_and_interventions_are_hidden_but_kept(): void
    {
        $student = $this->makeStudent();
        $tutor = User::factory()->create();
        $tutorship = Tutorship::create(['student_id' => $student->id, 'tutor_id' => $tutor->id, 'fecha' => '2026-09-12', 'motivo' => 'Seguimiento']);
        $intervention = Intervention::create(['student_id' => $student->id, 'origen' => 'tutoria', 'descripcion' => 'Plan de apoyo', 'responsable_id' => $tutor->id, 'fecha' => '2026-09-13']);

        $tutorship->delete();
        $intervention->delete();

        $this->assertSame(0, $student->tutorships()->count());
        $this->assertSame(0, $student->interventions()->count());
        $this->assertSame(1, Tutorship::withTrashed()->count());
        $this->assertSame(1, Intervention::withTrashed()->count());
    }

    public function test_audit_log_is_polymorphic_and_stores_json(): void
    {
        $student = $this->makeStudent();
        $log = AuditLog::create([
            'user_id' => User::factory()->create()->id,
            'auditable_type' => $student->getMorphClass(), 'auditable_id' => $student->id,
            'accion' => 'update', 'valores_anteriores' => ['estado' => 'activo'], 'valores_nuevos' => ['estado' => 'en_riesgo'],
            'ip' => '127.0.0.1',
        ]);

        $log = $log->fresh();
        $this->assertTrue($log->auditable->is($student));
        $this->assertSame(['estado' => 'en_riesgo'], $log->valores_nuevos);
        $this->assertNotNull($log->created_at);
    }

    public function test_alerts_and_import_batches_can_be_created(): void
    {
        $student = $this->makeStudent();
        Alert::create(['student_id' => $student->id, 'tipo' => 'caida_papa', 'detalle' => 'Caída del PAPA', 'fecha_deteccion' => '2026-09-12']);
        $batch = ImportBatch::create(['tipo' => 'estudiantes', 'archivo' => 'estudiantes.xlsx', 'user_id' => User::factory()->create()->id, 'filas_ok' => 40, 'filas_error' => 2]);

        $this->assertFalse($student->alerts()->first()->atendida);
        $this->assertNotNull($batch->fresh()->created_at);
    }
}
