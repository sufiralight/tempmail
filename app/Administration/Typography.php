<?php

namespace App\Administration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class Typography
{
    public const ROLES = ['body', 'heading'];

    public const FALLBACKS = [
        'system' => 'ui-sans-serif, system-ui, sans-serif',
        'sans' => 'Arial, sans-serif',
        'serif' => 'Georgia, serif',
    ];

    public function ready(): bool
    {
        return once(fn (): bool => Schema::hasTable('font_families') && Schema::hasTable('font_assignments'));
    }

    public function styles(?string $family): array
    {
        return $family === 'Roboto' ? ['normal', 'italic'] : ['normal'];
    }

    public function stylesReady(): bool
    {
        return once(fn (): bool => Schema::hasColumn('font_assignments', 'style'));
    }

    public function seed(): void
    {
        foreach (array_keys(config('typography.families')) as $name) {
            DB::table('font_families')->insertOrIgnore(['name' => $name]);
        }
    }

    public function script(string $locale): string
    {
        return config('typography.scripts')[$locale] ?? 'latin';
    }

    public function resolve(int $languageId, string $locale): array
    {
        $assignments = DB::table('font_assignments as assignment')
            ->leftJoin('font_families as family', 'family.id', '=', 'assignment.font_family_id')
            ->where('assignment.language_id', $languageId)
            ->select('assignment.*', 'family.name', 'family.is_active')
            ->get()->keyBy('role');
        $script = $this->script($locale);
        $catalog = config('typography.families');
        $inherited = null;

        if (! in_array($script, ['latin', 'latin-ext', 'cyrillic', 'vietnamese'], true)) {
            foreach (DB::table('font_families')->where('is_active', true)->pluck('name') as $name) {
                if (in_array($script, $catalog[$name] ?? [], true)) {
                    $inherited = $name;
                    break;
                }
            }
        }

        $fonts = [];
        foreach (self::ROLES as $role) {
            $assignment = $assignments->get($role);
            $name = $assignment === null ? $inherited : ($assignment->is_active ? $assignment->name : null);
            $fonts[$role] = [
                'family' => in_array($script, $catalog[$name] ?? [], true) ? $name : null,
                'style' => $name === null || ! in_array($assignment->style ?? 'normal', $this->styles($name), true) ? 'normal' : ($assignment->style ?? 'normal'),
                'weight' => (int) ($assignment->weight ?? ($role === 'heading' ? 600 : 400)),
                'fallback' => self::FALLBACKS[$assignment->fallback ?? 'system'] ?? self::FALLBACKS['system'],
            ];
        }

        return $fonts;
    }

    public function stylesheet(array $fonts): ?string
    {
        $families = [];
        foreach ($fonts as $font) {
            if (isset(config('typography.families')[$font['family']]) && in_array($font['weight'], [400, 600], true)) {
                $style = $font['style'] ?? 'normal';
                if (in_array($style, $this->styles($font['family']), true)) {
                    $families[$font['family']][$style][$font['weight']] = $font['weight'];
                }
            }
        }
        ksort($families);
        $parameters = [];
        foreach ($families as $name => $styles) {
            $italic = isset($styles['italic']);
            $variants = [];
            foreach (['normal', 'italic'] as $style) {
                $weights = $styles[$style] ?? [];
                sort($weights);
                foreach ($weights as $weight) {
                    $variants[] = ($italic ? ($style === 'italic' ? '1,' : '0,') : '').$weight;
                }
            }
            $parameters[] = 'family='.urlencode($name).($italic ? ':ital,wght@' : ':wght@').implode(';', $variants);
        }

        return $parameters === [] ? null : 'https://fonts.googleapis.com/css2?'.implode('&', $parameters).'&display=swap';
    }
}
