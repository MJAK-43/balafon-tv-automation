<?php

use App\Http\Controllers\BrandingOverlayController;
use Illuminate\Support\Facades\Route;

Route::get('/overlays/ticker/{uuid}', BrandingOverlayController::class)
    ->name('overlays.ticker');

Route::view('/{any?}', 'app')->where('any', '.*');
