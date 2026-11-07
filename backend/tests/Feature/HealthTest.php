<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('services.database.status', 'healthy')
            ->assertJsonPath('services.cache.status', 'healthy');
    }

    public function test_api_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy');
    }
}
