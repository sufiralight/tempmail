<?php

namespace Tests\Feature;

use App\Administration\LanguageRegistry;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LanguageRegistryTest extends TestCase
{
    use RefreshDatabase;

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = sys_get_temp_dir().'/tempmail-language-'.bin2hex(random_bytes(8));
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

    public function test_registry_is_seeded_and_reseeding_preserves_changes(): void
    {
        $this->assertDatabaseCount('languages', 30);
        $this->assertDatabaseCount('language_defaults', 1);
        $this->assertSame(array_keys(config('locales')), DB::table('languages')->orderBy('sort_order')->pluck('code')->all());
        DB::table('languages')->where('code', 'ar')->update(['is_active' => true]);
        app(LanguageRegistry::class)->seed();
        $this->assertDatabaseCount('languages', 30);
        $this->assertDatabaseHas('languages', ['code' => 'ar', 'is_active' => true]);
    }

    public function test_super_admin_can_filter_activate_and_replace_the_default_atomically(): void
    {
        $english = DB::table('languages')->where('code', 'en')->value('id');
        $arabic = DB::table('languages')->where('code', 'ar')->value('id');
        $hebrew = DB::table('languages')->where('code', 'he')->value('id');
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->get('/admin/languages?q=Arabic&direction=rtl')->assertOk()->assertSee('Arabic')->assertDontSee('Hebrew');
        $this->get("/admin/languages/$arabic")->assertOk()->assertSee('Save changes');
        $this->postJson("/admin/languages/$arabic/default")->assertUnprocessable();
        $this->post('/admin/languages/bulk', ['action' => 'activate', 'languages' => [$arabic, $hebrew]])->assertRedirect();
        $this->postJson('/admin/languages/bulk', ['action' => 'deactivate', 'languages' => [$english, $arabic]])->assertUnprocessable();
        $this->assertDatabaseHas('languages', ['id' => $arabic, 'is_active' => true]);
        $this->post("/admin/languages/$arabic/default")->assertRedirect();
        $this->assertDatabaseHas('language_defaults', ['id' => 1, 'language_id' => $arabic]);
        $this->post('/admin/languages/bulk', ['action' => 'deactivate', 'languages' => [$english, $hebrew]])->assertRedirect();
        $this->assertDatabaseHas('languages', ['id' => $english, 'is_active' => false]);
        $this->patchJson("/admin/languages/$arabic", ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'sort_order' => 12, 'is_active' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_public_locale_resolution_tracks_visibility_and_direction(): void
    {
        $this->get('/')->assertOk()->assertSee('lang="en" dir="ltr"', false)->assertDontSee('/locale/ar');
        $this->get('/locale/ar')->assertNotFound();
        $this->get('/locale/xx')->assertNotFound();
        DB::table('languages')->whereIn('code', ['ar', 'he', 'fa', 'zh-CN'])->update(['is_active' => true]);
        $this->get('/')->assertOk()->assertSee('/locale/ar');
        foreach (['ar', 'he', 'fa'] as $code) {
            $this->get("/locale/$code")->assertOk()->assertSee('lang="'.$code.'" dir="rtl"', false);
        }
        $this->get('/locale/ZH_cn')->assertOk()->assertSee('lang="zh-CN" dir="ltr"', false);
        $this->withSession(['locale' => 'fa'])->get('/')->assertSee('lang="fa" dir="rtl"', false);
        DB::table('languages')->where('code', 'fa')->update(['is_active' => false]);
        $this->get('/')->assertSee('lang="en" dir="ltr"', false)->assertDontSee('/locale/fa');
        $this->get('/locale/fa')->assertNotFound();
    }

    public function test_member_and_unverified_super_admin_cannot_manage_languages(): void
    {
        $this->get('/admin/languages')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/languages')->assertForbidden();
        $this->postJson('/admin/languages/bulk', ['action' => 'activate', 'languages' => [2]])->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create(['is_super_admin' => true]))
            ->get('/admin/languages')->assertRedirect('/email/verify');
    }
}
