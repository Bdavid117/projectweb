<?php

namespace Tests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function actingAsRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->make($attributes + ['id' => 1, 'role' => $role]);
        $this->actingAs($user);

        return $user;
    }
}
