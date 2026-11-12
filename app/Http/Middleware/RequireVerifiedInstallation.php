<?php

namespace App\Http\Middleware;

use App\Installation\InstallerState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class RequireVerifiedInstallation
{
    public function __construct(private InstallerState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->state->isVerified($request)) {
            Log::notice('Installer administrator step denied without a verified lease.');
            abort(404);
        }

        return $next($request);
    }
}
