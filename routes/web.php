<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\InstallerAdminController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceAdminController;
use App\Http\Middleware\BeginDatabaseVerification;
use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\RequireVerifiedInstallation;
use App\Http\Middleware\ThrottleInstallation;
use App\Http\Middleware\TrackAccountSession;
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

Route::middleware(['auth', 'auth.session', RequireActiveAccount::class, TrackAccountSession::class])->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed:relative', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification-resend')->name('verification.send');
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::view('/dashboard', 'workspace.dashboard')->middleware('can:dashboard.view')->name('dashboard');
    Route::prefix('media')->name('media.')->controller(MediaController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:20,1')->name('store');
        Route::get('/picker', 'index')->name('picker');
        Route::get('/trash', 'index')->name('trash');
        Route::post('/trash/restore', 'restore')->middleware('throttle:10,1,media-restore:')->name('restore');
        Route::delete('/trash', 'purge')->middleware('throttle:10,1,media-purge:')->name('purge');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('throttle:10,1')->name('bulk-delete');
        Route::get('/{media}/file', 'file')->whereNumber('media')->name('file');
        Route::get('/{media}', 'show')->whereNumber('media')->name('show');
        Route::patch('/{media}', 'update')->whereNumber('media')->name('update');
        Route::delete('/{media}', 'destroy')->whereNumber('media')->middleware('throttle:10,1')->name('destroy');
    });
    Route::prefix('media-library')->controller(MediaController::class)->group(function (): void {
        Route::get('/', 'legacyIndex');
        Route::get('/picker', 'legacyPicker');
        Route::get('/{media}/file', 'legacyFile')->whereNumber('media');
        Route::get('/{media}', 'legacyShow')->whereNumber('media');
        Route::post('/', 'store')->middleware('throttle:20,1');
        Route::post('/bulk-delete', 'bulkDelete')->middleware('throttle:10,1');
        Route::patch('/{media}', 'update')->whereNumber('media');
        Route::delete('/{media}', 'destroy')->whereNumber('media')->middleware('throttle:10,1');
    });
    Route::prefix('profile')->name('profile.')->group(function (): void {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/avatar/{mediaId}', [ProfileAvatarController::class, 'show'])->whereNumber('mediaId')->name('avatar.show');
        Route::controller(ProfileAvatarController::class)->middleware('throttle:10,1,avatar:')->group(function (): void {
            Route::post('/avatar', 'upload')->name('avatar.upload');
            Route::patch('/avatar', 'update')->name('avatar.update');
            Route::delete('/avatar', 'destroy')->name('avatar.destroy');
        });
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        Route::get('/sessions', [ProfileController::class, 'sessions'])->name('sessions');
        Route::get('/preferences', [ProfileController::class, 'preferences'])->name('preferences');
        Route::middleware('throttle:10,1')->group(function (): void {
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::patch('/password', [ProfileController::class, 'password'])->name('password');
            Route::patch('/preferences', [ProfileController::class, 'savePreferences'])->name('preferences.update');
            Route::delete('/sessions/{session}', [ProfileController::class, 'revoke'])->whereUuid('session')->name('sessions.revoke');
            Route::post('/sessions/revoke-others', [ProfileController::class, 'revoke'])->name('sessions.revoke-others');
        });
    });
    Route::prefix('admin')->middleware(['can:admin.access', 'verified'])->group(function (): void {
        Route::get('/media', [MediaController::class, 'index'])->middleware('can:media.audit')->name('admin.media.index');
        Route::get('/media/{media}', [MediaController::class, 'show'])->whereNumber('media')->middleware('can:media.audit')->name('admin.media.show');
        Route::get('/media/{media}/file', [MediaController::class, 'file'])->whereNumber('media')->middleware('can:media.audit')->name('admin.media.file');
        Route::get('/settings/branding', [BrandingController::class, 'show'])->middleware('can:settings.manage')->name('admin.branding');
        Route::patch('/settings/branding', [BrandingController::class, 'update'])->middleware(['can:settings.manage', 'throttle:10,1'])->name('admin.branding.update');
        Route::get('/', [WorkspaceAdminController::class, 'overview'])->name('admin');
        Route::get('/users', [WorkspaceAdminController::class, 'users'])->middleware('can:users.view')->name('admin.users');
        Route::get('/users/{user}/avatar/{mediaId}', [ProfileAvatarController::class, 'adminShow'])
            ->whereNumber('mediaId')->middleware('can:users.view')->name('admin.users.avatar');
        Route::get('/users/{user}', [WorkspaceAdminController::class, 'user'])->middleware('can:users.view')->name('admin.users.show');
        Route::patch('/users/{user}', [WorkspaceAdminController::class, 'updateUser'])
            ->middleware(['can:manage,user', 'throttle:10,1'])->name('admin.users.update');
        Route::get('/roles', [WorkspaceAdminController::class, 'roles'])->middleware('can:roles.manage')->name('admin.roles');
        Route::get('/settings', [WorkspaceAdminController::class, 'settings'])->middleware('can:settings.view')->name('admin.settings');
        Route::patch('/settings', [WorkspaceAdminController::class, 'updateSettings'])
            ->middleware(['can:settings.manage', 'throttle:10,1'])->name('admin.settings.update');
        Route::get('/audit-logs', [WorkspaceAdminController::class, 'audits'])->middleware('can:audit.view')->name('admin.audits');
    });
});

Route::get('/branding/{slot}', [BrandingController::class, 'asset'])
    ->whereIn('slot', BrandingController::SLOTS)->name('branding.asset');

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/_ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-preview');
    })->name('ui.preview');
}
