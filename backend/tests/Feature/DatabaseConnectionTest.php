<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_is_working()
    {
        // run a simple query
        $result = DB::select('SELECT 1 as value');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->value);
    }

    public function test_health_endpoint_reports_connected()
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'database' => 'connected',
                 ]);
    }
}
