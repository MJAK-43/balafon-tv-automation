<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_diagnostic_can_run(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $response = $this->actingAs($user)->postJson('/api/v1/system/run-diagnostic');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'os',
                'resources',
                'application',
                'vmix',
                'diagnostic_id',
            ]);
    }
}
