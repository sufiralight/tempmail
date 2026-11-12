<?php

use App\Http\Controllers\InstallerAdminController;
use App\Http\Controllers\InstallerController;
use App\Http\Middleware\BeginDatabaseVerification;
use App\Http\Middleware\RequireVerifiedInstallation;
use App\Http\Middleware\ThrottleInstallation;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallerController::class, 'show'])->name('install.database');
Route::post('/install', [InstallerController::class, 'verify'])
    ->middleware([ThrottleInstallation::class, BeginDatabaseVerification::class])
    ->block(20, 3)
    ->name('install.verify');

Route::get('/install/admin', [InstallerAdminController::class, 'show'])
    ->middleware(RequireVerifiedInstallation::class)
    ->name('install.admin');
Route::post('/install/admin', [InstallerAdminController::class, 'finish'])
    ->middleware([ThrottleInstallation::class, RequireVerifiedInstallation::class])
    ->block(30, 3)
    ->name('install.finish');
Route::get('/setup-complete', [InstallerAdminController::class, 'complete'])->name('install.complete');

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/_ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-preview');
    })->name('ui.preview');
}
