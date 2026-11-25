<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_ok_when_database_is_reachable(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => ['database' => 'ok'],
            ])
            ->assertJsonStructure(['status', 'checks', 'app', 'version', 'time']);
    }
}
