<?php

namespace App\Administration;

use Illuminate\Support\Collection;
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
            ->where('translation.status', 'published')->where('translation.value', '<>', '')
            ->pluck('translation.value', 'translation_key.identifier')->all();
    }

    public function homepageReady(): bool
    {
        return $this->ready() && Schema::hasColumn('ui_translation_values', 'draft_value');
    }

    public function homepage(int $languageId): Collection
    {
        return DB::table('ui_translation_keys as catalog')
            ->leftJoin('ui_translation_values as translation', fn ($join) => $join->on('catalog.id', '=', 'translation.key_id')->where('translation.language_id', $languageId))
            ->whereIn('catalog.identifier', array_keys(config('ui_translations')))
            ->select('catalog.id', 'catalog.identifier', 'translation.value', 'translation.status', 'translation.draft_value')
            ->orderBy('catalog.identifier')->get();
    }

    public function revision(Collection $entries): string
    {
        return hash('sha256', $entries->toJson(JSON_THROW_ON_ERROR));
    }

    public function text(string $key, array $parameters = []): string
    {
        $source = config('ui_translations')[$key] ?? $key;
        $translations = request()->attributes->get('ui_translations', []);
        $text = $translations[$key] ?? $source;
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
