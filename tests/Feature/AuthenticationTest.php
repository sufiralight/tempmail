<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/tempmail-auth-'.bin2hex(random_bytes(8));
        mkdir($this->workspace, 0700);
        touch($this->workspace.'/installed');
        touch($this->workspace.'/.env');
        $this->app->useEnvironmentPath($this->workspace);
        config(['installer.path' => $this->workspace, 'app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_authentication_is_closed_before_installation_and_when_environment_is_missing(): void
    {
        unlink($this->workspace.'/installed');
        $this->get('/login')->assertNotFound();
        $this->post('/register', ['email' => 'visitor@example.test'])->assertNotFound();
        touch($this->workspace.'/installed');
        unlink($this->workspace.'/.env');
        $this->get('/dashboard')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_uses_shared_components_regenerates_the_session_and_rejects_external_redirects(): void
    {
        $user = User::factory()->create(['email' => 'member@example.test', 'password' => 'fixture-password-123']);
        $this->get('/dashboard')->assertRedirect('/login');
        $page = $this->get('/login')->assertOk()->assertSee('Welcome back')->assertSee('current-password')
            ->assertSee('Color theme')->assertSee('Forgot password?');
        $oldSession = $page->baseRequest->session()->getId();

        $response = $this->withSession(['url.intended' => 'https://attacker.example/dashboard'])
            ->post('/login', ['email' => 'MEMBER@EXAMPLE.TEST', 'password' => 'fixture-password-123']);
        $response->assertHeader('Location', '/dashboard');
        $this->assertNotSame($oldSession, $response->baseRequest->session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
        $this->get('/admin')->assertForbidden();
    }

    public function test_invalid_credentials_are_generic_and_rate_limited(): void
    {
        User::factory()->create(['email' => 'member@example.test', 'password' => 'fixture-password-123']);

        foreach (['missing@example.test', 'member@example.test'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'wrong-fixture-password'])
                ->assertRedirect('/login')->assertSessionHasErrors(['email' => 'The email or password is incorrect.'])
                ->assertSessionMissing('_old_input.password');
        }

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/login', ['email' => 'MEMBER@EXAMPLE.TEST', 'password' => 'wrong'])->assertRedirect('/login');
        }
        $this->post('/login', ['email' => 'member@example.test', 'password' => 'fixture-password-123'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest();
    }

    public function test_super_admin_uses_the_same_login_and_logout_rotates_session_and_token(): void
    {
        $admin = User::factory()->create(['email' => 'owner@example.test', 'password' => 'fixture-password-123']);
        $admin->forceFill(['is_super_admin' => true])->save();

        $this->get('/admin')->assertRedirect('/login');
        $this->post('/login', ['email' => 'owner@example.test', 'password' => 'fixture-password-123'])
            ->assertHeader('Location', '/admin');
        $this->assertAuthenticatedAs($admin);
        $page = $this->get('/admin')->assertOk()->assertSee('Administration');
        $oldSession = $page->baseRequest->session()->getId();
        $oldToken = $page->baseRequest->session()->token();
        $this->get('/logout')->assertMethodNotAllowed();

        $this->app->detectEnvironment(static fn (): string => 'local');
        $this->post('/logout', [], ['Sec-Fetch-Site' => 'same-origin'])->assertStatus(419);
        $this->assertAuthenticatedAs($admin);
        $response = $this->post('/logout', ['_token' => $oldToken]);
        $response->assertStatus(303)->assertHeader('Location', '/login');
        $this->assertNotSame($oldSession, $response->baseRequest->session()->getId());
        $this->assertNotSame($oldToken, $response->baseRequest->session()->token());
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_registration_hashes_password_and_cannot_assign_admin_privileges(): void
    {
        $password = 'fixture-password-123';
        $input = [
            'name' => 'Fixture Member', 'email' => 'MEMBER@EXAMPLE.TEST',
            'password' => $password, 'password_confirmation' => $password,
        ];
        $this->get('/register')->assertOk()->assertSee('Create your account')->assertSee('new-password');
        $this->post('/register', array_replace($input, ['password' => 'short', 'password_confirmation' => 'other']))
            ->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
        $this->post('/register', $input + ['is_super_admin' => true, 'role' => 'admin'])
            ->assertSessionHasErrors(['is_super_admin', 'role']);
        $this->assertDatabaseCount('users', 0);

        $this->post('/register', $input)->assertHeader('Location', '/dashboard');
        $user = User::query()->sole();
        $this->assertSame('member@example.test', $user->email);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue(Hash::check($password, $user->password));
        $this->post('/logout')->assertStatus(303);
        $this->post('/register', $input)->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_recovery_is_neutral_and_uses_the_configured_origin(): void
    {
        $user = User::factory()->create(['email' => 'member@example.test', 'password' => 'fixture-password-123']);
        config(['mail.default' => 'smtp', 'app.url' => 'https://mail.example.test']);
        Notification::fake();

        foreach (['absent@example.test', $user->email] as $email) {
            $this->post('/forgot-password', ['email' => $email], ['Host' => 'untrusted.example'])
                ->assertHeader('Location', '/forgot-password')
                ->assertSessionHas('status', 'If this account can receive email, a password reset link will be sent.');
        }
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->assertStringStartsWith('https://mail.example.test/reset-password/', $notification->toMail($user)->actionUrl);

            return true;
        });

        $token = Password::createToken($user);
        $this->get('/reset-password/'.$token)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $input = ['email' => $user->email, 'token' => $token, 'password' => 'replacement-password-123', 'password_confirmation' => 'replacement-password-123'];
        $this->post('/reset-password', $input)->assertRedirect('/login');
        $this->assertTrue(Hash::check($input['password'], $user->fresh()->password));
        $this->post('/reset-password', $input)->assertSessionHasErrors('email')->assertSessionMissing('_old_input.token');
    }
}
