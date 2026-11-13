<?php

namespace App\Http;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class SafeErrors
{
    public static function databaseFailure(Throwable $exception): ?string
    {
        while (! $exception instanceof PDOException && $exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }

        if (! $exception instanceof PDOException) {
            return null;
        }

        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $native = (int) ($exception->errorInfo[1] ?? 0);

        return match (true) {
            in_array($state, ['42S02', '42S22', '42P01', '42703'], true),
            $state === '42000' && in_array($native, [207, 208], true),
            $state === 'HY000' && $native === 1
                && preg_match('/no such (table|column)|has no column named/i', (string) ($exception->errorInfo[2] ?? '')) => 'schema',
            str_starts_with($state, '08'), in_array($native, [1044, 1045, 1049, 2002, 2003, 2006], true) => 'connection',
            default => 'query',
        };
    }

    public static function report(Throwable $exception): bool
    {
        try {
            Log::error('Unhandled web request error.', [
                'exception' => $exception::class,
                'category' => self::databaseFailure($exception) ?? 'application',
                'route' => request()->route()?->getName(),
            ]);
        } catch (Throwable) {
            error_log('Temp Mail could not write an application error log.');
        }

        return false;
    }

    public static function render(Throwable $exception, Request $request): ?Response
    {
        if ($exception instanceof AuthenticationException || $exception instanceof ValidationException || $exception instanceof HttpResponseException) {
            return null;
        }

        $failure = self::databaseFailure($exception);
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode()
            : (in_array($failure, ['schema', 'connection'], true) ? 503 : 500);

        $messages = [
            403 => ['Access denied', 'You do not have permission to access this page.'],
            404 => ['Page not found', 'The page you are looking for does not exist or has moved.'],
            419 => ['Session expired', 'Open the form again and try once more.'],
            429 => ['Too many requests', 'Please wait before trying again.'],
            500 => ['Something went wrong', 'We could not complete your request. Please try again later.'],
            503 => ['Temporarily unavailable', 'The service is temporarily unavailable. Please try again shortly.'],
        ];
        if (! isset($messages[$status])) {
            return null;
        }
        [$title, $message] = $messages[$status];
        if ($failure === 'schema' && $request->is('admin', 'admin/*')) {
            $message = 'Ask the deployment operator to check migration status for this release.';
        }

        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $headers['Cache-Control'] = 'no-store, private';

        return self::respond($request, $status, $title, $message, $headers);
    }

    /** @param array<string, mixed> $headers */
    public static function respond(Request $request, int $status, string $title, string $message, array $headers = [], bool $installer = false): Response
    {
        $json = $request->expectsJson() || $request->is('api/*');
        $type = $json ? 'application/json' : 'text/html; charset=UTF-8';
        try {
            $body = $json ? json_encode(['message' => $message], JSON_THROW_ON_ERROR)
                : view('errors.safe', compact('status', 'title', 'message', 'installer'))->render();
        } catch (Throwable) {
            $body = $title.'. '.$message;
            $type = 'text/plain; charset=UTF-8';
        }

        $response = new Response($body, $status, array_merge($headers, [
            'Content-Type' => $type, 'Cache-Control' => 'no-store, private',
        ]));
        SecurityHeaders::apply($request, $response);

        return $response;
    }
}
