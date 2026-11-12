<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

final class VerifyWebRequestForgery extends PreventRequestForgery
{
    protected function hasValidOrigin($request): bool
    {
        return ! $request->is('install', 'install/*') && parent::hasValidOrigin($request);
    }
}
