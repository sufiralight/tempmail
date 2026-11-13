<?php

namespace App\Administration;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LanguageRegistry
{
    public function ready(): bool
    {
        return once(fn (): bool => Schema::hasTable('languages') && Schema::hasTable('language_defaults')
            && DB::table('language_defaults')->where('id', 1)->exists());
    }

    public function query(): Builder
    {
        return DB::table('languages');
    }

    public function defaults(): Builder
    {
        return DB::table('language_defaults')->where('id', 1);
    }

    public function seed(): void
    {
        DB::transaction(function (): void {
            $position = 0;
            foreach (config('locales') as $code => $locale) {
                $this->query()->insertOrIgnore([
                    'code' => $code, 'name' => $locale[0], 'native_name' => $locale[1],
                    'direction' => $locale[2] ?? 'ltr', 'is_active' => $code === 'en',
                    'sort_order' => ++$position, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('language_defaults')->insertOrIgnore([
                'id' => 1, 'language_id' => $this->query()->where('code', 'en')->value('id'),
            ]);
        });
    }

    public function change(Closure $operation): void
    {
        DB::transaction(function () use ($operation): void {
            $default = $this->defaults()->lockForUpdate()->first();
            abort_if($default === null, 503);
            $operation((int) $default->language_id);
        }, 3);
    }

    public function active(): Builder
    {
        return $this->query()->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function normalize(string $code): string
    {
        $code = strtolower(str_replace('_', '-', $code));

        return match ($code) {
            'zh-cn' => 'zh-CN',
            'zh-tw' => 'zh-TW',
            default => $code,
        };
    }
}
