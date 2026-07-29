<?php

namespace Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_asset_crud_flow_with_browser_and_preview(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/journal_20h.mp4', 'demo-video-content');

        $this->actingAs($user)->getJson('/api/v1/media-assets/browser')
            ->assertOk()
            ->assertJsonStructure(['roots']);

        $create = $this->actingAs($user)->postJson('/api/v1/media-assets', [
            'title' => 'Journal 20H',
            'description' => 'Evening news bulletin',
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/journal_20h.mp4',
            'duration_seconds' => 1800,
            'status' => 'READY',
        ]);

        $create->assertCreated()->assertJsonPath('title', 'Journal 20H');
        $uuid = $create->json('uuid');
        $this->assertStringStartsWith('media://0/', $create->json('storage_reference'));
        $this->assertStringEndsWith(
            str_replace('/', '\\', 'media-demo/journal_20h.mp4'),
            str_replace('/', '\\', $create->json('file_path'))
        );

        $this->actingAs($user)->getJson('/api/v1/media-assets?mode=all&search=Journal')
            ->assertOk()
            ->assertJsonCount(1);

        $this->actingAs($user)->get("/api/v1/media-assets/{$uuid}/preview")
            ->assertOk();

        $this->actingAs($user)->putJson("/api/v1/media-assets/{$uuid}", [
            'title' => 'Journal 20H HD',
            'description' => 'Updated bulletin',
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/journal_20h.mp4',
            'duration_seconds' => 1860,
            'status' => 'READY',
        ])->assertOk()->assertJsonPath('title', 'Journal 20H HD');

        $this->actingAs($user)->deleteJson("/api/v1/media-assets/{$uuid}")
            ->assertNoContent();
    }

    public function test_media_folder_import_preview_and_batch_import(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $mediaRoot = storage_path('app/media-demo/folder-import');
        File::ensureDirectoryExists($mediaRoot.'/sub');
        File::put($mediaRoot.'/journal_20h.mp4', 'demo-video-content');
        File::put($mediaRoot.'/jingle.wav', 'demo-audio-content');
        File::put($mediaRoot.'/sub/teaser.mp4', 'nested-video-content');
        File::put($mediaRoot.'/notes.txt', 'unsupported');

        $preview = $this->actingAs($user)->postJson('/api/v1/media-assets/import-folder/preview', [
            'path' => $mediaRoot,
            'recursive' => true,
        ]);

        $preview->assertOk()
            ->assertJsonPath('recursive', true)
            ->assertJsonCount(3, 'files');

        $import = $this->actingAs($user)->postJson('/api/v1/media-assets/import-folder', [
            'path' => $mediaRoot,
            'recursive' => true,
            'ignore_duplicates' => true,
        ]);

        $import->assertCreated()
            ->assertJsonPath('imported_count', 3)
            ->assertJsonPath('skipped_count', 0);

        $createdStorageReference = $import->json('created.0.storage_reference');
        $this->assertIsString($createdStorageReference);
        $this->assertStringStartsWith('media://0/', $createdStorageReference);

        $duplicateImport = $this->actingAs($user)->postJson('/api/v1/media-assets/import-folder', [
            'path' => $mediaRoot,
            'recursive' => true,
            'ignore_duplicates' => true,
        ]);

        $duplicateImport->assertCreated()
            ->assertJsonPath('imported_count', 0)
            ->assertJsonPath('skipped_count', 3);

        $this->actingAs($user)->getJson('/api/v1/media-assets?mode=all')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_media_can_be_uploaded_from_local_file_and_folder_inputs(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $single = UploadedFile::fake()->create('promo.mp4', 128, 'video/mp4');

        $this->actingAs($user)->post('/api/v1/media-assets/upload-single', [
            'file' => $single,
            'title' => 'Promo du soir',
            'description' => 'Uploaded from the native file picker',
            'media_type' => 'ADVERTISEMENT',
            'duration_seconds' => 7,
            'status' => 'READY',
        ])->assertCreated()
            ->assertJsonPath('imported_count', 1)
            ->assertJsonPath('created.0.title', 'Promo du soir')
            ->assertJsonPath('created.0.media_type', 'ADVERTISEMENT')
            ->assertJsonPath('created.0.duration_seconds', 7);

        $folderFiles = [
            UploadedFile::fake()->create('journal.mp4', 256, 'video/mp4'),
            UploadedFile::fake()->create('jingle.wav', 64, 'audio/wav'),
        ];

        $this->actingAs($user)->post('/api/v1/media-assets/upload-folder', [
            'files' => $folderFiles,
            'durations' => [30, 12],
        ])->assertCreated()
            ->assertJsonPath('imported_count', 2)
            ->assertJsonPath('created.0.duration_seconds', 30)
            ->assertJsonPath('created.1.duration_seconds', 12);

        $listing = $this->actingAs($user)->getJson('/api/v1/media-assets?mode=all')
            ->assertOk()
            ->assertJsonCount(3);

        $this->assertStringStartsWith('media://0/', $listing->json('0.storage_reference'));
    }
}
