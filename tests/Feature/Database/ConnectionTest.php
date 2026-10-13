<?php

namespace Tests\Feature\Database;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConnectionTest extends TestCase
{
    public function test_tests_run_against_the_dedicated_postgres_test_database(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('trayectoria_estudiantil_test', DB::connection()->getDatabaseName());
    }

    public function test_guard_refuses_to_run_destructive_tests_against_a_non_test_database(): void
    {
        config(['database.connections.pgsql.database' => 'trayectoria_estudiantil']);

        $this->expectException(\RuntimeException::class);
        $this->assertSafeTestDatabase();
    }
}
