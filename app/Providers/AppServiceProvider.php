<?php

namespace App\Providers;

use App\Domains\Automation\Repositories\Contracts\BroadcastRunRepositoryInterface;
use App\Domains\Automation\Repositories\EloquentBroadcastRunRepository;
use App\Domains\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use App\Domains\Channel\Repositories\EloquentChannelRepository;
use App\Domains\Media\Repositories\Contracts\MediaAssetRepositoryInterface;
use App\Domains\Media\Repositories\EloquentMediaAssetRepository;
use App\Domains\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use App\Domains\Playlist\Repositories\EloquentPlaylistRepository;
use App\Domains\Scheduling\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Domains\Scheduling\Repositories\EloquentScheduleRepository;
use App\Domains\System\Repositories\Contracts\LicenseRepositoryInterface;
use App\Domains\System\Repositories\EloquentLicenseRepository;
use App\Domains\Taxonomy\Repositories\Contracts\MediaCategoryRepositoryInterface;
use App\Domains\Taxonomy\Repositories\Contracts\MediaTagRepositoryInterface;
use App\Domains\Taxonomy\Repositories\EloquentMediaCategoryRepository;
use App\Domains\Taxonomy\Repositories\EloquentMediaTagRepository;
use App\Domains\Vmix\Providers\Contracts\VmixProviderInterface;
use App\Domains\Vmix\Providers\MockVmixProvider;
use App\Domains\Vmix\Providers\RealVmixProvider;
use App\Domains\Vmix\Repositories\Contracts\VmixConnectionRepositoryInterface;
use App\Domains\Vmix\Repositories\EloquentVmixConnectionRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BroadcastRunRepositoryInterface::class, EloquentBroadcastRunRepository::class);
        $this->app->bind(ChannelRepositoryInterface::class, EloquentChannelRepository::class);
        $this->app->bind(MediaAssetRepositoryInterface::class, EloquentMediaAssetRepository::class);
        $this->app->bind(PlaylistRepositoryInterface::class, EloquentPlaylistRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, EloquentScheduleRepository::class);
        $this->app->bind(MediaCategoryRepositoryInterface::class, EloquentMediaCategoryRepository::class);
        $this->app->bind(MediaTagRepositoryInterface::class, EloquentMediaTagRepository::class);
        $this->app->bind(VmixConnectionRepositoryInterface::class, EloquentVmixConnectionRepository::class);
        $this->app->bind(LicenseRepositoryInterface::class, EloquentLicenseRepository::class);

        $this->app->bind(VmixProviderInterface::class, function ($app) {
            return config('balafon.vmix.driver') === 'mock'
                ? $app->make(MockVmixProvider::class)
                : $app->make(RealVmixProvider::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
