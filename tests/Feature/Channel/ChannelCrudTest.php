<?php

namespace Tests\Feature\Channel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_channel_is_created_when_channel_list_is_empty(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $this->actingAs($user)->getJson('/api/v1/channels')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Balafon TV')
            ->assertJsonPath('data.0.code', 'BTV')
            ->assertJsonPath('data.0.status', 'active');

        $this->assertDatabaseHas('channels', [
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => config('app.timezone'),
            'status' => 'active',
        ]);
    }

    public function test_channel_crud_flow(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $create = $this->actingAs($user)->postJson('/api/v1/channels', [
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Africa/Douala',
            'description' => 'Primary channel',
            'status' => 'active',
        ]);

        $create->assertCreated();
        $uuid = $create->json('uuid');

        $this->actingAs($user)->getJson('/api/v1/channels')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'BTV');

        $this->actingAs($user)->putJson("/api/v1/channels/{$uuid}", [
            'name' => 'Balafon TV HD',
            'code' => 'BTVHD',
            'timezone' => 'Africa/Douala',
            'description' => 'Updated channel',
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('code', 'BTVHD');

        $this->actingAs($user)->deleteJson("/api/v1/channels/{$uuid}")
            ->assertNoContent();
    }
}
