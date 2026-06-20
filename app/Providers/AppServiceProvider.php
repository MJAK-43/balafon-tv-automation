<?php

namespace App\Providers;

use App\Domains\System\Repositories\Contracts\LicenseRepositoryInterface;
use App\Domains\System\Repositories\EloquentLicenseRepository;
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
