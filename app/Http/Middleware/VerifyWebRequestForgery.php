<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

final class VerifyWebRequestForgery extends PreventRequestForgery
{
    protected function hasValidOrigin($request): bool
    {
        return ! $request->is('install', 'install/*', 'admin/*', 'locale/*/preference', 'media', 'media/*', 'media-library', 'media-library/*', 'profile', 'profile/*', 'login', 'register', 'logout', 'forgot-password', 'reset-password', 'email/verification-notification')
            && parent::hasValidOrigin($request);
    }
}
