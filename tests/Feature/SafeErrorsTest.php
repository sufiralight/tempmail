<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class SafeErrorsTest extends TestCase
{
    public function test_errors_are_safe_without_database_and_keep_http_headers(): void
    {
        config(['app.debug' => true, 'app.key' => null, 'database.default' => 'unavailable']);

        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            Route::get('/test-error-'.$status, static fn () => abort($status, 'sensitive-internal-message', ['Retry-After' => '60']));
            $this->get('/test-error-'.$status)->assertStatus($status)->assertSee('<h1>', false)
                ->assertDontSee('sensitive-internal-message')->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Retry-After', '60')
                ->assertHeader('Referrer-Policy', 'no-referrer');
        }

        Route::get('/test-unexpected', static fn () => throw new RuntimeException('secret fixture value'));
        $this->getJson('/test-unexpected')->assertStatus(500)->assertDontSee('secret fixture value');

        Route::get('/schema-test', static function (): never {
            $exception = new PDOException('secret database details');
            $exception->errorInfo = ['42S02', 1146, 'secret database details'];
            throw $exception;
        });
        $this->get('/schema-test')->assertStatus(503)->assertSee('Temporarily unavailable')->assertDontSee('secret database details');
    }

    public function test_hsts_requires_production_https(): void
    {
        Route::get('/test-headers', static fn () => 'OK');
        config(['app.env' => 'production', 'app.url' => 'https://example.test']);
        $this->get('https://example.test/test-headers')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->get('http://example.test/test-headers', ['X-Forwarded-Proto' => 'https'])->assertHeaderMissing('Strict-Transport-Security');
    }
}
