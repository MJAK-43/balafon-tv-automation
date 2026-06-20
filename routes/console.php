<?php

use App\Jobs\CheckVmixHealthJob;
use App\Jobs\RunSystemDiagnosticJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckVmixHealthJob())
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::job(new RunSystemDiagnosticJob())
    ->everyTwoMinutes()
    ->withoutOverlapping();
