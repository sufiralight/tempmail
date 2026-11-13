<?php

use App\Auth\AccountSessions;
use App\Installation\InstallationAccess;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (): void {
    if (app(InstallationAccess::class)->installed()) {
        app(AccountSessions::class)->prune();
    }
})->daily()->name('account-sessions:prune');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
