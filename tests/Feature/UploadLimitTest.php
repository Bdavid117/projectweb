<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_oversized_post_shows_a_friendly_spanish_413_page(): void
    {
        $this->actingAsRole(Role::Estudiante);

        $response = $this->call('POST', '/solicitudes', [], [], [], ['CONTENT_LENGTH' => 999999999]);

        $response->assertStatus(413);
        $response->assertSee('superan el límite');
        $response->assertSee('Volver');
    }
}
