<?php

use Illuminate\Support\Facades\Route;
use Lightworx\FilamentPwa\Http\Controllers\PushSubscriptionController;

Route::middleware('auth')->group(function () {
    Route::post('/app/subscribe', [PushSubscriptionController::class, 'store'])->name('pwa.subscribe');
});

Route::get('/pwa-test', function () {
    return view('vendor.pwa.pages.home', ['title' => 'Test', 'themeColor' => '#4f46e5']);
});

Route::get('/app', function () {
    return view('vendor.pwa.pages.home'); 
})->name('filament-pwa.home');