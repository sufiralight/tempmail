<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_button_variants_sizes_and_disabled_state_render(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.button variant="outline" size="sm" disabled>Unavailable</x-ui.button>
            <x-ui.button href="/target" variant="secondary" size="lg">View</x-ui.button>
            <x-ui.button type="submit" loading>Save</x-ui.button>
            BLADE);

        $this->assertStringContainsString('ui-button-outline ui-button-sm', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('<a href="/target"', $html);
        $this->assertStringContainsString('ui-button-secondary ui-button-lg', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
    }

    public function test_fields_associate_labels_help_and_errors_and_exclude_password_values(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.input id="email" name="email" type="email" label="Email" help="Use a work address."
                error="Email is required." aria-describedby="external" required />
            <x-ui.password-field id="password" name="password" autocomplete="new-password" value="must-not-render" />
            BLADE);

        $this->assertStringContainsString('for="email"', $html);
        $this->assertStringContainsString('aria-describedby="external email-help email-error"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('id="email-error"', $html);
        $this->assertStringContainsString('autocomplete="new-password"', $html);
        $this->assertStringContainsString('aria-label="Show password"', $html);
        $this->assertStringNotContainsString('must-not-render', $html);
    }

    public function test_media_and_pagination_components_render_escaped_content(): void
    {
        $paginator = new LengthAwarePaginator(['first'], 2, 1, 1, ['path' => '/records']);
        $html = Blade::render(<<<'BLADE'
            <x-ui.file-upload name="photo" label="Photo" :progress="140" />
            <x-ui.image-preview src="/example.png" alt="Photo" thumbnail>{{ $caption }}</x-ui.image-preview>
            <x-ui.pagination :$paginator />
            BLADE, ['paginator' => $paginator, 'caption' => '<unsafe>']);

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('value="100"', $html);
        $this->assertStringContainsString('ui-image-thumbnail', $html);
        $this->assertStringContainsString('&lt;unsafe&gt;', $html);
        $this->assertStringContainsString('/records?page=2', $html);
    }

    public function test_local_preview_is_unavailable_in_the_default_test_environment(): void
    {
        $this->get('/_ui')->assertNotFound();
        $this->get('/up')->assertOk();
    }
}
