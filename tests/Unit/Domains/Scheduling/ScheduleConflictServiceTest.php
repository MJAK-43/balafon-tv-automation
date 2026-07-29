<?php

namespace Tests\Unit\Domains\Scheduling;

use App\Domains\Channel\Models\Channel;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\Playlist;
use App\Domains\Scheduling\Models\Schedule;
use App\Domains\Scheduling\Services\ScheduleConflictService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleConflictServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_overlapping_schedules_for_same_channel(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/clip.mp4', 'clip');

        $channel = Channel::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Balafon TV',
            'code' => 'BTV',
            'timezone' => 'Africa/Douala',
            'description' => null,
            'status' => 'active',
        ]);

        $media = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Clip',
            'description' => null,
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/clip.mp4',
            'duration_seconds' => 3600,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Playlist',
            'description' => null,
            'status' => 'READY',
        ]);
        $playlist->items()->create(['uuid' => (string) Str::uuid(), 'position' => 1, 'media_asset_id' => $media->id]);

        $scheduleA = Schedule::query()->create([
            'uuid' => (string) Str::uuid(),
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-06-21 20:00:00',
            'ends_at' => '2026-06-21 21:00:00',
            'status' => 'SCHEDULED',
        ]);
        $scheduleB = Schedule::query()->create([
            'uuid' => (string) Str::uuid(),
            'channel_id' => $channel->id,
            'playlist_id' => $playlist->id,
            'starts_at' => '2026-06-21 20:30:00',
            'ends_at' => '2026-06-21 21:30:00',
            'status' => 'SCHEDULED',
        ]);

        $service = $this->app->make(ScheduleConflictService::class);
        $conflicts = $service->detect(collect([
            $scheduleA->load('playlist.items.mediaAsset'),
            $scheduleB->load('playlist.items.mediaAsset'),
        ]));

        $this->assertNotEmpty($conflicts);
        $this->assertSame('overlap', $conflicts[0]['type']);
    }
}
