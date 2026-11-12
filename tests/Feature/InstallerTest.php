<?php

namespace Tests\Feature;

use App\Installation\DatabaseEngine;
use App\Installation\DatabaseVerifier;
use App\Installation\InstallerEnvironment;
use App\Installation\InstallerState;
use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use PDO;
use RuntimeException;
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
            ->assertHeader('Location', '/install/admin');
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
        $this->get('http://localhost:8000/install/admin')->assertOk()->assertSee('Create your administrator account');
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
        $this->get('http://localhost:8000/install/admin')->assertNotFound();

        $this->continueWith($verified);
        $this->open()->assertSee('Database connection verified');
        $this->travel(InstallerState::LIFETIME_MINUTES + 1)->minutes();
        $this->open()->assertDontSee('Database connection verified');
        $this->get('http://localhost:8000/install/admin')->assertNotFound();
        $this->assertFileDoesNotExist($this->directory.'/lease');
    }

    public function test_admin_form_requires_csrf_and_rejects_invalid_account_details_and_role_fields(): void
    {
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldReceive('verify')->once()->andReturnTrue());
        $this->continueWith($this->submit($this->open()));
        $url = 'http://localhost:8000/install/admin';
        $page = $this->get($url)->assertOk()->assertSee('Confirm password');
        $this->submit($page, ['_token' => 'invalid'], $url)->assertStatus(419);

        $response = $this->submit($page, [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short-pass',
            'password_confirmation' => 'different',
            'role' => 'super_admin',
            'is_super_admin' => true,
        ], $url)->assertHeader('Location', '/install/admin');
        $this->continueWith($response);
        $this->get($url)->assertOk()->assertSee('name field is required')
            ->assertSee('valid email address')->assertSee('at least 12 characters')
            ->assertSee('role field must be missing')->assertSee('is super admin field must be missing')
            ->assertDontSee('short-pass')->assertDontSee('different');

        $page = $this->get($url)->assertOk();
        $response = $this->submit($page, [
            'name' => 'Admin Fixture', 'email' => 'owner@example.test',
            'password' => 'fixture-long-secret', 'password_confirmation' => 'fixture-other-secret',
        ], $url)->assertHeader('Location', '/install/admin');
        $this->continueWith($response);
        $this->get($url)->assertOk()->assertSee('confirmation does not match')->assertDontSee('fixture-long-secret');
        $this->assertFileDoesNotExist($this->directory.'/finalization');
        $this->assertFalse((new User(['name' => 'Regular', 'is_super_admin' => true]))->isSuperAdmin());
    }

    public function test_environment_writer_preserves_the_application_key_and_round_trips_special_values(): void
    {
        $this->app->useEnvironmentPath($this->directory);
        $this->app->instance('installer.external_environment', []);
        $key = 'base64:'.base64_encode(random_bytes(32));
        $path = $this->app->environmentFilePath();
        file_put_contents($path, "# custom\r\nAPP_KEY={$key}\r\nAPP_NAME=\"Custom Workspace\"\r\nDB_PASSWORD=old\r\n");

        $request = Request::create('http://localhost:8000/install/admin');
        $request->attributes->set('installer.original_config', ['app.key' => $key, 'app.cipher' => 'AES-256-CBC']);
        $request->attributes->set('installer.access', ['local' => true]);
        $writer = app(InstallerEnvironment::class);
        $password = '  special "'.chr(39).chr(92).' ${APP_KEY} # trailing  ';
        $settings = ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'fixture', 'username' => 'owner', 'password' => $password];
        $plan = $writer->prepare($request, $settings);
        $this->assertStringContainsString('DB_PASSWORD=old', file_get_contents($path));
        $writer->persist($plan);
        $contents = file_get_contents($path);
        $parsed = Dotenv::parse($contents);
        $this->assertSame($password, $parsed['DB_PASSWORD']);
        $this->assertSame($key, $parsed['APP_KEY']);
        $this->assertSame('Custom Workspace', $parsed['APP_NAME']);
        $this->assertStringContainsString("# custom\r\n", $contents);
        $this->assertStringNotContainsString('DB_PASSWORD=old', $contents);

        $settings['password'] = '';
        $writer->persist($writer->prepare($request, $settings));
        $this->assertSame('', Dotenv::parse(file_get_contents($path))['DB_PASSWORD']);
    }

    public function test_real_mariadb_finalization_resumes_an_environment_write_failure_without_creating_a_second_admin(): void
    {
        $port = filter_var(getenv('INSTALLER_TEST_MYSQL_PORT'), FILTER_VALIDATE_INT);
        $database = getenv('INSTALLER_TEST_MYSQL_DATABASE');
        if ($port === false || $database === false) {
            $this->markTestSkipped('Requires an isolated loopback MariaDB fixture.');
        }

        $this->assertGreaterThanOrEqual(1024, $port);
        $this->assertLessThanOrEqual(65535, $port);
        $this->assertNotSame(3306, $port);
        $this->assertMatchesRegularExpression('/\Atempmail_installer_test_[a-f0-9]{16}\z/', $database);
        $pdo = new PDO("mysql:host=127.0.0.1;port={$port};dbname={$database}", 'root', '', [
            PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->assertFalse($pdo->query('SHOW FULL TABLES')->fetchColumn());

        $this->app->useEnvironmentPath($this->directory);
        $this->app->instance('installer.external_environment', []);
        $this->partialMock(InstallerEnvironment::class, function (MockInterface $mock): void {
            $mock->shouldReceive('persist')->once()->andThrow(new RuntimeException('Fixture write interruption.'));
        });
        $engine = getenv('INSTALLER_TEST_MYSQL_ENGINE') ?: 'mysql';
        $this->assertContains($engine, ['mysql', 'mariadb']);
        $this->continueWith($this->submit($this->open(), [
            'engine' => $engine,
            'port' => $port, 'database' => $database, 'username' => 'root',
        ])->assertHeader('Location', '/install/admin'));
        $url = 'http://localhost:8000/install/admin';
        $account = [
            'name' => 'Fixture Owner', 'email' => 'OWNER@EXAMPLE.TEST',
            'password' => 'Fixture-secret-123!', 'password_confirmation' => 'Fixture-secret-123!',
        ];
        $lock = 'tempmail:'.substr(hash('sha256', $database), 0, 48);
        $this->assertSame(1, (int) $pdo->query("SELECT GET_LOCK('{$lock}', 0)")->fetchColumn());
        try {
            $this->submit($this->get($url)->assertOk(), $account, $url)->assertStatus(409);
            $this->assertFileDoesNotExist($this->directory.'/finalization');
            $this->assertFalse($pdo->query('SHOW FULL TABLES')->fetchColumn());
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('{$lock}')");
        }

        $this->submit($this->get($url)->assertOk(), $account, $url)->assertStatus(503)
            ->assertDontSee($account['password']);
        $this->assertFileDoesNotExist($this->directory.'/installed');
        $this->assertFileDoesNotExist($this->app->environmentFilePath());
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());

        $this->app->forgetInstance(InstallerEnvironment::class);
        $this->submit($this->get($url)->assertOk(), $account, $url)->assertStatus(303)
            ->assertHeader('Location', '/setup-complete');
        $user = $pdo->query('SELECT email, password, is_super_admin FROM users')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('owner@example.test', $user['email']);
        $this->assertTrue(Hash::check($account['password'], $user['password']));
        $this->assertSame(1, (int) $user['is_super_admin']);
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM workspace_installation')->fetchColumn());
        $this->assertFileExists($this->directory.'/installed');
        foreach (['key', 'lease', 'enable.json', 'finalization'] as $name) {
            $this->assertFileDoesNotExist($this->directory.'/'.$name);
        }

        $environment = Dotenv::parse(file_get_contents($this->app->environmentFilePath()));
        $this->assertSame($engine, $environment['DB_CONNECTION']);
        $this->assertSame('', $environment['DB_PASSWORD']);
        config(['app.key' => $environment['APP_KEY']]);
        $this->get('http://localhost:8000/setup-complete')->assertRedirect('/login')->assertSessionHas('status');
        $this->get($url)->assertNotFound();
        $this->post($url, $account)->assertNotFound();
    }

    public function test_all_engine_options_are_visible_and_unavailable_drivers_cannot_be_submitted(): void
    {
        $page = $this->open()->assertSee('Database engine');
        foreach (DatabaseEngine::cases() as $engine) {
            $page->assertSee($engine->label());
            if (! $engine->available()) {
                $page->assertSee('requires '.$engine->extension());
            }
        }

        $unavailable = array_find(DatabaseEngine::cases(), static fn (DatabaseEngine $engine): bool => ! $engine->available());
        if ($unavailable !== null) {
            $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
            $response = $this->submit($page, ['engine' => $unavailable->value])->assertHeader('Location', '/install');
            $this->continueWith($response);
            $this->open()->assertSee('needs the '.$unavailable->extension());
        }
    }

    public function test_sqlite_rejects_supplied_paths_and_existing_files_then_finalizes_a_new_private_file(): void
    {
        $this->app->useEnvironmentPath($this->directory);
        $this->app->instance('installer.external_environment', []);
        $submitSqlite = function (TestResponse $page, array $extra = []): TestResponse {
            $this->continueWith($page);
            preg_match('/name="_token" value="([^"]+)"/', $page->getContent(), $token);

            return $this->post('http://localhost:8000/install', [
                '_token' => $token[1], 'engine' => 'sqlite',
            ] + $extra, ['Content-Type' => 'application/x-www-form-urlencoded']);
        };
        $path = $this->directory.'/workspace.sqlite';
        $bad = $submitSqlite($this->open(), ['path' => '../other.sqlite'])->assertHeader('Location', '/install');
        $this->continueWith($bad);
        $this->assertFileDoesNotExist($path);
        file_put_contents($path, 'Unrelated fixture content');
        $bad = $submitSqlite($this->open())->assertHeader('Location', '/install');
        $this->continueWith($bad);
        $this->open()->assertSee('Could not verify an empty database');
        $this->assertSame('Unrelated fixture content', file_get_contents($path));
        unlink($path);

        $unrelated = $this->directory.'/unrelated-file';
        file_put_contents($unrelated, 'Keep this file');
        if (@symlink($unrelated, $path)) {
            $bad = $submitSqlite($this->open())->assertHeader('Location', '/install');
            $this->continueWith($bad);
            $this->open()->assertSee('Could not verify an empty database');
            $this->assertSame('Keep this file', file_get_contents($unrelated));
            unlink($path);
        }

        $verified = $submitSqlite($this->open())->assertHeader('Location', '/install/admin');
        $this->assertFileDoesNotExist($path);
        $this->continueWith($verified);
        $url = 'http://localhost:8000/install/admin';
        $response = $this->submit($this->get($url)->assertOk(), [
            'name' => 'SQLite Owner', 'email' => 'owner@example.test',
            'password' => 'sqlite-fixture-secret', 'password_confirmation' => 'sqlite-fixture-secret',
        ], $url);
        $response->assertStatus(303);
        $environment = Dotenv::parse(file_get_contents($this->app->environmentFilePath()));
        $this->assertSame('sqlite', $environment['DB_CONNECTION']);
        $this->assertSame(realpath($path), $environment['DB_DATABASE']);
        $pdo = new PDO('sqlite:'.$path);
        $admin = $pdo->query('SELECT email, is_super_admin FROM users')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['email' => 'owner@example.test', 'is_super_admin' => 1], $admin);
        $this->get($url)->assertNotFound();
    }

    public function test_production_gate_pins_sqlite_without_accepting_a_network_engine(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $this->gate([
            'mode' => 'production', 'host' => 'mail.example.test', 'operator_ip' => '203.0.113.17',
            'database_engine' => 'sqlite',
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.17']);
        $url = 'https://mail.example.test/install';
        $page = $this->get($url)->assertOk()->assertSee('outside deployment configuration');
        $this->mock(DatabaseVerifier::class, fn (MockInterface $mock) => $mock->shouldNotReceive('verify'));
        $response = $this->submit($page, ['engine' => 'mysql'], $url)->assertHeader('Location', '/install');
        $this->continueWith($response);
        $this->get($url)->assertSee('Choose a database engine authorized for this installation.');
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
