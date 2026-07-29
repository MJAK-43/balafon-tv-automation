<?php

namespace Tests\Feature\Scheduling;

use App\Domains\Channel\Models\Channel;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_crud_flow_detects_conflicts_and_duplicates_day(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/journal.mp4', 'journal');
        File::put($mediaRoot.'/film.mp4', 'film');

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Africa/Douala',
            'description' => 'Main channel',
            'status' => 'active',
        ]);

        $mediaA = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Journal 20H',
            'description' => null,
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/journal.mp4',
            'duration_seconds' => 1800,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $mediaB = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Film Camerounais',
            'description' => null,
            'media_type' => 'MOVIE',
            'file_path' => $mediaRoot.'/film.mp4',
            'duration_seconds' => 5400,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Soiree',
            'description' => 'Prime time',
            'status' => 'READY',
        ]);

        $playlist->items()->createMany([
            ['uuid' => (string) Str::uuid(), 'position' => 1, 'media_asset_id' => $mediaA->id],
            ['uuid' => (string) Str::uuid(), 'position' => 2, 'media_asset_id' => $mediaB->id],
        ]);

        $create = $this->actingAs($user)->postJson('/api/v1/schedules', [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-06-21 20:00:00',
            'ends_at' => null,
            'status' => 'SCHEDULED',
        ]);

        $create->assertCreated()->assertJsonPath('channel_id', $channel->id);
        $uuid = $create->json('uuid');

        $this->actingAs($user)->postJson('/api/v1/schedules', [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-06-21 21:00:00',
            'ends_at' => '2026-06-21 23:00:00',
            'status' => 'DRAFT',
        ])->assertCreated();

        $this->actingAs($user)->getJson('/api/v1/schedule-conflicts?channel_id='.$channel->id.'&date=2026-06-21')
            ->assertOk()
            ->assertJsonFragment(['type' => 'overlap']);

        $this->actingAs($user)->postJson('/api/v1/schedules/duplicate-day', [
            'channel_id' => $channel->id,
            'source_date' => '2026-06-21',
            'target_date' => '2026-06-22',
        ])->assertCreated();

        $this->actingAs($user)->putJson("/api/v1/schedules/{$uuid}", [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-06-21 20:30:00',
            'ends_at' => '2026-06-21 22:00:00',
            'status' => 'SCHEDULED',
        ])->assertOk()->assertJsonPath('status', 'SCHEDULED');

        $this->actingAs($user)->deleteJson("/api/v1/schedules/{$uuid}")
            ->assertNoContent();
    }

    public function test_schedule_creation_uses_operator_timezone_and_stores_utc(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/tz.mp4', 'tz');

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Africa/Douala',
            'description' => 'Main channel',
            'status' => 'active',
        ]);

        $media = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'TZ Clip',
            'description' => null,
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/tz.mp4',
            'duration_seconds' => 55,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Timezone Playlist',
            'description' => 'Timezone test',
            'status' => 'READY',
        ]);

        $playlist->items()->create([
            'uuid' => (string) Str::uuid(),
            'position' => 1,
            'media_asset_id' => $media->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/schedules', [
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-07-03 14:50:00',
            'ends_at' => null,
            'status' => 'SCHEDULED',
            'timezone' => 'Europe/Paris',
        ])->assertCreated();

        $scheduleId = $response->json('id');

        $this->assertDatabaseHas('schedules', [
            'id' => $scheduleId,
            'starts_at' => '2026-07-03 12:50:00',
            'ends_at' => '2026-07-03 12:50:55',
        ]);

        $this->actingAs($user)->getJson('/api/v1/schedules?mode=all&channel_id='.$channel->id.'&date=2026-07-03&timezone=Europe/Paris')
            ->assertOk()
            ->assertJsonPath('0.id', $scheduleId);
    }
}
