<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;

Route::middleware('auth')->group(function () {
    Route::post('/pwa/subscribe', [PushSubscriptionController::class, 'store'])->name('pwa.subscribe');
});