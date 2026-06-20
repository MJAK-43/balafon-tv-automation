<?php

namespace Tests\Feature\Vmix;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VmixEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_vmix_status_endpoint_returns_mock_status(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/v1/vmix/status');

        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'version' => '29.x-mock',
                'inputs_count' => 2,
            ]);
    }
}
