<?php

use App\Http\Middleware\PrepareInstaller;
use App\Http\Middleware\VerifyWebRequestForgery;
use App\Installation\InstallationRecoveryRequired;
use App\Installation\InstallerEnvironment;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(PrepareInstaller::class);
        $middleware->redirectUsersTo('/dashboard');
        $middleware->trimStrings(except: ['database_password']);
        $middleware->web(replace: [PreventRequestForgery::class => VerifyWebRequestForgery::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['database_password', 'token']);
        $exceptions->dontReportWhen(fn () => request()->is('install', 'install/*'));
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('install', 'install/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            [$title, $message] = match (true) {
                $exception instanceof InstallationRecoveryRequired => ['Installation recovery required', 'The deployment operator must review the database and the private installation checkpoint before continuing.'],
                $status === 404 => ['Installation step unavailable', 'Return to database verification to continue.'],
                $status === 409 => ['Installation session unavailable', 'Another session may be active or your verification may have expired. Return to setup and try again when the lease expires.'],
                $status === 419 => ['Session expired', 'Return to setup and verify the database again.'],
                default => ['Installation unavailable', 'Try again shortly. If the problem persists, ask the deployment operator to check private installation storage and configuration.'],
            };

            return new Response(view('installation.notice', compact('title', 'message')), $status, [
                'Cache-Control' => 'no-store, private',
                'Referrer-Policy' => 'no-referrer',
                'X-Frame-Options' => 'DENY',
            ]);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

$app->beforeBootstrapping(LoadEnvironmentVariables::class, static function (): void {
    InstallerEnvironment::captureExternalValues();
});

return $app;
