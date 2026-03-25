<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;

Route::middleware('auth')->group(function () {
    Route::post('/app/subscribe', [PushSubscriptionController::class, 'store'])->name('pwa.subscribe');
});

Route::get(config('pwa.home_route', '/app'), function () { return view('vendor.pwa.pages.home'); })->name('app.home');