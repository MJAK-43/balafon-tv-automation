<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_log_in_and_receive_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@balafon.local',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email'],
            ]);
    }
}
