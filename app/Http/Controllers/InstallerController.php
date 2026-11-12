<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyInstallerDatabase;
use App\Installation\DatabaseEngine;
use App\Installation\DatabaseVerifier;
use App\Installation\InstallationRecoveryRequired;
use App\Installation\InstallerState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;

final class InstallerController extends Controller
{
    public function __construct(private InstallerState $state) {}

    public function show(Request $request): View
    {
        $verified = $this->state->isVerified($request);
        $pendingFinalization = $this->state->pendingFinalization();
        if ($pendingFinalization && ! $verified) {
            throw new InstallationRecoveryRequired;
        }

        $access = $request->attributes->get('installer.access');
        $initial = $access['engine'] ?? $request->session()->get('installer.engine', 'mysql');

        return view('installation.database', [
            'access' => $access,
            'engines' => DatabaseEngine::cases(),
            'initial' => DatabaseEngine::tryFrom($initial) ?? DatabaseEngine::MySql,
            'verified' => $verified,
            'pendingFinalization' => $pendingFinalization,
            'expired' => ! $verified && $request->session()->pull('installer.verified', false),
        ]);
    }

    public function verify(VerifyInstallerDatabase $request, DatabaseVerifier $verifier): RedirectResponse
    {
        $settings = $request->settings();

        if (! $verifier->verify($settings)) {
            return (new RedirectResponse('/install'))->setSession(app(Store::class))->with('installer.engine', $settings['engine'] ?? 'mysql')->withErrors([
                'connection' => 'Could not verify an empty database. Check the host, port, database name and credentials, and confirm the database has no tables or views.',
            ]);
        }

        $this->state->completeVerification($request, $request->attributes->get('installer.attempt'), $settings);

        return new RedirectResponse('/install/admin');
    }
}
