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
}
