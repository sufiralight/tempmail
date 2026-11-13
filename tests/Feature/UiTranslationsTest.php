<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UiTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = sys_get_temp_dir().'/tempmail-translation-'.bin2hex(random_bytes(8));
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

    public function test_translation_write_requires_a_csrf_token(): void
    {
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->app->instance('env', 'local');
        $language = DB::table('languages')->where('code', 'en')->value('id');
        $key = DB::table('ui_translation_keys')->where('identifier', 'home.headline')->value('id');
        $url = "/admin/ui-translations/$language/$key";
        $this->withHeader('Sec-Fetch-Site', 'same-origin')->patchJson($url, ['action' => 'reset'])->assertStatus(419);
        $this->withSession(['_token' => 'translation-test-token'])
            ->patchJson($url, ['action' => 'reset', '_token' => 'translation-test-token'])->assertRedirect();
    }

    public function test_translation_visibility_reset_and_safe_escaping(): void
    {
        $language = DB::table('languages')->where('code', 'ar')->value('id');
        $key = DB::table('ui_translation_keys')->where('identifier', 'home.headline')->value('id');
        $url = "/admin/ui-translations/$language/$key";

        $this->actingAs(User::factory()->create())->patchJson($url, ['action' => 'reset'])->assertForbidden();
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->get("/admin/ui-translations?language=$language&edit=$key&group=home")->assertOk()
            ->assertSee('dir="rtl" lang="ar"', false)->assertSee('home.headline');
        $value = '<script>example</script> نص';
        $this->patch($url, ['action' => 'save', 'value' => $value, 'status' => 'draft'])->assertRedirect();
        $this->get('/locale/ar')->assertNotFound();
        DB::table('languages')->where('id', $language)->update(['is_active' => true]);
        $this->get('/locale/ar')->assertOk()->assertSee('Temporary email. Keep your inbox yours.')->assertDontSee($value);
        $this->patch($url, ['action' => 'save', 'value' => $value, 'status' => 'published'])->assertRedirect();
        $this->get('/locale/ar')->assertOk()->assertSee($value)->assertDontSee($value, false);
        $this->get("/admin/ui-translations?language=$language&edit=$key")->assertSee($value);
        $this->assertDatabaseHas('administrative_audits', ['action' => 'ui_translation.save', 'target_id' => $key]);
        $this->patchJson($url, ['action' => 'save', 'value' => 'Extra :name', 'status' => 'published'])
            ->assertUnprocessable()->assertJsonValidationErrors('value');
        $this->patch($url, ['action' => 'reset'])->assertRedirect();
        $this->get('/locale/ar')->assertSee('Temporary email. Keep your inbox yours.');
    }
}
