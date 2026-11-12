<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\InstallerAdminController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\PasswordRecoveryController;
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

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AccountController::class, 'login'])->name('login');
    Route::post('/login', [AccountController::class, 'authenticate'])->middleware('throttle:login');
    Route::get('/register', [AccountController::class, 'register'])->name('register');
    Route::post('/register', [AccountController::class, 'create'])->middleware('throttle:register');
    Route::get('/forgot-password', [PasswordRecoveryController::class, 'requestLink'])->name('password.request');
    Route::post('/forgot-password', [PasswordRecoveryController::class, 'sendLink'])->middleware('throttle:password-recovery');
    Route::get('/reset-password/{token}', [PasswordRecoveryController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordRecoveryController::class, 'reset'])->middleware('throttle:password-recovery');
});

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::view('/dashboard', 'auth.workspace', [
        'title' => 'Your workspace', 'description' => 'You are signed in to Temp Mail.',
    ])->name('dashboard');
    Route::view('/admin', 'auth.workspace', [
        'title' => 'Administration', 'description' => 'You are signed in with Super Admin access.',
    ])->middleware('can:access-admin')->name('admin');
});

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/_ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-preview');
    })->name('ui.preview');
}
