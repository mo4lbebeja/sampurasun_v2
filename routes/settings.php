<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\LetterheadSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('settings/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('settings/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')
        ->name('appearance.edit');

    Route::get('settings/kop-surat', [LetterheadSettingController::class, 'edit'])
        ->name('settings.kop-surat.edit');

    Route::post('settings/kop-surat', [LetterheadSettingController::class, 'update'])
        ->name('settings.kop-surat.update');

    Route::post('settings/threshold', [\App\Http\Controllers\Settings\LetterheadSettingController::class, 'updateThreshold'])
        ->name('settings.threshold.update');
});