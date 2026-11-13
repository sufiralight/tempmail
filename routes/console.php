<?php

use App\Auth\AccountSessions;
use App\Installation\InstallationAccess;
use App\Media\MediaStorage;
use App\Models\Media;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Schedule::call(function (): void {
    if (app(InstallationAccess::class)->installed()) {
        app(AccountSessions::class)->prune();
    }
})->daily()->name('account-sessions:prune');

Schedule::call(function (): void {
    if (app(InstallationAccess::class)->installed() && Schema::hasTable('media')) {
        Media::onlyTrashed()->eachById(fn (Media $media) => app(MediaStorage::class)->purge($media));
    }
})->daily()->name('media:purge-deleted');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
