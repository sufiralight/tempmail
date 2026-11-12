<?php

namespace App\Http\Middleware;

use App\Installation\InstallationAccess;
use App\Installation\InstallerState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class PrepareInstaller
{
    public function __construct(private InstallationAccess $access, private InstallerState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        $installer = $request->is('install', 'install/*');
        $home = $request->is('/') && $request->isMethodSafe();

        if (! $installer && ! $home) {
            return $next($request);
        }

        $access = $this->access->forRequest($request);

        if ($home) {
            if ($access !== null) {
                return $this->private(new RedirectResponse('/install'));
            }

            if (! config('app.key')) {
                return $this->private(new Response('Configuration is unavailable. The deployment operator must restore the original environment or explicitly enable first installation.', 503));
            }

            return $next($request);
        }

        if ($access === null) {
            return $this->private(new Response('Installation is unavailable.', 404));
        }

        if ($request->getRealMethod() === 'POST' && $request->query->count() !== 0) {
            return $this->private(new Response('Submit connection details in the installation form.', 400));
        }

        if ($request->getRealMethod() === 'POST' && (
            (int) $request->server('CONTENT_LENGTH', 0) > 8192 || $request->getContentTypeFormat() !== 'form'
            || strlen($request->getContent()) > 8192
        )) {
            return $this->private(new Response('Submit the installation form with a request of at most 8 KiB.', 413));
        }

        $request->attributes->set('installer.access', $access);

        $overrides = [
            'app.key' => $this->state->bootstrapKey(),
            'app.cipher' => 'AES-256-CBC',
            'app.previous_keys' => [],
            'session.driver' => 'file',
            'session.cookie' => 'tempmail_installer',
            'session.lifetime' => InstallerState::LIFETIME_MINUTES,
            'session.expire_on_close' => true,
            'session.encrypt' => true,
            'session.path' => '/install',
            'session.domain' => null,
            'session.secure' => ! $access['local'] || $request->isSecure(),
            'session.http_only' => true,
            'session.same_site' => 'strict',
            'session.block_store' => 'file',
        ];

        $request->attributes->set('installer.original_config', config()->getMany(array_keys($overrides)));
        config($overrides);
        $this->resetSessionServices();

        return $this->private($next($request));
    }

    public function terminate(Request $request, Response $response): void
    {
        $original = $request->attributes->get('installer.original_config');
        if (is_array($original)) {
            config($original);
            $this->resetSessionServices();
        }
    }

    private function resetSessionServices(): void
    {
        app(SessionManager::class)->forgetDrivers();
        app()->forgetInstance('session.store');
        app()->forgetInstance('encrypter');
    }

    private function private(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
