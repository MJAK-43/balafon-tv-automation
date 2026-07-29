<?php

namespace Tests\Unit\Domains\Playlist;

use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Services\PlaylistService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_playlist_service_creates_playlist_with_items(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/foundation.mp4', 'foundation');

        $mediaAsset = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Foundation Clip',
            'description' => 'Unit test',
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/foundation.mp4',
            'duration_seconds' => 90,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = $this->app->make(PlaylistService::class);

        $playlist = $service->create([
            'title' => 'Foundation List',
            'description' => 'Unit test',
            'status' => 'DRAFT',
            'items' => [
                ['position' => 1, 'media_asset_id' => $mediaAsset->id],
            ],
        ]);

        $this->assertSame('Foundation List', $playlist->title);
        $this->assertCount(1, $playlist->items);
    }
}
