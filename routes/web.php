<?php

use App\Http\Controllers\ActiveSessionController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HotspotProfileController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/{router}/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    Route::resource('routers', RouterController::class)->except('show');
    Route::post('/routers/{router}/test', [RouterController::class, 'test'])->name('routers.test');

    // Everything that operates on one router.
    Route::prefix('r/{router}')->name('r.')->group(function () {
        Route::get('/', [MonitorController::class, 'show'])->name('overview');
        Route::get('/interfaces', [MonitorController::class, 'interfaces'])->name('interfaces');
        Route::get('/traffic', [MonitorController::class, 'traffic'])->name('traffic');
        Route::post('/sync', [RouterController::class, 'sync'])->name('sync');

        Route::get('/users', [HotspotUserController::class, 'index'])->name('users');
        Route::post('/users', [HotspotUserController::class, 'store'])->name('users.store');
        Route::post('/users/bulk', [HotspotUserController::class, 'bulk'])->name('users.bulk');

        Route::get('/profiles', [HotspotProfileController::class, 'index'])->name('profiles');
        Route::post('/profiles', [HotspotProfileController::class, 'store'])->name('profiles.store');
        Route::put('/profiles/{id}', [HotspotProfileController::class, 'update'])->name('profiles.update');
        Route::delete('/profiles/{id}', [HotspotProfileController::class, 'destroy'])->name('profiles.destroy');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers');
        Route::post('/vouchers', [VoucherController::class, 'generate'])->name('vouchers.generate');
        Route::get('/vouchers/{batch}', [VoucherController::class, 'show'])->name('vouchers.show');
        Route::get('/vouchers/{batch}/print', [VoucherController::class, 'print'])->name('vouchers.print');
        Route::get('/vouchers/{batch}/csv', [VoucherController::class, 'csv'])->name('vouchers.csv');
        Route::delete('/vouchers/{batch}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

        Route::get('/active', [ActiveSessionController::class, 'index'])->name('active');
        Route::get('/active/data', [ActiveSessionController::class, 'data'])->name('active.data');
        Route::post('/active/kick', [ActiveSessionController::class, 'kick'])->name('active.kick');
    });

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/locale', [SettingsController::class, 'updateLocale'])->name('settings.locale');
    Route::put('/settings/telegram', [SettingsController::class, 'updateTelegram'])->name('settings.telegram');
    Route::post('/settings/telegram/test', [SettingsController::class, 'testTelegram'])->name('settings.telegram.test');
    Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
