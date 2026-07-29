<?php

namespace Tests\Feature\Playlist;

use App\Domains\Branding\Models\BrandingAsset;
use App\Domains\Media\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_playlist_crud_flow_with_items(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/journal.mp4', 'demo-video');
        File::put($mediaRoot.'/ad.mp4', 'demo-ad');

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
            'title' => 'Orange Ad',
            'description' => null,
            'media_type' => 'ADVERTISEMENT',
            'file_path' => $mediaRoot.'/ad.mp4',
            'duration_seconds' => 120,
            'status' => 'READY',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $logo = BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Logo Balafon',
            'asset_type' => 'LOGO',
            'file_path' => $mediaRoot.'/journal.mp4',
            'loop_enabled' => true,
            'status' => 'ACTIVE',
        ]);
        $announcement = BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Flash info',
            'asset_type' => 'ANNOUNCEMENT_TEXT',
            'text_content' => 'Le journal commence.',
            'loop_enabled' => true,
            'status' => 'ACTIVE',
        ]);

        $create = $this->actingAs($user)->postJson('/api/v1/playlists', [
            'title' => 'Morning Rotation',
            'description' => 'Broadcast foundation playlist',
            'status' => 'DRAFT',
            'items' => [
                [
                    'position' => 1,
                    'media_asset_id' => $mediaA->id,
                    'logo_id' => $logo->id,
                    'announcement_id' => $announcement->id,
                ],
                ['position' => 2, 'media_asset_id' => $mediaB->id],
            ],
        ]);

        $create->assertCreated()->assertJsonCount(2, 'items');
        $create->assertJsonPath('items.0.logo.name', 'Logo Balafon');
        $create->assertJsonPath('items.0.announcement.name', 'Flash info');
        $uuid = $create->json('uuid');

        $this->actingAs($user)->getJson("/api/v1/playlists/{$uuid}")
            ->assertOk()
            ->assertJsonPath('items.0.position', 1);

        $this->actingAs($user)->putJson("/api/v1/playlists/{$uuid}", [
            'title' => 'Morning Rotation Updated',
            'description' => 'Updated',
            'status' => 'READY',
            'items' => [
                ['position' => 1, 'media_asset_id' => $mediaB->id],
            ],
        ])->assertOk()->assertJsonCount(1, 'items');

        $this->actingAs($user)->deleteJson("/api/v1/playlists/{$uuid}")
            ->assertNoContent();
    }
}
