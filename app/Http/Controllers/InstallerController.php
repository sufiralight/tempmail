<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyInstallerDatabase;
use App\Installation\DatabaseVerifier;
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

        return view('installation.database', [
            'access' => $request->attributes->get('installer.access'),
            'verified' => $verified,
            'expired' => ! $verified && $request->session()->pull('installer.verified', false),
        ]);
    }

    public function verify(VerifyInstallerDatabase $request, DatabaseVerifier $verifier): RedirectResponse
    {
        $settings = $request->settings();

        if (! $verifier->verify($settings)) {
            return (new RedirectResponse('/install'))->setSession(app(Store::class))->withErrors([
                'connection' => 'Could not verify an empty database. Check the host, port, database name and credentials, and confirm the database has no tables or views.',
            ]);
        }

        $this->state->completeVerification($request, $request->attributes->get('installer.attempt'), $settings);

        // Prompt 05 can replace this redirect once its protected admin route exists.
        return new RedirectResponse('/install#connection-verified');
    }
}
