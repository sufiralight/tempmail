<?php

namespace Tests\Feature;

use App\Administration\Typography;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TypographyTest extends TestCase
{
    use RefreshDatabase;

    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = sys_get_temp_dir().'/tempmail-fonts-'.bin2hex(random_bytes(8));
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

    public function test_catalog_and_public_stylesheet_follow_locale_and_assignments(): void
    {
        $this->assertDatabaseHas('font_families', ['name' => 'Inter']);
        $urdu = DB::table('languages')->where('code', 'ur')->value('id');
        $font = DB::table('font_families')->where('name', 'Noto Nastaliq Urdu')->value('id');
        $this->get('/')->assertOk()->assertDontSee('fonts.googleapis.com');
        $this->get('/locale/ur')->assertNotFound();
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->get("/admin/fonts?language=$urdu&script=urdu")->assertOk()->assertSee('Noto Nastaliq Urdu');
        $assignment = ['font_family_id' => $font, 'weight' => 400, 'fallback' => 'system'];
        $this->put("/admin/fonts/assignments/$urdu/body", $assignment)->assertRedirect();
        $this->put("/admin/fonts/assignments/$urdu/body", [...$assignment, 'weight' => 600])->assertRedirect();
        $this->assertDatabaseCount('font_assignments', 1);
        $this->assertDatabaseHas('administrative_audits', ['action' => 'font.assigned', 'target_id' => $urdu]);
        $this->patchJson("/admin/fonts/$font/visibility", ['is_active' => false])->assertUnprocessable();
        DB::table('languages')->where('id', $urdu)->update(['is_active' => true]);
        $this->get('/locale/ur')->assertOk()->assertSee('lang="ur" dir="rtl"', false)
            ->assertSee('Noto+Nastaliq+Urdu:wght@600', false)->assertDontSee('Noto+Sans+JP');
        $this->delete("/admin/fonts/assignments/$urdu/body")->assertRedirect();
        $this->assertDatabaseEmpty('font_assignments');
        $this->patch("/admin/fonts/$font/visibility", ['is_active' => false])->assertRedirect();
        app(Typography::class)->seed();
        $this->assertDatabaseHas('font_families', ['id' => $font, 'is_active' => false]);
        $this->get('/locale/ur')->assertOk()->assertSee('Noto+Sans+Arabic', false);
    }

    public function test_script_default_is_shared_and_language_override_can_be_reset(): void
    {
        $type = app(Typography::class);
        $this->assertSame('Geist Sans', $type->inherited('latin'));
        $this->assertSame('Noto Sans Hebrew', $type->inherited('hebrew'));
        $this->assertSame('Noto Sans Arabic', $type->inherited('urdu'));
        foreach (config('locales') as $code => $locale) {
            $this->assertNotNull($type->inherited($type->script($code)), $code);
        }

        $turkish = DB::table('languages')->where('code', 'tr')->value('id');
        $spanish = DB::table('languages')->where('code', 'es')->value('id');
        $inter = DB::table('font_families')->where('name', 'Inter')->value('id');
        $geist = DB::table('font_families')->where('name', 'Geist Sans')->value('id');
        $this->actingAs(User::factory()->create())->putJson("/admin/fonts/defaults/$turkish", ['default_family_id' => $inter])->assertForbidden();
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->put("/admin/fonts/defaults/$turkish", ['default_family_id' => $inter])->assertSessionHasNoErrors();
        $this->assertSame('Inter', $type->resolve($spanish, 'es')['body']['family']);
        $this->put("/admin/fonts/assignments/$turkish/body", ['font_family_id' => $geist, 'weight' => 400, 'fallback' => 'system'])->assertSessionHasNoErrors();
        $this->assertSame('Geist Sans', $type->resolve($turkish, 'tr')['body']['family']);
        $this->delete("/admin/fonts/assignments/$turkish/body")->assertRedirect();
        $this->assertSame('Inter', $type->resolve($turkish, 'tr')['body']['family']);
        $this->get("/admin/fonts?language=$turkish")->assertSee('demo@example.com')->assertSee('msg-001');
    }

    public function test_italic_assignment_is_persisted_and_loaded_only_for_the_selected_role(): void
    {
        $language = DB::table('languages')->where('code', 'en')->value('id');
        $family = DB::table('font_families')->where('name', 'Roboto')->value('id');
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->put("/admin/fonts/assignments/$language/heading", [
            'font_family_id' => $family, 'weight' => 600, 'fallback' => 'system', 'style' => 'italic',
        ])->assertRedirect();
        $this->assertDatabaseHas('font_assignments', ['language_id' => $language, 'role' => 'heading', 'style' => 'italic']);
        $this->get('/')->assertOk()->assertSee('Roboto:ital,wght@1,600', false)
            ->assertSee('--style-heading: italic', false)->assertDontSee('Roboto:wght@400', false);
    }

    public function test_font_writes_validate_roles_coverage_and_structured_fallbacks(): void
    {
        $language = DB::table('languages')->where('code', 'ar')->value('id');
        $font = DB::table('font_families')->where('name', 'Roboto')->value('id');
        $url = "/admin/fonts/assignments/$language/heading";
        $this->actingAs(User::factory()->create())->putJson($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['is_super_admin' => true]));
        $this->putJson($url, ['font_family_id' => $font, 'weight' => 400, 'fallback' => 'system'])
            ->assertUnprocessable()->assertJsonValidationErrors('font_family_id');
        $this->putJson($url, ['weight' => 900, 'fallback' => 'url(https://example.test)'])
            ->assertUnprocessable()->assertJsonValidationErrors(['weight', 'fallback']);
        $this->assertDatabaseEmpty('font_assignments');
    }
}
