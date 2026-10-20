<?php

use App\Http\Controllers\ActiveSessionController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceAccountController;
use App\Http\Controllers\DeviceLoginController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\HotspotProfileController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Language switch: open to guests so the sign-in page can use it.
Route::post('/locale', [LocaleController::class, 'update'])->name('locale');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/{router}/stats', [DashboardController::class, 'stats'])
        ->middleware(['router.access', 'device.login', 'permission:monitor.view'])->name('dashboard.stats');

    // Router management (admins)
    Route::middleware('permission:routers.manage')->group(function () {
        Route::resource('routers', RouterController::class)->except('show');
        Route::post('/routers/{router}/test', [RouterController::class, 'test'])->name('routers.test');
    });

    // The second login: staff sign in to each device with that device's own account.
    Route::prefix('r/{router}')->name('r.')->middleware('router.access')->group(function () {
        Route::get('/login', [DeviceLoginController::class, 'show'])->name('login');
        Route::post('/login', [DeviceLoginController::class, 'store'])->name('login.store');
        Route::post('/logout', [DeviceLoginController::class, 'destroy'])->name('logout');
    });

    // Everything that operates on one router (staff must have signed in to it first).
    Route::prefix('r/{router}')->name('r.')->middleware(['router.access', 'device.login'])->group(function () {
        Route::middleware('permission:accounts.manage')->group(function () {
            Route::get('/accounts', [DeviceAccountController::class, 'index'])->name('accounts');
            Route::post('/accounts', [DeviceAccountController::class, 'store'])->name('accounts.store');
            Route::put('/accounts/{account}', [DeviceAccountController::class, 'update'])->name('accounts.update');
            Route::delete('/accounts/{account}', [DeviceAccountController::class, 'destroy'])->name('accounts.destroy');
        });

        Route::middleware('permission:monitor.view')->group(function () {
            Route::get('/', [MonitorController::class, 'show'])->name('overview');
            Route::get('/interfaces', [MonitorController::class, 'interfaces'])->name('interfaces');
            Route::get('/traffic', [MonitorController::class, 'traffic'])->name('traffic');
            Route::get('/monitoring', [MonitorController::class, 'charts'])->name('monitoring');
            Route::get('/monitoring/data', [MonitorController::class, 'metrics'])->name('monitoring.data');
            Route::get('/logs', [LogController::class, 'index'])->name('logs');
            Route::get('/logs/data', [LogController::class, 'data'])->name('logs.data');
            Route::get('/checks', [CheckController::class, 'index'])->name('checks');
        });
        Route::middleware('permission:alerts.manage')->group(function () {
            Route::post('/checks', [CheckController::class, 'store'])->name('checks.store');
            Route::post('/checks/{check}/toggle', [CheckController::class, 'toggle'])->name('checks.toggle');
            Route::delete('/checks/{check}', [CheckController::class, 'destroy'])->name('checks.destroy');
        });

        Route::middleware('permission:backups.manage')->group(function () {
            Route::get('/backups', [BackupController::class, 'index'])->name('backups');
            Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
            Route::get('/backups/{backup}', [BackupController::class, 'show'])->name('backups.show');
            Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
            Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');
        });
        Route::post('/backups/reset-fingerprint', [BackupController::class, 'resetFingerprint'])
            ->middleware('permission:routers.manage')->name('backups.reset-fingerprint');

        Route::post('/sync', [RouterController::class, 'sync'])->middleware('permission:hotspot.manage')->name('sync');

        Route::middleware('permission:hotspot.view')->group(function () {
            Route::get('/users', [HotspotUserController::class, 'index'])->name('users');
            Route::get('/profiles', [HotspotProfileController::class, 'index'])->name('profiles');
            Route::get('/active', [ActiveSessionController::class, 'index'])->name('active');
            Route::get('/active/data', [ActiveSessionController::class, 'data'])->name('active.data');
        });
        Route::middleware('permission:hotspot.manage')->group(function () {
            Route::post('/users', [HotspotUserController::class, 'store'])->name('users.store');
            Route::post('/users/bulk', [HotspotUserController::class, 'bulk'])->name('users.bulk');
            Route::post('/active/kick', [ActiveSessionController::class, 'kick'])->name('active.kick');
        });
        Route::middleware('permission:profiles.manage')->group(function () {
            Route::post('/profiles', [HotspotProfileController::class, 'store'])->name('profiles.store');
            Route::put('/profiles/{id}', [HotspotProfileController::class, 'update'])->name('profiles.update');
            Route::delete('/profiles/{id}', [HotspotProfileController::class, 'destroy'])->name('profiles.destroy');
        });

        Route::middleware('permission:vouchers.manage')->group(function () {
            Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers');
            Route::post('/vouchers', [VoucherController::class, 'generate'])->name('vouchers.generate');
            Route::get('/vouchers/{batch}', [VoucherController::class, 'show'])->name('vouchers.show');
            Route::get('/vouchers/{batch}/print', [VoucherController::class, 'print'])->name('vouchers.print');
            Route::get('/vouchers/{batch}/csv', [VoucherController::class, 'csv'])->name('vouchers.csv');
            Route::delete('/vouchers/{batch}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        });
    });

    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports');
    Route::get('/audit', [AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit');

    Route::get('/alerts', [AlertController::class, 'index'])->middleware('permission:monitor.view')->name('alerts');
    Route::middleware('permission:alerts.manage')->group(function () {
        Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
        Route::post('/alerts/defaults', [AlertController::class, 'defaults'])->name('alerts.defaults');
        Route::post('/alerts/{rule}/toggle', [AlertController::class, 'toggle'])->name('alerts.toggle');
        Route::delete('/alerts/{rule}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/locale', [SettingsController::class, 'updateLocale'])->name('settings.locale');
        Route::put('/settings/telegram', [SettingsController::class, 'updateTelegram'])->name('settings.telegram');
        Route::post('/settings/telegram/test', [SettingsController::class, 'testTelegram'])->name('settings.telegram.test');
        Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::post('/settings/notifications/test', [SettingsController::class, 'testNotifications'])->name('settings.notifications.test');
        Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
        Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
        Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
    });

    // The user guide is for every signed-in account, whatever the role.
    Route::get('/docs/{page?}', [DocsController::class, 'show'])->name('docs');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
