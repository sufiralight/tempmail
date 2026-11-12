<?php

namespace Tests\Feature;

use App\Installation\DatabaseVerifier;
use App\Installation\InstallerState;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/tempmail-installer-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        mkdir($this->directory.'/sessions', 0700);

        config([
            'app.key' => null,
            'installer.path' => $this->directory,
            'session.files' => $this->directory.'/sessions',
            'cache.stores.file.path' => $this->directory.'/cache',
            'cache.stores.file.lock_path' => $this->directory.'/locks',
        ]);

        $this->app->detectEnvironment(fn (): string => 'local');
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'SERVER_ADDR' => '127.0.0.1']);
        $this->gate(['mode' => 'local']);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_fresh_installation_boots_without_an_application_key_and_accepts_both_loopback_hosts(): void
    {
        $this->get('http://localhost:8000/')->assertRedirect('/install');
        foreach (['localhost', '127.0.0.1'] as $host) {
            $page = $this->get("http://{$host}:8000/install")->assertOk()
                ->assertSee('Connect your database')->assertHeader('cache-control', 'no-store, private');
            $this->assertMatchesRegularExpression('/<input[^>]*name="database_password"[^>]*>/', $page->getContent());
            $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="database_password"[^>]*required/', $page->getContent());
            $this->assertTrue($page->getCookie('tempmail_installer', false)->isHttpOnly());
        }
        $this->assertNull(config('app.key'));
    }

    public function test_successful_verification_keeps_an_empty_password_and_creates_only_a_private_handoff(): void
    {
        $before = config('database.connections.mysql');
        $this->mock(DatabaseVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->with([
                'host' => '127.0.0.1', 'port' => 3306, 'database' => 'tempmail_fixture', 'username' => 'fixture', 'password' => '',
            ])->andReturnTrue();
        });

        $page = $this->open();
        $firstSession = $page->baseRequest->session()->getId();
        $verified = $this->submit($page, ['host' => 'localhost', 'database_password' => ''])
            ->assertHeader('Location', '/install#connection-verified');
        $this->assertNotSame($firstSession, $verified->baseRequest->session()->getId());
        $this->assertSame([
            'host' => '127.0.0.1', 'port' => 3306, 'database' => 'tempmail_fixture', 'username' => 'fixture', 'password' => '',
        ], app(InstallerState::class)->verifiedDatabase($verified->baseRequest));
        $this->assertStringNotContainsString('tempmail_fixture', file_get_contents($this->directory.'/lease'));
        $this->assertFileDoesNotExist($this->directory.'/installed');
        $this->assertSame($before, config('database.connections.mysql'));

        $this->continueWith($page);
        $this->open()->assertDontSee('Database connection verified');
        $this->continueWith($verified);
        $this->get('http://localhost:8000/install')->assertOk()->assertSee('Database connection verified');
        $this->get('http://localhost:8000/install/admin')->assertNotFound();
    }

    public function test_invalid_inputs_never_reach_the_database_and_are_not_flashed(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
        $page = $this->open();
        $response = $this->submit($page, ['host' => '127.0.0.1;unix_socket=/tmp/mysql.sock', 'port' => 65536, 'database' => '../other', 'database_password' => 'do-not-flash'])
            ->assertHeader('Location', '/install');
        $this->continueWith($response);
        $this->get('http://localhost:8000/install')->assertOk()
            ->assertSee('Enter the database host authorized for this installation.')
            ->assertSee('database name field format is invalid')
            ->assertDontSee('do-not-flash')
            ->assertDontSee('unix_socket')
            ->assertDontSee('../other');

        foreach (glob($this->directory.'/sessions/*') as $file) {
            $this->assertStringNotContainsString('do-not-flash', file_get_contents($file));
        }
    }

    public function test_connection_failure_is_generic_even_with_debug_enabled(): void
    {
        config(['app.debug' => true]);
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldReceive('verify')->once()->andReturnFalse());

        $response = $this->submit($this->open(), ['database_password' => 'private-password'])
            ->assertHeader('Location', '/install');
        $this->continueWith($response);
        $this->get('http://localhost:8000/install')->assertOk()
            ->assertSee('Could not verify an empty database')
            ->assertDontSee('private-password')->assertDontSee('PDOException');
        $this->assertStringNotContainsString('private-password', file_get_contents($this->directory.'/lease'));
    }

    public function test_csrf_is_enforced_and_admin_route_is_unavailable(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
        $this->get('http://localhost:8000/install/admin')->assertNotFound();
        $this->submit($this->open(), ['_token' => 'incorrect'])->assertStatus(419)->assertSee('Session expired');
        $this->assertFileDoesNotExist($this->directory.'/lease');
    }

    public function test_verification_rate_limit_and_body_limit_apply_before_database_work(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
        $page = $this->open();
        $this->continueWith($page);
        $this->call('POST', 'http://localhost:8000/install', [], [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ], str_repeat('x', 8193))->assertStatus(413);
        $this->post('http://localhost:8000/install?host=localhost', [], ['Content-Type' => 'application/x-www-form-urlencoded'])->assertStatus(400);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = $this->submit($page, ['database' => ''])->assertRedirect();
            $this->continueWith($response);
            $page = $this->open();
        }
        $this->submit($page, ['database' => ''])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_competing_sessions_cannot_replace_or_read_the_active_handoff_and_it_expires(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldReceive('verify')->once()->andReturnTrue());
        $first = $this->open();
        $second = $this->open();
        $verified = $this->submit($first)->assertRedirect();
        $this->submit($second)->assertStatus(409);
        $this->continueWith($second);
        $this->open()->assertDontSee('Database connection verified');

        $this->continueWith($verified);
        $this->open()->assertSee('Database connection verified');
        $this->travel(InstallerState::LIFETIME_MINUTES + 1)->minutes();
        $this->open()->assertDontSee('Database connection verified');
        $this->assertFileDoesNotExist($this->directory.'/lease');
    }

    public function test_new_submission_invalidates_previous_verification_before_field_validation(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldReceive('verify')->once()->andReturnTrue());
        $verified = $this->submit($this->open());
        $this->continueWith($verified);
        $page = $this->open()->assertSee('Database connection verified');
        $response = $this->submit($page, ['database' => ''])->assertRedirect();
        $this->continueWith($response);
        $this->open()->assertDontSee('Database connection verified');
    }

    public function test_missing_configuration_without_owner_gate_and_existing_installations_fail_closed(): void
    {
        $this->app->useEnvironmentPath($this->directory);
        $this->assertFileDoesNotExist($this->app->environmentFilePath());
        unlink($this->directory.'/enable.json');
        $this->get('http://localhost:8000/')->assertStatus(503)->assertSee('restore the original environment');
        $this->get('http://localhost:8000/install')->assertNotFound();

        $this->gate(['mode' => 'local']);
        touch($this->directory.'/installed');
        $this->get('http://localhost:8000/install')->assertNotFound();
        $this->get('http://localhost:8000/')->assertStatus(503);
        $this->assertFileDoesNotExist($this->directory.'/key');
    }

    public function test_non_loopback_and_spoofed_proxy_headers_cannot_open_local_installer(): void
    {
        $this->get('http://untrusted.example/install')->assertNotFound();
        foreach (['localhost', '127.0.0.1'] as $host) {
            foreach ([
                'X-Forwarded-For' => '127.0.0.1',
                'X-Forwarded-Host' => 'localhost',
                'Forwarded' => 'for=127.0.0.1',
            ] as $header => $value) {
                $this->get("http://{$host}:8000/install", [$header => $value])->assertNotFound();
            }
        }
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.15', 'SERVER_ADDR' => '127.0.0.1']);
        $this->get('http://localhost:8000/install')->assertNotFound();
        $this->assertFileDoesNotExist($this->directory.'/key');
    }

    public function test_local_enable_file_is_required_and_never_overrides_installed_state(): void
    {
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        unlink($this->directory.'/enable.json');

        foreach (['localhost', '127.0.0.1'] as $host) {
            $this->get("http://{$host}:8000/install")->assertNotFound()->assertSee('Installation is unavailable.');
        }
        $this->assertFileDoesNotExist($this->directory.'/key');

        $this->gate(['mode' => 'local']);
        foreach (['localhost', '127.0.0.1'] as $host) {
            $this->get("http://{$host}:8000/install")->assertOk()->assertSee('Connect your database');
        }

        touch($this->directory.'/installed');
        foreach (['localhost', '127.0.0.1'] as $host) {
            $this->get("http://{$host}:8000/install")->assertNotFound();
        }
    }

    public function test_production_requires_the_operator_peer_https_and_the_configured_host_and_database_target(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $this->gate([
            'mode' => 'production', 'host' => 'mail.example.test', 'operator_ip' => '203.0.113.17',
            'database_host' => 'db.example.test', 'database_port' => 3307,
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.16']);
        $this->get('https://mail.example.test/install', ['X-Forwarded-For' => '203.0.113.17'])->assertNotFound();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.17']);
        $this->get('http://mail.example.test/install', ['X-Forwarded-Proto' => 'https'])->assertNotFound();
        $this->get('https://wrong.example.test/install')->assertNotFound();
        $page = $this->get('https://mail.example.test/install')->assertOk()->assertSee('db.example.test');
        $this->assertTrue($page->getCookie('tempmail_installer', false)->isSecure());

        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
        $response = $this->submit($page, ['host' => '127.0.0.1', 'port' => 3306], 'https://mail.example.test/install')->assertRedirect();
        $this->continueWith($response);
        $this->get('https://mail.example.test/install')->assertSee('database host authorized')->assertSee('database port authorized');
    }

    /** @param array<string, mixed> $configuration */
    private function gate(array $configuration): void
    {
        file_put_contents($this->directory.'/enable.json', json_encode($configuration));
    }

    private function open(): TestResponse
    {
        return $this->get('http://localhost:8000/install')->assertOk();
    }

    private function continueWith(TestResponse $response): void
    {
        $this->withUnencryptedCookie('tempmail_installer', $response->getCookie('tempmail_installer', false)->getValue());
    }

    /** @param array<string, mixed> $overrides */
    private function submit(TestResponse $page, array $overrides = [], string $url = 'http://localhost:8000/install'): TestResponse
    {
        $this->continueWith($page);
        preg_match('/name="_token" value="([^"]+)"/', $page->getContent(), $token);
        $this->assertArrayHasKey(1, $token);

        return $this->post($url, array_replace([
            '_token' => $token[1],
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'tempmail_fixture',
            'username' => 'fixture',
            'database_password' => '',
        ], $overrides), ['Content-Type' => 'application/x-www-form-urlencoded']);
    }
}
