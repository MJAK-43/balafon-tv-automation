<?php

use App\Jobs\CheckVmixHealthJob;
use App\Jobs\MonitorActiveBroadcastsJob;
use App\Jobs\ProcessScheduledBroadcastsJob;
use App\Jobs\RunSystemDiagnosticJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckVmixHealthJob)
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::job(new RunSystemDiagnosticJob)
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::job(new ProcessScheduledBroadcastsJob)
    ->everySecond()
    ->withoutOverlapping(1);

Schedule::job(new MonitorActiveBroadcastsJob)
    ->everySecond()
    ->withoutOverlapping(1);
