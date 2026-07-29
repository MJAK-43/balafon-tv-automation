<?php

namespace Tests\Feature\Branding;

use App\Domains\Branding\Models\BrandingAsset;
use App\Domains\Media\Models\MediaAsset;
use App\Domains\Playlist\Models\Playlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrandingAssetCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_and_announcement_library_flow(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $logo = $this->actingAs($user)->post('/api/v1/branding-assets/logos', [
            'name' => 'Logo Balafon TV',
            'file' => UploadedFile::fake()->create('balafon.png', 64, 'image/png'),
            'logo_position' => 'TOP_RIGHT',
            'logo_scale' => 20,
        ])->assertCreated()
            ->assertJsonPath('asset_type', 'LOGO')
            ->assertJsonPath('name', 'Logo Balafon TV')
            ->assertJsonPath('logo_position', 'TOP_RIGHT')
            ->assertJsonPath('logo_scale', 20)
            ->json();

        $text = $this->actingAs($user)->post('/api/v1/branding-assets/announcements', [
            'name' => 'Actualites du soir',
            'asset_type' => 'ANNOUNCEMENT_TEXT',
            'text_content' => 'Retrouvez le journal complet a 20 heures.',
            'loop_enabled' => true,
            'text_color' => '#FFD700',
            'text_background_color' => '#102030',
            'text_font' => 'TREBUCHET',
            'ticker_speed' => 180,
        ])->assertCreated()
            ->assertJsonPath('asset_type', 'ANNOUNCEMENT_TEXT')
            ->assertJsonPath('loop_enabled', true)
            ->assertJsonPath('text_color', '#FFD700')
            ->assertJsonPath('text_background_color', '#102030')
            ->assertJsonPath('text_font', 'TREBUCHET')
            ->assertJsonPath('ticker_speed', 180)
            ->json();

        $video = $this->actingAs($user)->post('/api/v1/branding-assets/announcements', [
            'name' => 'Promotion sport',
            'asset_type' => 'ANNOUNCEMENT_VIDEO',
            'file' => UploadedFile::fake()->create('promotion.mp4', 128, 'video/mp4'),
            'loop_enabled' => false,
        ])->assertCreated()
            ->assertJsonPath('asset_type', 'ANNOUNCEMENT_VIDEO')
            ->assertJsonPath('loop_enabled', false)
            ->json();

        $this->actingAs($user)->getJson('/api/v1/branding-assets')
            ->assertOk()
            ->assertJsonCount(3);

        $this->actingAs($user)->get("/api/v1/branding-assets/{$logo['uuid']}/preview")
            ->assertOk();

        $this->actingAs($user)->putJson("/api/v1/branding-assets/{$text['uuid']}", [
            'name' => 'Actualites mises a jour',
            'text_content' => 'Nouveau message antenne.',
            'loop_enabled' => true,
            'status' => 'ACTIVE',
            'text_color' => '#00FFAA',
            'text_background_color' => '#001122',
            'text_font' => 'GEORGIA',
            'ticker_speed' => 80,
        ])->assertOk()
            ->assertJsonPath('text_content', 'Nouveau message antenne.')
            ->assertJsonPath('text_color', '#00FFAA')
            ->assertJsonPath('text_background_color', '#001122')
            ->assertJsonPath('text_font', 'GEORGIA')
            ->assertJsonPath('ticker_speed', 80);

        $this->actingAs($user)->deleteJson("/api/v1/branding-assets/{$video['uuid']}")
            ->assertNoContent();

        $this->assertDatabaseMissing('branding_assets', ['uuid' => $video['uuid']]);
    }

    public function test_used_graphic_cannot_be_deleted(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/program.mp4', 'program');

        $media = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Programme',
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/program.mp4',
            'duration_seconds' => 30,
            'status' => 'READY',
        ]);
        $logo = BrandingAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Logo utilise',
            'asset_type' => 'LOGO',
            'file_path' => $mediaRoot.'/program.mp4',
            'loop_enabled' => true,
            'status' => 'ACTIVE',
        ]);
        $playlist = Playlist::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Playlist habillee',
            'status' => 'READY',
        ]);
        $playlist->items()->create([
            'uuid' => (string) Str::uuid(),
            'position' => 1,
            'media_asset_id' => $media->id,
            'logo_id' => $logo->id,
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/branding-assets/{$logo->uuid}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('asset');

        $this->assertDatabaseHas('branding_assets', ['id' => $logo->id]);
    }
}
