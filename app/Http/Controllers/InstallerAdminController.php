<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinishInstallation;
use App\Installation\InstallerFinalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

final class InstallerAdminController extends Controller
{
    public function show(): View
    {
        return view('installation.admin');
    }

    public function finish(FinishInstallation $request, InstallerFinalizer $installer): RedirectResponse
    {
        $installer->finish($request, $request->administrator());

        return new RedirectResponse('/setup-complete', 303);
    }

    public function complete(): View|RedirectResponse
    {
        return Route::has('login')
            ? redirect()->route('login')->with('status', 'Workspace setup is complete. Sign in with your administrator account.')
            : view('installation.complete');
    }
}
