<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinishInstallation;
use App\Installation\InstallerFinalizer;
use App\Installation\InstallerState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

final class InstallerAdminController extends Controller
{
    public function show(InstallerState $state): View
    {
        return view('installation.admin', ['reversible' => ! $state->pendingFinalization()]);
    }

    public function finish(FinishInstallation $request, InstallerFinalizer $installer): RedirectResponse
    {
        $installer->finish($request, $request->administrator());

        return new RedirectResponse('/setup-complete', 303);
    }

    public function complete(Request $request): View|RedirectResponse
    {
        if (! Route::has('login')) {
            return view('installation.complete');
        }

        $request->session()->flash('status', 'Workspace setup is complete. Sign in with your administrator account.');

        return new RedirectResponse(route('login', absolute: false));
    }
}
