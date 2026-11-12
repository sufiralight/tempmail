<?php

use App\Http\Controllers\InstallerController;
use App\Http\Middleware\BeginDatabaseVerification;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallerController::class, 'show'])->name('install.database');
Route::post('/install', [InstallerController::class, 'verify'])
    ->middleware(BeginDatabaseVerification::class)
    ->block(20, 3)
    ->name('install.verify');

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/_ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-preview');
    })->name('ui.preview');
}
