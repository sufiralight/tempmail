<?php

namespace Tests\Feature;

use App\Auth\AccountSessions;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_media_upload_ownership_picker_and_branding_references(): void
    {
        Storage::fake('media');
        $owner = User::factory()->create();
        $owner->forceFill(['is_super_admin' => true])->save();
        $other = User::factory()->create();
        $this->get('/media')->assertRedirect('/login');
        $this->get('/media-library')->assertRedirect('/login');
        $this->actingAs($owner)->get('/media')->assertOk()->assertSee('Media Library')->assertSee('multiple="multiple"', false);
        $this->postJson('/media', ['image' => UploadedFile::fake()->createWithContent('bad.svg', '<svg/>')])
            ->assertUnprocessable()->assertJsonPath('errors.image.0', 'Choose a JPEG, PNG, WebP or GIF image.');
        $this->postJson('/media', ['image' => UploadedFile::fake()->image('large.png')->size(config('media.max_kib') + 1)])
            ->assertUnprocessable()->assertJsonPath('errors.image.0', 'The file exceeds the '.config('media.max_kib').' KiB upload limit.');
        $response = $this->postJson('/media', ['image' => UploadedFile::fake()->image('fixture.png', 12, 14)])
            ->assertCreated();
        $id = $response->json('id');
        $this->get('/media/picker')->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertSee('fixture.png');
        $this->get("/media/{$id}/file?preview=1")->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->patch("/media/{$id}", ['title' => 'New title', 'alt_text' => 'Sample'])->assertRedirect();
        $this->patch("/media-library/{$id}", ['title' => 'Legacy edit'])->assertRedirect("/media/{$id}");
        $this->assertDatabaseHas('media', ['id' => $id, 'title' => 'Legacy edit', 'alt_text' => 'Sample']);
        $this->actingAs($other)->get("/media/{$id}/file")->assertForbidden();
        $this->get('/media/picker')->assertOk()->assertDontSee('fixture.png');
        $this->get('/admin/settings/branding')->assertForbidden();
        $this->get('/admin/media')->assertForbidden();
        $this->get("/media-library/{$id}")->assertForbidden();
        $this->actingAs($owner)->get('/media-library/picker?q=fixture')->assertRedirect('/media/picker?q=fixture');
        $this->get('/admin/media')->assertOk()->assertSee('Legacy edit');
        $this->assertActiveNavigation('/admin/media', '/admin/media');
        $this->assertActiveNavigation("/admin/media/{$id}", '/admin/media');
        $this->assertActiveNavigation('/admin/settings/branding', '/admin/settings/branding');
        $this->assertActiveNavigation('/admin', '/admin');
        $this->get("/admin/media/{$id}")->assertOk()->assertDontSee('Save details');
        $this->actingAs($owner)->get('/admin/settings/branding')->assertOk()->assertSee('Choose image');
        $branding = ['logo' => $id, 'dark-logo' => $id, 'favicon' => null, 'social' => null];
        $this->patch('/admin/settings/branding', $branding)->assertRedirect();
        $this->get('/dashboard')->assertOk()->assertSee('/branding/logo')->assertSee('/branding/dark-logo');
        $this->get('/branding/logo')->assertOk();
        $this->delete("/media/{$id}")->assertSessionHasErrors('media');
        $this->patch('/admin/settings/branding', array_fill_keys(array_keys($branding), null))->assertRedirect();
        $this->post('/media/bulk-delete', ['items' => [$id]])->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $id]);
        $this->assertDatabaseHas('administrative_audits', ['action' => 'media.bulk_deleted', 'target_id' => $id]);
    }

    public function test_workspace_shell_groups_navigation_by_permissions_and_verification(): void
    {
        $member = User::factory()->unverified()->create();
        $this->actingAs($member)->get('/dashboard')->assertOk()
            ->assertSee('Account overview')->assertSee('Open workspace navigation')
            ->assertSee('Account menu')->assertSee('Email verification')
            ->assertSee($member->email)->assertSee('My Profile')->assertSee('Preferences')
            ->assertDontSee('Temporary inbox')->assertDontSee('Quick actions')->assertDontSee('Admin Console');
        $this->get('/admin')->assertForbidden();

        foreach ([
            '/dashboard' => '/dashboard',
            '/profile' => null,
            '/profile/security' => null,
            '/profile/sessions' => null,
            '/profile/preferences' => null,
            '/email/verify' => null,
            '/media' => '/media',
        ] as $page => $active) {
            $this->assertActiveNavigation($page, $active, ['/dashboard', '/media']);
        }

        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Admin Console')->assertDontSee('Media audit');
        $this->assertActiveNavigation('/dashboard', '/dashboard', ['/dashboard', '/media', '/admin']);
        $this->get('/admin')->assertOk()->assertSee('Admin Overview')->assertSee('Media audit');
        $admin->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Verify your email to access Admin Console.')
            ->assertDontSee('href="/admin"', false);
        $this->get('/admin')->assertRedirect('/email/verify');
    }

    private function assertActiveNavigation(string $page, ?string $active, ?array $destinations = null): void
    {
        $html = $this->get($page)->assertOk()->getContent();
        preg_match_all('/<nav aria-label="Workspace navigation".*?<\/nav>/s', $html, $menus);
        $this->assertCount(2, $menus[0]);

        foreach ($menus[0] as $menu) {
            preg_match_all('/<a\b[^>]*aria-current="page"[^>]*>/s', $menu, $links);
            $this->assertCount($active === null ? 0 : 1, $links[0]);
            if ($active !== null) {
                $this->assertStringContainsString('href="'.$active.'"', $links[0][0]);
            }
            if ($destinations !== null) {
                preg_match_all('/<a\b[^>]*href="([^"]+)"/', $menu, $hrefs);
                $this->assertSame($destinations, $hrefs[1]);
            }
        }
    }

    public function test_media_upload_rejects_stale_tokens_and_throttles_repeated_requests(): void
    {
        Storage::fake('media');
        $this->postJson('/media')->assertUnauthorized();
        $this->actingAs(User::factory()->create());

        // Exercise the real middleware instead of its PHPUnit shortcut.
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'current-token']);
        $this->postJson('/media', ['_token' => 'stale-token'], ['Sec-Fetch-Site' => 'same-origin'])
            ->assertStatus(419)->assertJsonPath('message', 'Open the form again and try once more.');

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/media', ['_token' => 'current-token'])->assertUnprocessable()->assertJsonValidationErrors('image');
        }

        $this->postJson('/media', ['_token' => 'current-token'])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_profile_security_preferences_and_scoped_session_revocation(): void
    {
        $password = 'Fixture current password 42';
        $user = User::factory()->create(['password' => $password]);
        $other = User::factory()->create();
        $this->get('/profile')->assertRedirect('/login');
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('Personal information');
        $this->patch('/profile', ['name' => 'New name', 'email' => $other->email, 'current_password' => $password])->assertSessionHasErrors('email');
        $this->patch('/profile', ['name' => 'New name', 'email' => 'new@example.test', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->patch('/profile', ['name' => 'New name', 'email' => 'new@example.test'])->assertSessionHasErrors('current_password');
        $this->patch('/profile', ['name' => 'New name', 'email' => 'new@example.test', 'current_password' => $password])->assertRedirect('/profile');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->isSuperAdmin());
        $this->patch('/profile/preferences', ['appearance' => 'dark', 'interface_locale' => 'xx', 'timezone' => 'invalid'])->assertSessionHasErrors(['interface_locale', 'timezone']);
        $this->patch('/profile/preferences', ['appearance' => 'dark', 'interface_locale' => 'en', 'timezone' => 'Europe/Istanbul'])->assertRedirect('/profile/preferences');
        $this->assertSame('dark', $user->fresh()->appearance);
        $page = $this->actingAs($user->fresh())->get('/profile/security')->assertOk()->assertSee('profile-strength')->assertSee('data-page-progress-root');
        $this->withUnencryptedCookie(config('session.cookie'), $page->getCookie(config('session.cookie'), false)->getValue());
        $current = DB::table('account_sessions')
            ->where('digest', app(AccountSessions::class)->digest($page->baseRequest))->first();
        $db = DB::table('account_sessions');
        foreach ([$user, $other, $user] as $index => $account) {
            $ids[$index] = (string) Str::uuid();
            $db->insert(['id' => $ids[$index], 'user_id' => $account->id, 'digest' => hash('sha256', $ids[$index]), 'device' => 'Fixture browser', 'last_seen' => now()->timestamp]);
        }
        $this->get('/profile/sessions')->assertOk()->assertSee('Current session')->assertDontSee($current->digest);
        $this->delete('/profile/sessions/'.$ids[1], ['current_password' => $password])->assertRedirect('/profile/sessions');
        $this->assertDatabaseHas('account_sessions', ['id' => $ids[1], 'revoked' => false]);
        $this->delete('/profile/sessions/'.$ids[0], ['current_password' => $password])->assertRedirect('/profile/sessions');
        $this->assertDatabaseHas('account_sessions', ['id' => $ids[0], 'revoked' => true]);
        $this->post('/profile/sessions/revoke-others', ['current_password' => $password])->assertRedirect('/profile/sessions');
        $this->assertDatabaseHas('account_sessions', ['id' => $ids[2], 'revoked' => true]);
        $this->assertDatabaseHas('account_sessions', ['id' => $current->id, 'revoked' => false]);
        $this->travel(1)->minutes();
        $this->patch('/profile/password', ['current_password' => 'wrong', 'password' => 'Fixture new password 82', 'password_confirmation' => 'Fixture new password 82'])->assertSessionHasErrors('current_password');
        $before = $user->fresh()->remember_token;
        $this->patch('/profile/password', ['current_password' => $password, 'password' => 'Fixture new password 82', 'password_confirmation' => 'Fixture new password 82'])->assertRedirect('/profile/security');
        $this->assertNotSame($before, $user->fresh()->remember_token);
        $this->assertTrue(Hash::check('Fixture new password 82', $user->fresh()->password));
        $this->assertDatabaseHas('administrative_audits', ['action' => 'account.password_changed', 'target_id' => $user->id, 'metadata' => '[]']);
    }

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

    public function test_expired_reset_token_and_common_password_cannot_change_the_account(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'password1234', 'password_confirmation' => 'password1234'];
        $this->post('/reset-password', $payload)->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
        $this->travel(61)->minutes();
        $payload['password'] = $payload['password_confirmation'] = 'Fixture autumn river 39!';
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_verification_requires_signed_unexpired_link_for_the_current_account(): void
    {
        $user = User::factory()->unverified()->create();
        $parameters = ['id' => $user->id, 'hash' => sha1($user->email)];
        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), $parameters, absolute: false);
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), $parameters, absolute: false);
        $this->get($link)->assertRedirect('/login');
        $this->actingAs(User::factory()->unverified()->create())->get($link)->assertForbidden();
        $this->actingAs($user)->get($expired)->assertForbidden();
        $this->get($link.'&tampered=1')->assertForbidden();
        $this->get('/email/verify')->assertOk();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->get($link)->assertRedirect('/dashboard');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get($link)->assertRedirect('/dashboard');
    }

    public function test_verification_resend_is_throttled_and_admin_login_remains_available(): void
    {
        $admin = User::factory()->unverified()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        config(['mail.default' => 'smtp', 'app.url' => 'https://mail.example.test']);
        Notification::fake();
        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->get('/admin')->assertRedirect('/email/verify');
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/email/verification-notification')->assertRedirect('/email/verify');
        }
        $this->post('/email/verification-notification')->assertStatus(429);
        Notification::assertSentToTimes($admin, VerifyEmail::class, 3);
        Notification::assertSentTo($admin, VerifyEmail::class, fn (VerifyEmail $mail) => str_starts_with($mail->toMail($admin)->actionUrl, 'https://mail.example.test/email/verify/'));
    }

    public function test_workspace_permissions_changes_and_audits(): void
    {
        $owner = User::factory()->create();
        $owner->forceFill(['is_super_admin' => true])->save();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $member = User::factory()->create(['name' => 'Unique Member']);
        User::factory()->count(22)->create();

        $this->actingAs($member)->get('/dashboard')->assertOk()->assertSee('Unique Member')
            ->assertSee('data-page-progress-root', false)->assertDontSee('href="/admin/users"', false);
        $this->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users?q=Unique&role=user')->assertOk()->assertSee('Unique Member');
        $this->get('/admin/users?page=2')->assertOk()->assertSee('Page 2');
        $this->get('/admin/settings')->assertOk();
        $this->patch('/admin/settings', ['display_name' => 'Forbidden'])->assertForbidden();
        $this->patch('/admin/users/'.$admin->id, ['role' => 'super_admin', 'active' => true, 'confirmed' => true])->assertForbidden();
        $this->get('/admin/audit-logs')->assertForbidden();

        $this->actingAs($owner)->get('/admin/roles')->assertOk()->assertSee('Super Admin');
        $this->patch('/admin/users/'.$owner->id, ['role' => 'user', 'active' => false, 'confirmed' => true])
            ->assertSessionHasErrors('role');
        $this->assertTrue($owner->fresh()->isSuperAdmin());
        $this->patch('/admin/users/'.$member->id, [
            'role' => 'admin', 'active' => false, 'confirmed' => true, 'password' => 'never-audit-this',
        ])->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);
        $this->assertSame('admin', $member->fresh()->workspaceRole()->value);
        $this->patch('/admin/settings', ['display_name' => 'Fixture Workspace'])->assertRedirect();
        $this->get('/admin/settings')->assertOk()->assertSee('Fixture Workspace');
        $this->get('/admin/audit-logs')->assertOk()->assertSee('user.role_changed')
            ->assertSee('user.deactivated')->assertSee('settings.updated')->assertDontSee('never-audit-this');
        $this->assertDatabaseHas('administrative_audits', ['action' => 'user.change_denied', 'actor_id' => $owner->id]);
        $this->actingAs($member->fresh())->get('/dashboard')->assertRedirect('/login');
    }
}
