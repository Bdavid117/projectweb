<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Support\Audit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_logs_only_changed_fields_and_skips_no_op_updates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->signInSeeded(Role::Estudiante);
        $student = $user->student;
        $request = Request::create('/perfil', 'PUT');
        $request->setUserResolver(fn () => $user);

        Audit::update($request, $student, ['telefono' => '3001112233', 'ciudad' => 'Manizales'], 'perfil.actualizado');
        Audit::update($request, $student, ['telefono' => '3001112233'], 'perfil.actualizado');

        $this->assertSame(1, AuditLog::count());
        $log = AuditLog::firstOrFail();
        $this->assertSame($user->id, $log->user_id);
        $this->assertEquals(['telefono' => '3001112233', 'ciudad' => 'Manizales'], $log->valores_nuevos);
        $this->assertEquals(['telefono' => null, 'ciudad' => null], $log->valores_anteriores);
    }
}
