<?php

namespace App\Http\Middleware;

use App\Installation\InstallerState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BeginDatabaseVerification
{
    public function __construct(private InstallerState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('installer.attempt', $this->state->beginVerification($request));

        return $next($request);
    }
}
