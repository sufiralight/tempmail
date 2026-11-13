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
        $this->assertDatabaseCount('font_families', 13);
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
        $this->get('/locale/ur')->assertOk()->assertDontSee('fonts.googleapis.com');
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
