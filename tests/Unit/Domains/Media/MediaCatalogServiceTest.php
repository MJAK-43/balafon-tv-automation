<?php

namespace Tests\Unit\Domains\Media;

use App\Domains\Media\Services\MediaCatalogService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_catalog_service_creates_a_media_reference(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();
        $mediaRoot = storage_path('app/media-demo');
        File::ensureDirectoryExists($mediaRoot);
        File::put($mediaRoot.'/demo.mp4', 'demo');

        $service = $this->app->make(MediaCatalogService::class);

        $asset = $service->create([
            'title' => 'Demo Clip',
            'description' => 'Unit test media',
            'media_type' => 'PROGRAM',
            'file_path' => $mediaRoot.'/demo.mp4',
            'duration_seconds' => 60,
            'status' => 'READY',
        ], $user->id);

        $this->assertSame('Demo Clip', $asset->title);
        $this->assertSame(realpath($mediaRoot.'/demo.mp4'), $asset->file_path);
    }
}
