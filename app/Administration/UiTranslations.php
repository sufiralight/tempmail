<?php

namespace App\Administration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UiTranslations
{
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Translated', 'review' => 'Needs review'];

    public function ready(): bool
    {
        return once(fn (): bool => Schema::hasColumns('ui_translation_keys', ['id', 'identifier', 'source', 'context'])
            && Schema::hasColumns('ui_translation_values', ['key_id', 'language_id', 'value', 'status', 'updated_at']));
    }

    public function published(int $languageId): array
    {
        if (! $this->ready()) {
            return [];
        }

        return DB::table('ui_translation_values as translation')
            ->join('ui_translation_keys as translation_key', 'translation_key.id', '=', 'translation.key_id')
            ->where('translation.language_id', $languageId)
            ->where('translation.status', 'published')
            ->pluck('translation.value', 'translation_key.identifier')->all();
    }

    public function text(string $key, array $parameters = []): string
    {
        $source = config('ui_translations')[$key] ?? $key;
        $translations = request()->attributes->get('ui_translations', []);
        $text = $translations[$key] ?? trans($key);
        if ($text === $key || ! is_string($text)) {
            $text = $source;
        }

        return preg_replace_callback('/(?<![\w:]):([a-zA-Z][a-zA-Z0-9_]*)/', fn (array $match): string => (string) ($parameters[$match[1]] ?? $match[0]), $text) ?? $text;
    }

    public function placeholders(string $value): array
    {
        preg_match_all('/(?<![\w:]):([a-zA-Z][a-zA-Z0-9_]*)/', $value, $matches);
        $tokens = array_unique($matches[1]);
        sort($tokens);

        return $tokens;
    }
}
